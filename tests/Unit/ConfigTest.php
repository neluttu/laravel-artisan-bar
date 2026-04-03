<?php

declare(strict_types=1);

namespace Neluttu\ArtisanBar\Tests\Unit;

use Neluttu\ArtisanBar\Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_config_is_loaded(): void
    {
        $this->assertNotNull(config('artisan-bar'));
        $this->assertIsArray(config('artisan-bar.commands'));
    }

    public function test_default_commands_include_safe_commands(): void
    {
        $commands = config('artisan-bar.commands');

        $this->assertArrayHasKey('about', $commands);
        $this->assertArrayHasKey('cache:clear', $commands);
        $this->assertArrayHasKey('optimize:clear', $commands);
        $this->assertArrayHasKey('route:list', $commands);
        $this->assertArrayHasKey('migrate:status', $commands);
    }

    public function test_default_shell_mode_is_disabled(): void
    {
        $this->assertEquals('disabled', config('artisan-bar.shell_mode'));
    }

    public function test_default_auth_mode_is_password(): void
    {
        $this->assertEquals('password', config('artisan-bar.auth_mode'));
    }

    public function test_default_timeout_is_30(): void
    {
        $this->assertEquals(30, config('artisan-bar.command_timeout'));
    }

    public function test_audit_enabled_by_default(): void
    {
        $this->assertTrue(config('artisan-bar.audit.enabled'));
    }
}
