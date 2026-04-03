<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class ArtisanBarAuth
{
    /**
     * Determine if the bar should be rendered on the page.
     * For password mode: always render (UI shows login form).
     * For app-auth mode: only render if user is authorized.
     * For either mode: render if user is authorized OR password auth is available.
     */
    public static function shouldRender(Request $request): bool
    {
        if (! static::isEnabled()) {
            return false;
        }

        $mode = config('artisan-bar.auth_mode', 'password');

        // Password mode: always show (UI handles login/command state)
        if ($mode === 'password') {
            return true;
        }

        // App-auth mode: only show if user is authorized
        if ($mode === 'app-auth') {
            $auth = static::authorize($request);

            return $auth->allowed;
        }

        // Either mode: show if authorized OR password login is available
        $auth = static::authorize($request);

        if ($auth->allowed) {
            return true;
        }

        return static::hasPasswordAuth();
    }

    /**
     * Check if the bar is enabled based on environment and config.
     */
    public static function isEnabled(): bool
    {
        $enabled = config('artisan-bar.enabled');

        // Explicit true/false
        if ($enabled === true) {
            return static::hasAuthConfigured();
        }

        if ($enabled === false) {
            return false;
        }

        // null = auto: only in local/testing
        if (! in_array(app()->environment(), ['local', 'testing'])) {
            return false;
        }

        return static::hasAuthConfigured();
    }

    /**
     * Check if at least one auth method is configured.
     */
    protected static function hasAuthConfigured(): bool
    {
        $mode = config('artisan-bar.auth_mode', 'password');

        return match ($mode) {
            'password' => ! empty(config('artisan-bar.password_hash')),
            'app-auth' => Gate::has(config('artisan-bar.auth_ability', 'access-artisan-bar')),
            'either' => ! empty(config('artisan-bar.password_hash'))
                || Gate::has(config('artisan-bar.auth_ability', 'access-artisan-bar')),
            default => false,
        };
    }

    /**
     * Authorize the current request. Returns AuthResult with status 200, 401, or 403.
     */
    public static function authorize(Request $request): AuthResult
    {
        $mode = config('artisan-bar.auth_mode', 'password');

        return match ($mode) {
            'password' => static::checkPassword($request),
            'app-auth' => static::checkAppAuth($request),
            'either' => static::checkEither($request),
            default => AuthResult::unauthorized('guest:' . $request->ip()),
        };
    }

    /**
     * Verify a password against the stored hash.
     */
    public static function verifyPassword(string $password): bool
    {
        $hash = config('artisan-bar.password_hash');

        if (empty($hash)) {
            return false;
        }

        return Hash::check($password, $hash);
    }

    /**
     * Check if the request has a valid password session.
     */
    public static function hasValidSession(Request $request): bool
    {
        $session = $request->session();

        if (! $session->has('artisan_bar_authenticated')) {
            return false;
        }

        $authenticatedAt = $session->get('artisan_bar_authenticated_at', 0);
        $lifetime = config('artisan-bar.session_lifetime', 120) * 60; // minutes to seconds

        return (time() - $authenticatedAt) < $lifetime;
    }

    /**
     * Set the password session as authenticated.
     */
    public static function setSession(Request $request): void
    {
        $session = $request->session();
        $session->put('artisan_bar_authenticated', true);
        $session->put('artisan_bar_authenticated_at', time());
    }

    /**
     * Clear the password session.
     */
    public static function clearSession(Request $request): void
    {
        $session = $request->session();
        $session->forget('artisan_bar_authenticated');
        $session->forget('artisan_bar_authenticated_at');
    }

    /**
     * Check if password auth mode allows login endpoint.
     */
    public static function supportsPasswordLogin(): bool
    {
        return in_array(config('artisan-bar.auth_mode', 'password'), ['password', 'either']);
    }

    /**
     * Check if password auth is available (hash configured).
     */
    public static function hasPasswordAuth(): bool
    {
        return ! empty(config('artisan-bar.password_hash'));
    }

    protected static function checkPassword(Request $request): AuthResult
    {
        $actor = 'guest:' . $request->ip();

        if (static::hasValidSession($request)) {
            return AuthResult::allowed('password', $actor);
        }

        return AuthResult::unauthorized($actor);
    }

    protected static function checkAppAuth(Request $request): AuthResult
    {
        $user = $request->user();

        if (! $user) {
            return AuthResult::unauthorized('guest:' . $request->ip());
        }

        $actor = 'user:' . $user->getAuthIdentifier();
        $ability = config('artisan-bar.auth_ability', 'access-artisan-bar');

        if (Gate::forUser($user)->allows($ability)) {
            return AuthResult::allowed('app-auth', $actor);
        }

        return AuthResult::forbidden('app-auth', $actor);
    }

    protected static function checkEither(Request $request): AuthResult
    {
        // Check password session first
        if (static::hasPasswordAuth() && static::hasValidSession($request)) {
            return AuthResult::allowed('password', 'guest:' . $request->ip());
        }

        // Then check app-auth
        $user = $request->user();

        if ($user) {
            $actor = 'user:' . $user->getAuthIdentifier();
            $ability = config('artisan-bar.auth_ability', 'access-artisan-bar');

            if (Gate::forUser($user)->allows($ability)) {
                return AuthResult::allowed('app-auth', $actor);
            }
        }

        return AuthResult::unauthorized('guest:' . $request->ip());
    }
}
