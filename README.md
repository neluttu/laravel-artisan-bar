# Laravel Artisan Bar

A safe, self-contained admin command bar for Laravel. Run artisan and shell commands directly from the browser.

Debugbar-style toolbar fixed at the bottom of the page. Password-protected or app-auth. Self-contained CSS/JS — no Tailwind or Alpine required.

## Requirements

- Laravel 12 (PHP 8.2-8.5) or Laravel 13 (PHP 8.3-8.5)

## Installation

```bash
# Local/staging only
composer require neluttu/laravel-artisan-bar --dev

# If you need it on production too
composer require neluttu/laravel-artisan-bar

# Publish assets (required)
php artisan vendor:publish --tag=artisan-bar-assets
```

## Quick Start

### Password mode (default, works on any project)

```bash
# Generate password hash
php -r "echo password_hash('your-password', PASSWORD_BCRYPT);"
```

Add to `.env`:
```env
ARTISAN_BAR_PASSWORD_HASH='$2y$12$your-generated-hash-here'
```

Add to your layout (before `</body>`):
```blade
@artisanBar
```

### App-auth mode (projects with users)

Add to `.env`:
```env
ARTISAN_BAR_AUTH_MODE=app-auth
```

Define gate in `AppServiceProvider`:
```php
Gate::define('access-artisan-bar', fn ($user) => $user->isAdmin());
```

### Either mode (both methods)

```env
ARTISAN_BAR_AUTH_MODE=either
ARTISAN_BAR_PASSWORD_HASH='$2y$12$...'
```

Plus the gate definition above. Users can authenticate via password OR app login.

## Configuration

```bash
php artisan vendor:publish --tag=artisan-bar-config
```

### Auth Modes

| Mode | Description | Needs Password Hash | Needs Gate |
|------|-------------|--------------------:|----------:|
| `password` | Standalone, any project | Yes | No |
| `app-auth` | Uses Laravel auth + gate | No | Yes |
| `either` | Accepts both methods | Optional | Optional |

### Environment Behavior

| `ARTISAN_BAR_ENABLED` | Environment | Result |
|----------------------:|-------------|--------|
| `null` (default) | `local` / `testing` | Enabled |
| `null` (default) | `production` / other | Disabled |
| `true` | Any | Enabled |
| `false` | Any | Disabled |

### Commands

Safe commands are enabled by default. Dangerous commands are commented out in config — uncomment what you need:

```php
// config/artisan-bar.php
'commands' => [
    'about' => [],           // safe, enabled
    'cache:clear' => [],     // safe, enabled
    // ...

    // Uncomment at your own risk:
    // 'migrate' => ['args' => ['--force' => true], 'dangerous' => true],
],
```

Commands marked `dangerous: true` require confirmation in the UI before executing.

### Shell Modes

| Mode | Description |
|------|-------------|
| `disabled` | No shell access (default, safe) |
| `aliases` | Only predefined aliases from config |
| `raw` | Full shell access (every command requires confirmation) |

```php
'shell_mode' => 'aliases',
'shell_aliases' => [
    'logs' => 'tail -n 200 storage/logs/laravel.log',
    'disk' => 'df -h',
    'fix-perms' => 'chmod -R ug+rw storage bootstrap/cache',
],
```

### Audit Logging

Every command is logged with: auth_mode, auth_method, actor, ip, command, ok, exit_code, duration_ms.

```php
'audit' => [
    'enabled' => true,
    'channel' => 'stack',
    'log_output' => false,  // Set to int (e.g. 500) to log truncated output
],
```

## Features

- Minimize/maximize toggle (bottom-left tab)
- Autocomplete suggestions as you type
- Command history (up/down arrows)
- Resizable output panel
- Tab to complete first suggestion
- `/help` — shows all available commands
- `/clear` — clears output
- `/logout` — ends session
- Color-coded output (green=success, red=error, yellow=confirmation)
- Rate-limited login (configurable)
- Structured audit logging

## Limitations

- **Not a real terminal** — this is a command runner, not a shell emulator
- **No interactive commands** — `top`, `vim`, `mysql`, `tail -f` etc. won't work
- **No streaming output** — output returned after command completes
- **Command timeout** — default 30 seconds, configurable

## Security

See [SECURITY.md](SECURITY.md) for full details.

- Whitelist-only execution
- Bcrypt password hashing
- Rate-limited login
- Dangerous commands require confirmation
- Raw shell requires confirmation on every command
- Audit logging
- No arbitrary code execution (no tinker in v1)
- Self-contained UI (no external dependencies)

## License

MIT License. See [LICENSE](LICENSE).
