<?php

use Illuminate\Support\Facades\Route;
use Neluttu\ArtisanBar\Http\Controllers\ArtisanBarController;
use Neluttu\ArtisanBar\Http\Middleware\ArtisanBarEnabled;

$prefix = config('artisan-bar.route_prefix', 'artisan-bar');

Route::prefix($prefix)
    ->middleware(['web', ArtisanBarEnabled::class])
    ->group(function () {
        Route::post('/login', [ArtisanBarController::class, 'login'])
            ->middleware('throttle:artisan-bar-login')
            ->name('artisan-bar.login');

        Route::post('/logout', [ArtisanBarController::class, 'logout'])
            ->name('artisan-bar.logout');

        Route::post('/run', [ArtisanBarController::class, 'run'])
            ->name('artisan-bar.run');
    });
