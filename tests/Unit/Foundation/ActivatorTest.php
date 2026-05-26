<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Activator;
use BotCat\Foundation\Schema;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers plugin-foundation Requirement: Plugin entrypoint and metadata
 * (PHP / WP version guards) and Requirement: Activation, deactivation,
 * and uninstall lifecycle (activation side).
 */
final class ActivatorTest extends TestCase
{
    public function testActivationAbortsWhenPhpIsTooOld(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->never())->method('install');

        Functions\expect('deactivate_plugins')->once();
        Functions\expect('plugin_basename')->andReturn('bot-cat/bot-cat.php');
        Functions\expect('wp_die')->once()
            ->with($this->stringContains('PHP'));
        Functions\stubs(['esc_html' => static fn($s) => $s]);

        $activator = new Activator($schema, fn() => null);
        $activator->run('8.0.99', '7.0');
    }

    public function testActivationAbortsWhenWordPressIsTooOld(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->never())->method('install');

        Functions\expect('deactivate_plugins')->once();
        Functions\expect('plugin_basename')->andReturn('bot-cat/bot-cat.php');
        Functions\expect('wp_die')->once()
            ->with($this->stringContains('WordPress'));
        Functions\stubs(['esc_html' => static fn($s) => $s]);

        $activator = new Activator($schema, fn() => null);
        $activator->run('8.1.0', '6.9');
    }

    public function testActivationInstallsSchemaAndSchedulesCronWhenSupported(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->expects($this->once())->method('install');

        Functions\expect('wp_next_scheduled')->once()
            ->with('botcat_cleanup_old_logs')
            ->andReturn(false);
        Functions\expect('wp_schedule_event')->once()
            ->with($this->isType('int'), 'daily', 'botcat_cleanup_old_logs');

        $activator = new Activator($schema, fn() => null);
        $activator->run('8.2.0', '7.1');
    }

    public function testCronIsNotRescheduledWhenAlreadyPresent(): void
    {
        $schema = $this->createMock(Schema::class);
        $schema->method('install');

        Functions\expect('wp_next_scheduled')->once()
            ->with('botcat_cleanup_old_logs')
            ->andReturn(1_750_000_000);
        Functions\expect('wp_schedule_event')->never();

        $activator = new Activator($schema, fn() => null);
        $activator->run('8.2.0', '7.1');

        $this->assertTrue(true, 'expectations on wp_schedule_event are the real assertion');
    }
}
