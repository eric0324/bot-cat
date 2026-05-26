<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\PushJob;
use BotCat\Push\PushJobRepository;
use BotCat\Tests\TestCase;

final class PushJobRepositoryTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public int $insert_id = 0;
            /** @var list<array{0:string,1:string,2:list<mixed>}> */
            public array $queries = [];
            /** @var array<int, array<string, mixed>> */
            public array $rows = [];
            public string $count = '0';
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
            }
            public function get_row(string $sql, $output = OBJECT): ?array
            {
                $this->queries[] = ['get_row', $sql, []];
                return array_shift($this->rows);
            }
            public function get_var(string $sql): ?string
            {
                $this->queries[] = ['get_var', $sql, []];
                return $this->count;
            }
            public function get_results(string $sql, $output = OBJECT): array
            {
                $this->queries[] = ['get_results', $sql, []];
                return array_values($this->rows);
            }
            public function query(string $sql): int
            {
                $this->queries[] = ['query', $sql, []];
                $this->insert_id = $this->insert_id ?: 99;
                return 1;
            }
            public function insert(string $table, array $data, array $formats): int
            {
                $this->queries[] = ['insert', $table, $data];
                $this->insert_id = 123;
                return 1;
            }
            public function update(string $table, array $data, array $where, array $df = array(), array $wf = array()): int
            {
                $this->queries[] = ['update', $table . ':' . implode(',', array_keys($where)), array_merge($data, $where)];
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testCreatePendingInsertsRowAndReturnsId(): void
    {
        $id = (new PushJobRepository())->create_pending(post_id: 42, post_type: 'post', is_test: false);

        $this->assertSame(123, $id);

        $insert_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert' && $q[1] === 'wp_botcat_push_jobs') {
                $insert_found = true;
                $this->assertSame(42, $q[2]['post_id']);
                $this->assertSame('post', $q[2]['post_type']);
                $this->assertSame(PushJob::STATUS_PENDING, $q[2]['status']);
                $this->assertSame(0, $q[2]['is_test']);
            }
        }
        $this->assertTrue($insert_found);
    }

    public function testFindCompletedForPostReturnsSentOrPartialOnly(): void
    {
        (new PushJobRepository())->find_completed_for_post(42);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'post_id = %d') && str_contains($q[1], 'status IN')) {
                $found = true;
                $this->assertContains(42, $q[2]);
                $this->assertContains(PushJob::STATUS_SENT, $q[2]);
                $this->assertContains(PushJob::STATUS_PARTIAL, $q[2]);
            }
        }
        $this->assertTrue($found, 'idempotency lookup must filter by post_id + completed statuses');
    }

    public function testMarkRunningSetsStatusTriggeredAndRecipientCount(): void
    {
        (new PushJobRepository())->mark_running(99, recipient_count: 1200);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'update' && str_starts_with($q[1], 'wp_botcat_push_jobs:')) {
                $found = true;
                $this->assertSame(99, $q[2]['id']);
                $this->assertSame(PushJob::STATUS_RUNNING, $q[2]['status']);
                $this->assertSame(1200, $q[2]['recipient_count']);
                $this->assertNotEmpty($q[2]['started_at']);
            }
        }
        $this->assertTrue($found);
    }

    public function testMarkStatusUpdatesStatusAndOptionalLastError(): void
    {
        (new PushJobRepository())->mark_status(99, PushJob::STATUS_ABORTED_AUTH, last_error: 'Authentication failed');

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'update' && str_starts_with($q[1], 'wp_botcat_push_jobs:')) {
                $found = true;
                $this->assertSame(PushJob::STATUS_ABORTED_AUTH, $q[2]['status']);
                $this->assertSame('Authentication failed', $q[2]['last_error']);
            }
        }
        $this->assertTrue($found);
    }

    public function testIncrementCountsUsesAtomicSqlUpdate(): void
    {
        (new PushJobRepository())->increment_counts(99, sent_delta: 498, failed_delta: 2);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'UPDATE') && str_contains($q[1], 'sent_count = sent_count')) {
                $found = true;
                $this->assertContains(498, $q[2]);
                $this->assertContains(2, $q[2]);
                $this->assertContains(99, $q[2]);
            }
        }
        $this->assertTrue($found, 'increment_counts must be a single atomic UPDATE');
    }
}
