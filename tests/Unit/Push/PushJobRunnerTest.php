<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\PushJob;
use BotCat\Push\PushJobRepository;
use BotCat\Push\PushJobRunner;
use BotCat\Push\PushJobScheduler;
use BotCat\Push\PushLogRepository;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class PushJobRunnerTest extends TestCase
{
    public function testIdempotencyCheckMarksDuplicateJobSkipped(): void
    {
        $jobs = $this->createMock(PushJobRepository::class);
        $jobs->method('find_by_id')->willReturn($this->job(7, 42, PushJob::STATUS_PENDING));
        $jobs->method('find_completed_for_post')
            ->with(42)
            ->willReturn($this->job(5, 42, PushJob::STATUS_SENT));
        $jobs->expects($this->once())->method('mark_status')
            ->with(7, PushJob::STATUS_SKIPPED_DUPLICATE);

        $logs = $this->createMock(PushLogRepository::class);
        $logs->expects($this->never())->method('create_for_job');

        $subscribers = $this->createMock(SubscriberRepository::class);

        Functions\expect('as_schedule_single_action')->never();

        (new PushJobRunner($jobs, $logs, $subscribers))->run(7);
    }

    public function testEmptyAudienceMarksJobSentImmediately(): void
    {
        $jobs = $this->createMock(PushJobRepository::class);
        $jobs->method('find_by_id')->willReturn($this->job(7, 42, PushJob::STATUS_PENDING));
        $jobs->method('find_completed_for_post')->willReturn(null);
        $jobs->expects($this->once())->method('mark_running')->with(7, 0);
        $jobs->expects($this->once())->method('mark_status')->with(7, PushJob::STATUS_SENT);

        $logs = $this->createMock(PushLogRepository::class);
        $logs->expects($this->never())->method('create_for_job');

        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->method('active_ids')->willReturnCallback(fn() => yield from []);

        Functions\expect('as_schedule_single_action')->never();

        (new PushJobRunner($jobs, $logs, $subscribers))->run(7);
    }

    public function testTwelveHundredSubscribersFanOutIntoThreeBatchesOfFiveHundredFiveHundredAndTwoHundred(): void
    {
        $jobs = $this->createMock(PushJobRepository::class);
        $jobs->method('find_by_id')->willReturn($this->job(7, 42, PushJob::STATUS_PENDING));
        $jobs->method('find_completed_for_post')->willReturn(null);
        $jobs->expects($this->once())->method('mark_running')->with(7, 1200);

        $logs        = $this->createMock(PushLogRepository::class);
        $batch_sizes = [];
        $logs->method('create_for_job')
            ->willReturnCallback(function (int $job_id, array $line_user_ids) use (&$batch_sizes) {
                $batch_sizes[] = count($line_user_ids);
                return range(1, count($line_user_ids));
            });

        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->method('active_ids')->willReturnCallback(function () {
            for ($i = 1; $i <= 1200; $i++) {
                yield 'U' . $i;
            }
        });

        Functions\expect('as_schedule_single_action')->times(3)
            ->with(
                $this->isType('int'),
                PushJobScheduler::ACTION_RUN_BATCH,
                $this->callback(static fn(array $args): bool => $args[0] === 7 && is_array($args[1])),
                PushJobScheduler::GROUP
            );

        (new PushJobRunner($jobs, $logs, $subscribers))->run(7);

        $this->assertSame([500, 500, 200], $batch_sizes);
    }

    public function testTestPushUsesProvidedUserIdsAndSkipsSubscriberRepository(): void
    {
        $jobs = $this->createMock(PushJobRepository::class);
        $jobs->method('find_by_id')->willReturn($this->test_job(7));
        $jobs->method('find_completed_for_post')->willReturn(null);
        $jobs->expects($this->once())->method('mark_running')->with(7, 2);

        $logs = $this->createMock(PushLogRepository::class);
        $logs->expects($this->once())->method('create_for_job')
            ->with(7, ['U1', 'U2'], $this->anything(), true)
            ->willReturn([1, 2]);

        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->expects($this->never())->method('active_ids');

        Functions\expect('as_schedule_single_action')->once();

        (new PushJobRunner($jobs, $logs, $subscribers))->run(7, ['U1', 'U2']);
    }

    public function testNonPendingJobIsNoOp(): void
    {
        $jobs = $this->createMock(PushJobRepository::class);
        $jobs->method('find_by_id')->willReturn($this->job(7, 42, PushJob::STATUS_RUNNING));

        $jobs->expects($this->never())->method('find_completed_for_post');
        $jobs->expects($this->never())->method('mark_running');

        (new PushJobRunner($jobs, $this->createMock(PushLogRepository::class), $this->createMock(SubscriberRepository::class)))->run(7);
    }

    private function job(int $id, int $post_id, string $status): PushJob
    {
        return new PushJob(
            id: $id,
            post_id: $post_id,
            post_type: 'post',
            status: $status,
            recipient_count: 0,
            sent_count: 0,
            failed_count: 0,
            is_test: false,
            last_error: null,
            created_at: '2026-05-20 00:00:00',
            triggered_at: '2026-05-20 00:00:00',
            started_at: null,
            finished_at: null
        );
    }

    private function test_job(int $id): PushJob
    {
        return new PushJob(
            id: $id,
            post_id: 0,
            post_type: 'test',
            status: PushJob::STATUS_PENDING,
            recipient_count: 0,
            sent_count: 0,
            failed_count: 0,
            is_test: true,
            last_error: null,
            created_at: '2026-05-20 00:00:00',
            triggered_at: '2026-05-20 00:00:00',
            started_at: null,
            finished_at: null
        );
    }
}
