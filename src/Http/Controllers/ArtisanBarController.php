<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Neluttu\ArtisanBar\ArtisanBarAuth;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ArtisanBarController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        if (! ArtisanBarAuth::supportsPasswordLogin()) {
            return response()->json(['ok' => false, 'output' => 'Password login is not enabled for this auth mode.'], 400);
        }

        $password = $request->input('password', '');

        if (ArtisanBarAuth::verifyPassword($password)) {
            ArtisanBarAuth::setSession($request);
            $this->auditLog('login:success', $request);

            return response()->json(['ok' => true, 'output' => 'Authenticated.']);
        }

        $this->auditLog('login:failed', $request);

        return response()->json(['ok' => false, 'output' => 'Invalid password.'], 401);
    }

    public function logout(Request $request): JsonResponse
    {
        ArtisanBarAuth::clearSession($request);

        return response()->json(['ok' => true, 'output' => 'Logged out.']);
    }

    public function run(Request $request): JsonResponse
    {
        $auth = ArtisanBarAuth::authorize($request);

        if (! $auth->allowed) {
            return response()->json(['ok' => false, 'output' => 'Not authenticated.'], $auth->status);
        }

        $cmd = trim($request->input('cmd', ''));
        $confirmed = (bool) $request->input('confirmed', false);

        if ($cmd === '') {
            return response()->json(['ok' => false, 'output' => 'No command provided.']);
        }

        // Strip "php artisan " prefix for artisan matching
        $artisanCmd = preg_replace('/^php\s+artisan\s+/', '', $cmd);
        $commands = config('artisan-bar.commands', []);

        // Check artisan commands
        if (array_key_exists($artisanCmd, $commands)) {
            return $this->handleArtisan($artisanCmd, $commands[$artisanCmd], $confirmed, $auth, $request);
        }

        // Check shell aliases
        $shellMode = config('artisan-bar.shell_mode', 'disabled');

        if ($shellMode === 'aliases') {
            $aliases = config('artisan-bar.shell_aliases', []);

            if (array_key_exists($cmd, $aliases)) {
                return $this->handleShellAlias($cmd, $aliases[$cmd], $auth, $request);
            }
        }

        // Check raw shell
        if ($shellMode === 'raw') {
            return $this->handleRawShell($cmd, $confirmed, $auth, $request);
        }

        return response()->json(['ok' => false, 'output' => "Command not allowed: {$cmd}"]);
    }

    protected function handleArtisan(string $cmd, array $config, bool $confirmed, $auth, Request $request): JsonResponse
    {
        $isDangerous = $config['dangerous'] ?? false;

        if ($isDangerous && ! $confirmed) {
            return response()->json([
                'ok' => false,
                'confirm' => true,
                'output' => "Dangerous command: {$cmd}. Type Y to confirm.",
            ]);
        }

        // Build args
        $args = $config['args'] ?? $config;
        unset($args['dangerous']);

        $baseCmd = explode(' ', $cmd)[0];

        return $this->executeArtisan($baseCmd, $args, $cmd, $auth, $request);
    }

    protected function handleShellAlias(string $alias, string $command, $auth, Request $request): JsonResponse
    {
        return $this->executeProcess($command, true, "{$alias} => {$command}", $auth, $request);
    }

    protected function handleRawShell(string $cmd, bool $confirmed, $auth, Request $request): JsonResponse
    {
        if (! $confirmed) {
            return response()->json([
                'ok' => false,
                'confirm' => true,
                'output' => "Raw shell command. Type Y to confirm.",
            ]);
        }

        return $this->executeProcess($cmd, true, $cmd, $auth, $request);
    }

    /**
     * Execute an artisan command via Artisan::call() (in-process).
     */
    protected function executeArtisan(string $baseCmd, array $args, string $logCommand, $auth, Request $request): JsonResponse
    {
        $maxOutput = config('artisan-bar.max_output_length', 65536);
        $startTime = microtime(true);

        try {
            $exitCode = Artisan::call($baseCmd, $args);
            $output = trim(Artisan::output());
        } catch (\Throwable $e) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->auditLog("command:{$logCommand}", $request, $auth, false, 1, $durationMs);

            return response()->json(['ok' => false, 'output' => $e->getMessage()]);
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $ok = $exitCode === 0;

        if (strlen($output) > $maxOutput) {
            $output = substr($output, 0, $maxOutput) . "\n\n[Output truncated at {$maxOutput} bytes]";
        }

        $this->auditLog("command:{$logCommand}", $request, $auth, $ok, $exitCode, $durationMs, $output);

        return response()->json([
            'ok' => $ok,
            'output' => $output ?: 'Done.',
        ]);
    }

    /**
     * Execute a command via Symfony Process.
     *
     * @param  array|string  $command  Array for Process, string for fromShellCommandline
     * @param  bool  $isShell  Whether to use fromShellCommandline
     */
    protected function executeProcess(array|string $command, bool $isShell, string $logCommand, $auth, Request $request): JsonResponse
    {
        $timeout = config('artisan-bar.command_timeout', 30);
        $maxOutput = config('artisan-bar.max_output_length', 65536);

        $process = $isShell
            ? Process::fromShellCommandline($command, base_path())
            : new Process($command, base_path());

        if ($timeout > 0) {
            $process->setTimeout((float) $timeout);
        }

        $startTime = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $this->auditLog("command:{$logCommand}", $request, $auth, false, 124, $durationMs);

            return response()->json([
                'ok' => false,
                'output' => "Command timed out after {$timeout}s.",
            ]);
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);
        $output = $process->getOutput() . $process->getErrorOutput();
        $output = trim($output);
        $exitCode = $process->getExitCode();
        $ok = $exitCode === 0;

        // Truncate output
        if (strlen($output) > $maxOutput) {
            $output = substr($output, 0, $maxOutput) . "\n\n[Output truncated at {$maxOutput} bytes]";
        }

        $this->auditLog("command:{$logCommand}", $request, $auth, $ok, $exitCode, $durationMs, $output);

        return response()->json([
            'ok' => $ok,
            'output' => $output ?: 'Done.',
        ]);
    }

    protected function auditLog(string $action, Request $request, $auth = null, ?bool $ok = null, ?int $exitCode = null, ?int $durationMs = null, ?string $output = null): void
    {
        if (! config('artisan-bar.audit.enabled', true)) {
            return;
        }

        $channel = config('artisan-bar.audit.channel', 'stack');

        $data = [
            'action' => $action,
            'auth_mode' => config('artisan-bar.auth_mode', 'password'),
            'ip' => $request->ip(),
        ];

        if ($auth) {
            $data['auth_method'] = $auth->authMethod;
            $data['actor'] = $auth->actor;
        }

        if ($ok !== null) {
            $data['ok'] = $ok;
        }

        if ($exitCode !== null) {
            $data['exit_code'] = $exitCode;
        }

        if ($durationMs !== null) {
            $data['duration_ms'] = $durationMs;
        }

        $logOutput = config('artisan-bar.audit.log_output', false);

        if ($logOutput && $output) {
            $maxChars = is_int($logOutput) ? $logOutput : 500;
            $data['output'] = substr($output, 0, $maxChars);
        }

        Log::channel($channel)->info('[ArtisanBar] ' . $action, $data);
    }
}
