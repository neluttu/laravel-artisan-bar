<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests;

use Neluttu\ArtisanBar\ArtisanBarServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ArtisanBarServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
        $app['config']->set('app.env', 'testing');
        $app['config']->set('artisan-bar.enabled', null);
        $app['config']->set('artisan-bar.password_hash', bcrypt('test-password'));
        $app['config']->set('artisan-bar.auth_mode', 'password');
    }

    protected function authenticateSession(): void
    {
        $this->withSession([
            'artisan_bar_authenticated' => true,
            'artisan_bar_authenticated_at' => time(),
        ]);
    }
}
