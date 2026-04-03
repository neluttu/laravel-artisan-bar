<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ArtisanBarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/artisan-bar.php', 'artisan-bar');
    }

    public function boot(): void
    {
        // Always load views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'artisan-bar');

        // Always load routes (compatible with route:cache)
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');

        // Rate limiter for login endpoint
        RateLimiter::for('artisan-bar-login', function (Request $request) {
            $maxAttempts = config('artisan-bar.login_attempts', 5);

            return Limit::perMinute($maxAttempts)->by($request->ip());
        });

        // Blade directive - checks isEnabled() and hides bar for unauthorized users in app-auth mode
        Blade::directive('artisanBar', function () {
            return "<?php if (\Neluttu\ArtisanBar\ArtisanBarAuth::shouldRender(request())) { echo view('artisan-bar::bar')->render(); } ?>";
        });

        // Publishables (always available)
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/artisan-bar.php' => config_path('artisan-bar.php'),
            ], 'artisan-bar-config');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/artisan-bar'),
            ], 'artisan-bar-views');

            $this->publishes([
                __DIR__ . '/../dist' => public_path('vendor/artisan-bar'),
            ], 'artisan-bar-assets');
        }
    }
}
