<?php
/**
 * @package BotCat\Tests\Unit\Tags
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Tags;

use BotCat\Tags\SubscriberTagRepository;
use BotCat\Tests\TestCase;

final class SubscriberTagRepositoryTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            /** @var list<array{0:string,1:string,2:array}> */
            public array $queries = [];
            /** @var list<array<string,mixed>>|null */
            public ?array $next_rows = null;
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $this->queries[] = ['get_results', $sql, []];
                return $this->next_rows ?? [];
            }
            public function query(string $sql): int
            {
                $this->queries[] = ['query', $sql, []];
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testSubscribeUpsertsJoinRow(): void
    {
        (new SubscriberTagRepository())->subscribe(101, 7);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'INSERT') && str_contains($q[1], 'ON DUPLICATE KEY UPDATE')) {
                $found = true;
                $this->assertContains(101, $q[2]);
                $this->assertContains(7, $q[2]);
            }
        }
        $this->assertTrue($found, 'subscribe should use INSERT...ON DUPLICATE KEY UPDATE');
    }

    public function testUnsubscribeRemovesJoinRow(): void
    {
        (new SubscriberTagRepository())->unsubscribe(101, 7);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'DELETE') && str_contains($q[1], 'subscriber_id = %d')) {
                $found = true;
                $this->assertContains(101, $q[2]);
                $this->assertContains(7, $q[2]);
            }
        }
        $this->assertTrue($found);
    }

    public function testTagsForSubscriberReturnsTagIds(): void
    {
        $this->wpdb->next_rows = [
            ['tag_id' => '7'],
            ['tag_id' => '9'],
        ];

        $ids = (new SubscriberTagRepository())->tag_ids_for_subscriber(101);

        $this->assertSame([7, 9], $ids);
    }

    public function testActiveLineUserIdsForTagsStreamsInChunks(): void
    {
        $page = SubscriberTagRepository::CHUNK_SIZE;
        $batch1 = array_map(static fn($i) => ['line_user_id' => 'U' . $i], range(1, $page));
        $batch2 = array_map(static fn($i) => ['line_user_id' => 'U' . $i], range($page + 1, $page + 3));

        $calls = [$batch1, $batch2, []];
        $this->wpdb = $this->wpdb;
        $this->wpdb->next_rows = $batch1;

        // Make the mock cycle through batches via get_results side effects:
        $wpdb_mock = $this->wpdb;
        $wpdb_mock->next_rows = $batch1;
        $wpdb_mock_swap = function () use ($wpdb_mock, &$calls) {
            $wpdb_mock->next_rows = array_shift($calls) ?? [];
        };

        // Reimplement get_results dynamically — simpler: instead use a counter on the mock by replacing the class.
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public array $queries = [];
            /** @var list<list<array<string,mixed>>> */
            public array $batches = [];
            public function prepare(string $query, ...$args): string { $this->queries[] = ['prepare', $query, $args]; return $query; }
            public function get_results(string $sql, $output = OBJECT): array { $this->queries[] = ['get_results', $sql, []]; return array_shift($this->batches) ?? []; }
            public function query(string $sql): int { return 1; }
        };
        $wpdb->batches = [$batch1, $batch2, []];

        $ids = iterator_to_array((new SubscriberTagRepository())->active_line_user_ids_for_tags([7, 9]), false);

        $this->assertCount($page + 3, $ids);
        $this->assertSame('U1', $ids[0]);
    }
}
