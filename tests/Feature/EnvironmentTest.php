<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\ArtisanBarAuth;
use Neluttu\ArtisanBar\Tests\TestCase;

class EnvironmentTest extends TestCase
{
    public function test_local_env_with_password_is_enabled(): void
    {
        config()->set('app.env', 'local');
        config()->set('artisan-bar.enabled', null);
        config()->set('artisan-bar.password_hash', bcrypt('test'));

        $this->assertTrue(ArtisanBarAuth::isEnabled());
    }

    public function test_production_env_without_explicit_enable_is_disabled(): void
    {
        app()->detectEnvironment(fn () => 'production');
        config()->set('artisan-bar.enabled', null);

        $this->assertFalse(ArtisanBarAuth::isEnabled());
    }

    public function test_production_env_with_explicit_enable_is_enabled(): void
    {
        config()->set('app.env', 'production');
        config()->set('artisan-bar.enabled', true);
        config()->set('artisan-bar.password_hash', bcrypt('test'));

        $this->assertTrue(ArtisanBarAuth::isEnabled());
    }

    public function test_no_password_hash_in_password_mode_is_disabled(): void
    {
        config()->set('artisan-bar.auth_mode', 'password');
        config()->set('artisan-bar.password_hash', null);

        $this->assertFalse(ArtisanBarAuth::isEnabled());
    }

    public function test_testing_env_with_password_is_enabled(): void
    {
        config()->set('app.env', 'testing');
        config()->set('artisan-bar.enabled', null);
        config()->set('artisan-bar.password_hash', bcrypt('test'));

        $this->assertTrue(ArtisanBarAuth::isEnabled());
    }

    public function test_explicitly_disabled_is_always_disabled(): void
    {
        config()->set('artisan-bar.enabled', false);
        config()->set('artisan-bar.password_hash', bcrypt('test'));

        $this->assertFalse(ArtisanBarAuth::isEnabled());
    }
}
