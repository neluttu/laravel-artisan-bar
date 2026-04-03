<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Illuminate\Support\Facades\Log;
use Neluttu\ArtisanBar\Tests\TestCase;

class AuditLogTest extends TestCase
{
    public function test_command_execution_is_logged(): void
    {
        Log::shouldReceive('channel')
            ->with('stack')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'command:about')
                    && isset($context['auth_mode'])
                    && isset($context['ip'])
                    && isset($context['ok'])
                    && isset($context['duration_ms']);
            });

        $this->authenticateSession();
        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about']);
    }

    public function test_login_attempt_is_logged(): void
    {
        Log::shouldReceive('channel')
            ->with('stack')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'login:'));

        $this->postJson(route('artisan-bar.login'), ['password' => 'wrong-password']);
    }

    public function test_audit_disabled_does_not_log(): void
    {
        config()->set('artisan-bar.audit.enabled', false);

        Log::shouldReceive('channel')->never();
        Log::shouldReceive('info')->never();

        $this->authenticateSession();
        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about']);
    }
}
