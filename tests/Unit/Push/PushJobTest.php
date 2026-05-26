<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\PushJob;
use BotCat\Tests\TestCase;

final class PushJobTest extends TestCase
{
    public function testStatusConstantsCoverTheW2StateMachine(): void
    {
        // Spec scenarios reference exactly these terminal/transient states.
        $this->assertSame('pending', PushJob::STATUS_PENDING);
        $this->assertSame('running', PushJob::STATUS_RUNNING);
        $this->assertSame('sent', PushJob::STATUS_SENT);
        $this->assertSame('partial', PushJob::STATUS_PARTIAL);
        $this->assertSame('skipped_duplicate', PushJob::STATUS_SKIPPED_DUPLICATE);
        $this->assertSame('aborted_auth', PushJob::STATUS_ABORTED_AUTH);
        $this->assertSame('failed', PushJob::STATUS_FAILED);
    }

    public function testFromRowMapsScalarColumns(): void
    {
        $job = PushJob::from_row([
            'id'              => '17',
            'post_id'         => '42',
            'post_type'       => 'post',
            'status'          => 'running',
            'recipient_count' => '500',
            'sent_count'      => '250',
            'failed_count'    => '0',
            'is_test'         => '0',
            'last_error'      => null,
            'created_at'      => '2026-05-20 12:00:00',
            'triggered_at'    => '2026-05-20 12:00:01',
            'started_at'      => '2026-05-20 12:00:05',
            'finished_at'     => null,
        ]);

        $this->assertSame(17, $job->id);
        $this->assertSame(42, $job->post_id);
        $this->assertSame('post', $job->post_type);
        $this->assertSame('running', $job->status);
        $this->assertSame(500, $job->recipient_count);
        $this->assertSame(250, $job->sent_count);
        $this->assertSame(0, $job->failed_count);
        $this->assertFalse($job->is_test);
        $this->assertNull($job->finished_at);
    }

    public function testIsTerminalReturnsTrueForCompletedStates(): void
    {
        foreach ([PushJob::STATUS_SENT, PushJob::STATUS_PARTIAL, PushJob::STATUS_SKIPPED_DUPLICATE, PushJob::STATUS_ABORTED_AUTH, PushJob::STATUS_FAILED] as $status) {
            $job = $this->job_with_status($status);
            $this->assertTrue($job->is_terminal(), "$status should be terminal");
        }

        foreach ([PushJob::STATUS_PENDING, PushJob::STATUS_RUNNING] as $status) {
            $job = $this->job_with_status($status);
            $this->assertFalse($job->is_terminal(), "$status should NOT be terminal");
        }
    }

    private function job_with_status(string $status): PushJob
    {
        return new PushJob(
            id: 1,
            post_id: 1,
            post_type: 'post',
            status: $status,
            recipient_count: 0,
            sent_count: 0,
            failed_count: 0,
            is_test: false,
            last_error: null,
            created_at: '2026-05-20 12:00:00',
            triggered_at: null,
            started_at: null,
            finished_at: null
        );
    }
}
