<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class ArtisanCommandTest extends TestCase
{
    public function test_whitelisted_command_returns_output(): void
    {
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'about']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertNotEmpty($response->json('output'));
    }

    public function test_non_whitelisted_command_is_blocked(): void
    {
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'migrate:fresh'])
            ->assertStatus(200)
            ->assertJson(['ok' => false]);
    }

    public function test_command_with_php_artisan_prefix_works(): void
    {
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'php artisan about']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_custom_command_added_via_config(): void
    {
        config()->set('artisan-bar.commands.env', []);
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'env']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_empty_command_returns_non_success(): void
    {
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => '']);

        // Should not return ok=true
        $this->assertNotEquals(true, $response->json('ok'));
    }
}
