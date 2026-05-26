<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\RetentionCron;
use BotCat\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Retention cron for push logs.
 */
final class RetentionCronTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public array $queries = [];
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return vsprintf(str_replace(['%d', '%s'], ['%d', "'%s'"], $query), $args);
            }
            public function query(string $sql): int
            {
                $this->queries[] = ['query', $sql];
                return 0;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testDefaultRetentionIsThirtyDays(): void
    {
        Filters\expectApplied('botcat_log_retention_days')
            ->once()
            ->with(30)
            ->andReturn(30);

        $cron = new RetentionCron();
        $cron->run();

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'wp_botcat_push_logs')) {
                $found = true;
                $this->assertSame(30, $q[2][0] ?? null, 'retention argument should be 30');
            }
        }
        $this->assertTrue($found, 'expected push_logs DELETE prepared statement');
    }

    public function testRetentionWindowIsFilterable(): void
    {
        Filters\expectApplied('botcat_log_retention_days')
            ->once()
            ->with(30)
            ->andReturn(7);

        $cron = new RetentionCron();
        $cron->run();

        $arg = null;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'wp_botcat_push_logs')) {
                $arg = $q[2][0] ?? null;
            }
        }
        $this->assertSame(7, $arg, 'filtered retention should reach prepare()');
    }
}
