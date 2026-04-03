<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Feature;

use Neluttu\ArtisanBar\Tests\TestCase;

/**
 * Note: In Testbench, artisan commands fall back to Artisan::call() because
 * the testbench Laravel skeleton lacks a working vendor/autoload.php at base_path().
 * The subprocess path (Symfony Process) is exercised in real Laravel apps.
 * These tests verify the fallback path and config values.
 */
class SubprocessTest extends TestCase
{
    public function test_php_binary_config_defaults_to_php_binary_constant(): void
    {
        $this->assertEquals(PHP_BINARY, config('artisan-bar.php_binary'));
    }

    public function test_command_timeout_config_is_set(): void
    {
        $this->assertEquals(30, config('artisan-bar.command_timeout'));
    }

    public function test_max_output_length_config_is_set(): void
    {
        $this->assertEquals(65536, config('artisan-bar.max_output_length'));
    }

    public function test_artisan_fallback_works_in_testbench(): void
    {
        $this->authenticateSession();

        // This uses Artisan::call() fallback since testbench has no real artisan binary
        $response = $this->postJson(route('artisan-bar.run'), ['cmd' => 'about']);

        $response->assertStatus(200)->assertJson(['ok' => true]);
    }
}
