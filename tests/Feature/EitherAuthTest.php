<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Gate;
use Neluttu\ArtisanBar\Tests\TestCase;

class EitherAuthTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('artisan-bar.auth_mode', 'either');
    }

    protected function setUp(): void
    {
        parent::setUp();
        Gate::define('access-artisan-bar', fn ($user) => $user->getAuthIdentifier() === 1);
    }

    public function test_guest_with_password_session_can_run(): void
    {
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
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

    public function test_guest_without_password_returns_401(): void
    {
        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }

    public function test_authenticated_user_without_ability_and_no_password_returns_401(): void
    {
        $user = new class extends Authenticatable
        {
            public function getAuthIdentifier(): int { return 99; }
        };

        $this->actingAs($user)
            ->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }
}
