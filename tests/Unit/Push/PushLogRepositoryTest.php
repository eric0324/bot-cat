<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\PushLog;
use BotCat\Push\PushLogRepository;
use BotCat\Tests\TestCase;

final class PushLogRepositoryTest extends TestCase
{
    private object $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        global $wpdb;
        $wpdb = new class () {
            public string $prefix = 'wp_';
            public int $insert_id = 0;
            /** @var list<array{0:string,1:string,2:array}> */
            public array $queries = [];
            /** @var array<int, array<string, mixed>> */
            public array $rows = [];
            public string $count = '0';
            public function prepare(string $query, ...$args): string
            {
                $this->queries[] = ['prepare', $query, $args];
                return $query;
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
                return 1;
            }
            public function insert(string $table, array $data, array $formats): int
            {
                $this->queries[] = ['insert', $table, $data];
                $this->insert_id = ($this->insert_id ?: 0) + 1;
                return 1;
            }
        };
        $this->wpdb = $wpdb;
    }

    public function testCreateForJobInsertsOneRowPerRecipientAndReturnsIds(): void
    {
        $repo = new PushLogRepository();

        $ids = $repo->create_for_job(
            job_id: 7,
            line_user_ids: ['U1', 'U2', 'U3'],
            subscriber_ids: [101, 102, null],
            is_test: false
        );

        $this->assertCount(3, $ids);
        $insert_count = 0;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'insert' && $q[1] === 'wp_botcat_push_logs') {
                $insert_count++;
                $this->assertSame(7, $q[2]['job_id']);
                $this->assertSame(PushLog::STATUS_PENDING, $q[2]['status']);
            }
        }
        $this->assertSame(3, $insert_count);
    }

    public function testMarkStatusUpdatesOnlyTheGivenIds(): void
    {
        (new PushLogRepository())->mark_status([10, 11, 12], PushLog::STATUS_SENT);

        $update_found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'UPDATE') && str_contains($q[1], 'IN (')) {
                $update_found = true;
                $this->assertContains(PushLog::STATUS_SENT, $q[2]);
                $this->assertContains(10, $q[2]);
                $this->assertContains(11, $q[2]);
                $this->assertContains(12, $q[2]);
            }
        }
        $this->assertTrue($update_found);
    }

    public function testMarkStatusStoresErrorWhenProvided(): void
    {
        (new PushLogRepository())->mark_status([10], PushLog::STATUS_FAILED, 'HTTP 500');

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'error = %s')) {
                $found = true;
                $this->assertContains('HTTP 500', $q[2]);
            }
        }
        $this->assertTrue($found, 'error column must be updated when error is supplied');
    }

    public function testCountForJobByStatusReturnsInteger(): void
    {
        $this->wpdb->count = '7';
        $this->assertSame(7, (new PushLogRepository())->count_for_job(42, PushLog::STATUS_FAILED));

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'COUNT')) {
                $found = true;
                $this->assertContains(42, $q[2]);
                $this->assertContains(PushLog::STATUS_FAILED, $q[2]);
            }
        }
        $this->assertTrue($found);
    }

    public function testFailedLogsForJobReturnsTypedRows(): void
    {
        $this->wpdb->rows = [
            ['id' => '1', 'job_id' => '42', 'subscriber_id' => '101', 'line_user_id' => 'U1', 'status' => 'failed', 'error' => '500', 'attempts' => '3', 'is_test' => '0', 'created_at' => '2026-05-20 00:00:00'],
        ];

        $logs = (new PushLogRepository())->failed_for_job(42);

        $this->assertCount(1, $logs);
        $this->assertInstanceOf(PushLog::class, $logs[0]);
        $this->assertSame('U1', $logs[0]->line_user_id);
    }

    public function testResetForResendBringsFailedRowsBackToPending(): void
    {
        (new PushLogRepository())->reset_for_resend([10, 11]);

        $found = false;
        foreach ($this->wpdb->queries as $q) {
            if ($q[0] === 'prepare' && str_contains($q[1], 'UPDATE') && str_contains($q[1], 'IN (')) {
                $found = true;
                $this->assertContains(PushLog::STATUS_PENDING, $q[2]);
                $this->assertContains(10, $q[2]);
                $this->assertContains(11, $q[2]);
            }
        }
        $this->assertTrue($found);
    }
}
