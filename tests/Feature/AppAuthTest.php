<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;
use Neluttu\ArtisanBar\Tests\TestCase;

class AppAuthTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('artisan-bar.auth_mode', 'app-auth');
        $app['config']->set('artisan-bar.password_hash', null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('access-artisan-bar', fn ($user) => $user->getAuthIdentifier() === 1);
    }

    public function test_unauthenticated_user_returns_401(): void
    {
        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }

    public function test_authenticated_user_without_ability_returns_403(): void
    {
        $user = new class extends Authenticatable
        {
            public function getAuthIdentifier(): int { return 99; }
        };

        $this->actingAs($user)
            ->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(403);
    }

    public function test_authenticated_user_with_ability_can_run(): void
    {
        $user = new class extends Authenticatable
        {
            public function getAuthIdentifier(): int { return 1; }
        };

        $this->actingAs($user)
            ->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    public function test_login_endpoint_returns_error_in_app_auth_mode(): void
    {
        $this->postJson(route('artisan-bar.login'), ['password' => 'test'])
            ->assertStatus(400);
    }
}
