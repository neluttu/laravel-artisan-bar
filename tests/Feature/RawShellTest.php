<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class RawShellTest extends TestCase
{
    public function test_raw_command_without_confirmation_returns_confirm(): void
    {
        config()->set('artisan-bar.shell_mode', 'raw');
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'echo hello']);

        $response->assertStatus(200)->assertJson(['confirm' => true]);
    }

    public function test_raw_command_with_confirmation_executes(): void
    {
        config()->set('artisan-bar.shell_mode', 'raw');
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), [
            'cmd' => 'echo hello',
            'confirmed' => true,
        ]);

        $response->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertStringContains('hello', $response->json('output'));
    }

    public function test_raw_blocked_when_mode_is_not_raw(): void
    {
        config()->set('artisan-bar.shell_mode', 'aliases');
        config()->set('artisan-bar.shell_aliases', []);
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), [
            'cmd' => 'echo hello',
            'confirmed' => true,
        ])
            ->assertStatus(200)
            ->assertJson(['ok' => false]);
    }

    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '{$haystack}' contains '{$needle}'."
        );
    }
}
