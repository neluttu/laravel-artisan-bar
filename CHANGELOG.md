# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-04-03

### Added
- Initial release
- Debugbar-style command bar fixed at the bottom of the page
- Three auth modes: `password`, `app-auth`, `either`
- Whitelisted artisan commands with safe defaults
- Dangerous command confirmation prompts
- Three shell modes: `disabled`, `aliases`, `raw`
- Structured audit logging (auth_mode, auth_method, actor, ip, command, ok, exit_code, duration_ms)
- Self-contained CSS/JS (no Tailwind/Alpine dependency)
- Autocomplete suggestions
- Command history (up/down arrows)
- Resizable output panel
- Rate-limited login endpoint
- Environment-aware (auto-enabled in local/testing only)
- `@artisanBar` Blade directive
- Symfony Process for uniform timeout and kill
- Configurable `php_binary`, `command_timeout`, `max_output_length`
