<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

class ShellAliasTest extends TestCase
{
    public function test_shell_disabled_returns_error(): void
    {
        config()->set('artisan-bar.shell_mode', 'disabled');
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'whoami'])
            ->assertStatus(200)
            ->assertJson(['ok' => false]);
    }

    public function test_shell_alias_runs_mapped_command(): void
    {
        config()->set('artisan-bar.shell_mode', 'aliases');
        config()->set('artisan-bar.shell_aliases', ['who' => 'whoami']);
        $this->authenticateSession();

        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'who']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertNotEmpty($response->json('output'));
    }

    public function test_non_existent_alias_is_blocked(): void
    {
        config()->set('artisan-bar.shell_mode', 'aliases');
        config()->set('artisan-bar.shell_aliases', ['who' => 'whoami']);
        $this->authenticateSession();

        $this->postJson(route('artisan-bar.run'), ['cmd' => 'not-an-alias'])
            ->assertStatus(200)
            ->assertJson(['ok' => false]);
    }
}
