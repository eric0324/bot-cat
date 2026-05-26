<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\PushLogEntry;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tests\TestCase;

/**
 * Covers Scenario: Detail page shows last 20 pushes.
 */
final class RecentLogsTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();

        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            /** @var list<array{0:string,1:string,2:list<mixed>}> */
            public array $queries = [];
            /** @var list<array<string, mixed>> */
            public array $rows_to_return = [];
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $this->queries[] = ['get_results', $sql, []];
                return $this->rows_to_return;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testReturnsAtMostTwentyRowsByDefault(): void
    {
        $this->wpdb->rows_to_return = $this->fixture_rows(20);

        $logs = (new SubscriberRepository())->recent_logs_for_subscriber(42);

        $this->assertCount(20, $logs);
        $this->assertInstanceOf(PushLogEntry::class, $logs[0]);

        $limit_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'LIMIT')) {
                $this->assertContains(20, $q[2]);
                $this->assertContains(42, $q[2]);
                $limit_found = true;
            }
        }
        $this->assertTrue($limit_found, 'recent_logs should apply LIMIT and the subscriber id');
    }

    public function testCustomLimitIsRespected(): void
    {
        $this->wpdb->rows_to_return = $this->fixture_rows(5);

        (new SubscriberRepository())->recent_logs_for_subscriber(42, limit: 5);

        $limit_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'LIMIT')) {
                $this->assertContains(5, $q[2]);
                $limit_found = true;
            }
        }
        $this->assertTrue($limit_found);
    }

    public function testOrdersByMostRecentFirst(): void
    {
        $this->wpdb->rows_to_return = [];

        (new SubscriberRepository())->recent_logs_for_subscriber(42);

        $order_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && preg_match('/ORDER BY .*created_at DESC/i', $q[1])) {
                $order_found = true;
            }
        }
        $this->assertTrue($order_found, 'logs should be ordered created_at DESC');
    }

    public function testJoinsPushJobsForPostMetadata(): void
    {
        $this->wpdb->rows_to_return = [];

        (new SubscriberRepository())->recent_logs_for_subscriber(42);

        $join_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'wp_botcat_push_jobs')) {
                $join_found = true;
            }
        }
        $this->assertTrue($join_found, 'recent_logs must join wp_botcat_push_jobs for post info');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fixture_rows(int $count): array
    {
        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'id'            => (string) $i,
                'job_id'        => (string) (100 + $i),
                'subscriber_id' => '42',
                'line_user_id'  => 'U42',
                'status'        => 'sent',
                'error'         => null,
                'created_at'    => '2026-05-' . str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ' 00:00:00',
                'post_id'       => (string) (200 + $i),
            ];
        }
        return $rows;
    }
}
