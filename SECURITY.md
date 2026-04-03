# Security Policy

## Reporting Vulnerabilities

If you discover a security vulnerability, please email **neluttu@proton.me** instead of opening a public issue.

We will acknowledge your report within 48 hours and aim to release a fix within 7 days for critical issues.

## Security Design

### Authentication
- Password stored as **bcrypt hash** in `.env`, verified with `Hash::check()`
- Masked password input, never stored in command history or output
- Rate-limited login endpoint (configurable, default: 5 attempts/minute)
- Encrypted session cookie for password auth state

### Authorization
- Three auth modes: `password`, `app-auth` (Gate ability), `either`
- Centralized auth via `ArtisanBarAuth` helper

### Command Execution
- **Whitelist-only**: no command runs unless explicitly listed in config
- **Dangerous commands require confirmation** in the UI before executing
- **Raw shell requires confirmation** on every command, no exceptions
- **No tinker / arbitrary PHP execution** in v1
- Shell disabled by default, opt-in via config
- All shell commands run via Symfony Process with timeout

### Audit
- Every command execution logged with: auth_mode, auth_method, actor, ip, command, ok, exit_code, duration_ms
- Login attempts logged (success and failure)

## Recommendations

- Install as `--dev` for local-only use
- If used on production, set `ARTISAN_BAR_ENABLED=true` explicitly and use a **strong password**
- Review and customize the command whitelist for your environment
- Keep `shell_mode` as `disabled` unless you specifically need it
- Enable audit logging in production
