<?php

return [
    // ── General ──────────────────────────────────────────────

    // Enable bar. null = auto (local/testing only)
    'enabled' => env('ARTISAN_BAR_ENABLED', null),

    // Route prefix
    'route_prefix' => 'artisan-bar',

    // Command execution limits
    'command_timeout' => 30,         // seconds, 0 = no limit (not recommended)
    'max_output_length' => 65536,    // bytes (64KB), output truncated beyond this

    // PHP binary path (used for artisan subprocess execution)
    // Default: PHP_BINARY (the exact binary running the current request)
    'php_binary' => env('ARTISAN_BAR_PHP_BINARY', PHP_BINARY),

    // Prompt display
    'prompt' => [
        'show_hostname' => true,
    ],

    // ── Authentication ───────────────────────────────────────

    // Auth mode: 'password', 'app-auth', or 'either'
    // - password: standalone, works on any project (default)
    // - app-auth: uses Laravel auth + gate ability
    // - either: accepts password session OR app-auth ability
    'auth_mode' => env('ARTISAN_BAR_AUTH_MODE', 'password'),

    // Password auth settings
    // Generate: php -r "echo password_hash('your-password', PASSWORD_BCRYPT);"
    // Or: php artisan tinker --execute "echo bcrypt('your-password');"
    'password_hash' => env('ARTISAN_BAR_PASSWORD_HASH'),
    'session_lifetime' => 120, // minutes

    // App-auth settings (used when auth_mode is 'app-auth' or 'either')
    'auth_ability' => 'access-artisan-bar', // Gate ability name

    // Login rate limiting (attempts per minute, for password auth)
    'login_attempts' => 5,

    // ── Audit Logging ────────────────────────────────────────

    'audit' => [
        'enabled' => true,
        'channel' => env('ARTISAN_BAR_LOG_CHANNEL', 'stack'),
        // Log truncated output (first N chars). Set to 0 or false to disable.
        'log_output' => false,
    ],

    // ── Artisan Commands ─────────────────────────────────────
    // Active commands can be executed from the bar.
    // 'dangerous' => true triggers a confirmation prompt in the UI.
    // Uncomment dangerous commands only if you need them.
    'commands' => [

        // Cache & Optimization
        'about' => [],
        'cache:clear' => [],
        'config:clear' => [],
        'config:cache' => [],
        'view:clear' => [],
        'view:cache' => [],
        'event:list' => [],
        'optimize' => [],
        'optimize:clear' => [],
        'clear-compiled' => [],
        'package:discover' => [],

        // Routing
        'route:list' => [],
        'route:clear' => [],
        'route:cache' => [],
        'channel:list' => [],

        // Queue (read-only)
        'queue:failed' => [],
        'queue:restart' => [],
        'schedule:list' => [],

        // Database (read-only)
        'migrate:status' => [],
        'db:show' => [],

        // ── Dangerous commands (uncomment at your own risk) ──
        // 'migrate' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'migrate:install' => ['dangerous' => true],
        // 'migrate:rollback' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'migrate:fresh' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'migrate:fresh --seed' => ['args' => ['--force' => true, '--seed' => true], 'dangerous' => true],
        // 'migrate:refresh' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'migrate:reset' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'db:seed' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'db:wipe' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'schema:dump' => ['dangerous' => true],
        // 'route:list --except-vendor' => ['args' => ['--except-vendor' => true]],
        // 'route:list --only-vendor' => ['args' => ['--only-vendor' => true]],
        // 'queue:clear' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'queue:flush' => ['dangerous' => true],
        // 'queue:prune-batches' => [],
        // 'queue:prune-failed' => [],
        // 'queue:work --once' => ['args' => ['--once' => true]],
        // 'schedule:run' => ['dangerous' => true],
        // 'schedule:clear-cache' => [],
        // 'schedule:interrupt' => ['dangerous' => true],
        // 'auth:clear-resets' => [],
        // 'down' => ['dangerous' => true],
        // 'up' => ['dangerous' => true],
        // 'key:generate' => ['args' => ['--force' => true], 'dangerous' => true],
        // 'model:prune' => ['dangerous' => true],
        // 'storage:link' => [],
        // 'storage:unlink' => ['dangerous' => true],
        // 'lang:publish' => [],
        // 'stub:publish' => [],
        // 'config:publish' => [],
    ],

    // ── Shell ────────────────────────────────────────────────
    // Runs via Symfony Process. Requires proc_open() to be enabled on the server.

    // Shell mode: 'disabled', 'aliases', or 'raw'
    // - disabled: no shell access (default, safe)
    // - aliases: only predefined command aliases from config
    // - raw: full shell access (HIGH RISK - every command requires confirmation)
    'shell_mode' => 'disabled',

    // Shell aliases (used when shell_mode is 'aliases')
    // Friendly name => actual shell command
    // All aliases run in the project root directory
    'shell_aliases' => [
        'php-version' => 'php -v',
        'php-modules' => 'php -m',
        'composer-version' => 'composer --version',
        'node-version' => 'node -v',
        'npm-version' => 'npm -v',
        'disk' => 'df -h',
        'memory' => 'free -m',
        'uptime' => 'uptime',
        'whoami' => 'whoami',
        'pwd' => 'pwd',
        'os' => 'cat /etc/os-release',
        'logs' => 'tail -n 200 storage/logs/laravel.log',
        'fix-perms' => 'chmod -R ug+rw storage bootstrap/cache',
    ],
];
