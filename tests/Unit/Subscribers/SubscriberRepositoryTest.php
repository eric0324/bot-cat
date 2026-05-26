<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Subscribers\Subscriber;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Repository accessor for other capabilities.
 */
final class SubscriberRepositoryTest extends TestCase
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
            public function get_var(string $sql): ?string
            {
                return '0';
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $batch = array_shift($this->rows_to_return);
                return $batch ?: [];
            }
            public function get_row(string $sql, $output = OBJECT): ?array
            {
                $batch = array_shift($this->rows_to_return);
                return $batch[0] ?? null;
            }
            public function query(string $sql): int
            {
                $this->queries[] = ['query', $sql, []];
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testFindByLineIdReturnsSubscriberWhenRowExists(): void
    {
        $this->wpdb->rows_to_return = [[[
            'id'            => '42',
            'line_user_id'  => 'U123',
            'display_name'  => 'Eric',
            'picture_url'   => null,
            'status'        => 'active',
            'followed_at'   => '2026-05-01 00:00:00',
            'unfollowed_at' => null,
        ]]];

        $repo = new SubscriberRepository();
        $sub  = $repo->find_by_line_id('U123');

        $this->assertNotNull($sub);
        $this->assertSame(42, $sub->id);
        $this->assertSame('U123', $sub->line_user_id);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if (str_contains($q[1], 'wp_botcat_subscribers') && in_array('U123', $q[2], true)) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'query should filter by line_user_id = U123');
    }

    public function testFindByLineIdReturnsNullWhenAbsent(): void
    {
        $this->wpdb->rows_to_return = [[]];

        $repo = new SubscriberRepository();

        $this->assertNull($repo->find_by_line_id('Uabsent'));
    }

    public function testActiveIdsIteratesInChunksToBoundMemory(): void
    {
        // 2.5 chunks worth of data — repo should issue >= 3 LIMIT queries.
        $page_size = SubscriberRepository::CHUNK_SIZE;
        $rows1 = array_map(fn($i) => ['line_user_id' => 'U' . $i], range(1, $page_size));
        $rows2 = array_map(fn($i) => ['line_user_id' => 'U' . $i], range($page_size + 1, $page_size * 2));
        $rows3 = array_map(fn($i) => ['line_user_id' => 'U' . $i], range($page_size * 2 + 1, $page_size * 2 + 5));
        $this->wpdb->rows_to_return = [$rows1, $rows2, $rows3, []];

        $repo  = new SubscriberRepository();
        $ids   = iterator_to_array($repo->active_ids(), false);

        $this->assertCount($page_size * 2 + 5, $ids);
        $this->assertSame('U1', $ids[0]);

        $limit_queries = array_filter(
            $this->wpdb->queries,
            static fn(array $q): bool => str_contains($q[1], 'LIMIT') && str_contains($q[1], 'wp_botcat_subscribers')
        );
        $this->assertGreaterThanOrEqual(3, count($limit_queries), 'active_ids must page via multiple LIMIT queries');
    }

    public function testUpsertOnFollowCreatesOrReactivatesByLineId(): void
    {
        $captured_args = null;
        $this->wpdb->rows_to_return = [];

        $repo = new SubscriberRepository();
        $repo->upsert_active(
            line_user_id: 'U123',
            display_name: 'Eric',
            picture_url: null,
            followed_at: '2026-05-20 10:00:00'
        );

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] !== 'prepare') {
                continue;
            }
            if (str_contains($q[1], 'INSERT') && str_contains($q[1], 'ON DUPLICATE KEY UPDATE')) {
                $found = true;
                $this->assertContains('U123', $q[2]);
                $this->assertContains('active', $q[2]);
            }
        }
        $this->assertTrue($found, 'upsert should use INSERT ... ON DUPLICATE KEY UPDATE');
    }

    public function testMarkUnfollowedUpdatesStatusButDoesNotDelete(): void
    {
        $repo = new SubscriberRepository();
        $repo->mark_unfollowed('U123', '2026-05-21 00:00:00');

        $update_found = false;
        $delete_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] !== 'prepare') {
                continue;
            }
            if (str_starts_with(ltrim($q[1]), 'UPDATE')) {
                $update_found = true;
                $this->assertContains('unfollowed', $q[2]);
                $this->assertContains('U123', $q[2]);
            }
            if (str_starts_with(ltrim($q[1]), 'DELETE')) {
                $delete_found = true;
            }
        }
        $this->assertTrue($update_found, 'mark_unfollowed must issue an UPDATE');
        $this->assertFalse($delete_found, 'unfollow MUST NOT delete the subscriber row');
    }
}
