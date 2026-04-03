<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class ThrottleTest extends TestCase
{
    public function test_sixth_login_attempt_returns_429(): void
    {
        config()->set('artisan-bar.login_attempts', 5);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('artisan-bar.login'), ['password' => 'wrong']);
        }

        $this->postJson(route('artisan-bar.login'), ['password' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_429_includes_retry_after_header(): void
    {
        config()->set('artisan-bar.login_attempts', 1);

        $this->postJson(route('artisan-bar.login'), ['password' => 'wrong']);

        $response = $this->postJson(route('artisan-bar.login'), ['password' => 'wrong']);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }
}
