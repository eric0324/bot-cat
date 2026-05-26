<?php
/**
 * @package BotCat\Tests\Unit\Shortener
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Shortener;

use BotCat\Shortener\ClickLogRepository;
use BotCat\Tests\TestCase;

final class ClickLogRepositoryTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public array $queries = [];
            public string $next_var = '0';
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_var(string $sql): ?string
            {
                $this->queries[] = ['get_var', $sql, []];
                return $this->next_var;
            }
            public function insert(string $table, array $data, array $formats): int
            {
                $this->queries[] = ['insert', $table, $data];
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testLogInsertsRowAndTruncatesUaAndReferer(): void
    {
        $long_ua  = str_repeat('a', 400);
        $long_ref = str_repeat('b', 400);

        (new ClickLogRepository())->log(7, 101, str_repeat('h', 64), $long_ua, $long_ref);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert' && $q[1] === 'wp_botcat_short_link_clicks') {
                $found = true;
                $this->assertSame(7, $q[2]['short_link_id']);
                $this->assertSame(101, $q[2]['subscriber_id']);
                $this->assertSame(64, strlen($q[2]['ip_hash']));
                $this->assertLessThanOrEqual(255, strlen($q[2]['user_agent']));
                $this->assertLessThanOrEqual(255, strlen($q[2]['referer']));
            }
        }
        $this->assertTrue($found);
    }

    public function testLogAcceptsNullSubscriberId(): void
    {
        (new ClickLogRepository())->log(7, null, str_repeat('h', 64), 'curl/8.0', null);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert') {
                $found = true;
                $this->assertNull($q[2]['subscriber_id']);
            }
        }
        $this->assertTrue($found);
    }

    public function testCountTotalAndUnique(): void
    {
        $this->wpdb->next_var = '125';
        $this->assertSame(125, (new ClickLogRepository())->count_total(7));

        $this->wpdb->next_var = '37';
        $this->assertSame(37, (new ClickLogRepository())->count_unique(7));
    }
}
