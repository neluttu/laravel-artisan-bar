<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class DangerousCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Add a safe command marked as dangerous for testing
        config()->set('artisan-bar.commands.about', ['dangerous' => true]);
    }

    public function test_dangerous_command_without_confirmation_returns_confirm(): void
    {
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'about']);

        $response->assertStatus(200)->assertJson(['confirm' => true]);
    }

    public function test_dangerous_command_with_confirmation_executes(): void
    {
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'about', 'confirmed' => true]);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }

    public function test_non_dangerous_command_executes_without_confirmation(): void
    {
        config()->set('artisan-bar.commands.cache:clear', []);
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'cache:clear'])
            ->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonMissing(['confirm' => true]);
    }
}
