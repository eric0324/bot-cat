<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Uninstaller;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Scenario: Uninstall removes all plugin data.
 */
final class UninstallerTest extends TestCase
{
    public function testUninstallDropsTablesAndDeletesOptionsAndClearsCron(): void
    {
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public array $queries = [];
            public function query(string $sql): int
            {
                $this->queries[] = $sql;
                return 0;
            }
        };

        Functions\expect('delete_option')->atLeast()->once()
            ->with($this->stringStartsWith('botcat_'));
        Functions\expect('wp_clear_scheduled_hook')->once()
            ->with('botcat_cleanup_old_logs');

        Uninstaller::uninstall();

        $joined = implode("\n", $wpdb->queries);
        $this->assertStringContainsString('DROP TABLE', $joined);
        $this->assertStringContainsString('wp_botcat_subscribers', $joined);
        $this->assertStringContainsString('wp_botcat_push_jobs', $joined);
        $this->assertStringContainsString('wp_botcat_push_logs', $joined);
    }
}
