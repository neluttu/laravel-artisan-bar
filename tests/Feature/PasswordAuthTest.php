<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class PasswordAuthTest extends TestCase
{
    public function test_no_password_hash_returns_404(): void
    {
        config()->set('artisan-bar.password_hash', null);

        $this->post(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(404);
    }

    public function test_wrong_password_returns_401(): void
    {
        $this->post(route('artisan-bar.login'), ['password' => 'wrong'])
            ->assertStatus(401)
            ->assertJson(['ok' => false]);
    }

    public function test_correct_password_authenticates(): void
    {
        $response = $this->post(route('artisan-bar.login'), ['password' => 'test-password']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_authenticated_user_can_run_commands(): void
    {
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(200)
            ->assertJson(['ok' => true]);
    }

    public function test_unauthenticated_user_cannot_run_commands(): void
    {
        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }

    public function test_expired_session_returns_401(): void
    {
        $this->withSession([
            'artisan_bar_authenticated' => true,
            'artisan_bar_authenticated_at' => time() - 99999,
        ]);

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }

    public function test_logout_clears_session(): void
    {
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.logout'))
            ->assertStatus(200)
            ->assertJson(['ok' => true]);

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'about'])
            ->assertStatus(401);
    }
}
