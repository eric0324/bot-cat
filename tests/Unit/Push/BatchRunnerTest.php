<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Channel\ChannelSettings;
use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Push\BatchRunner;
use BotCat\Push\MessageBuilder;
use BotCat\Push\MulticastClient;
use BotCat\Push\MulticastResult;
use BotCat\Push\PushJob;
use BotCat\Push\PushJobRepository;
use BotCat\Push\PushJobScheduler;
use BotCat\Push\PushLog;
use BotCat\Push\PushLogRepository;
use BotCat\Push\RetryPolicy;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class BatchRunnerTest extends TestCase
{
    public function testSuccessfulBatchMarksLogsSentAndIncrementsJobCounters(): void
    {
        $deps = $this->build_deps();

        $deps['jobs']->method('find_by_id')->willReturn($this->job_pending(7));

        $deps['logs']->expects($this->once())->method('mark_status')
            ->with($this->equalTo([1, 2]), PushLog::STATUS_SENT, null);
        $deps['jobs']->expects($this->once())->method('increment_counts')
            ->with(7, 2, 0);

        $deps['client']->expects($this->once())->method('send')
            ->willReturn(new MulticastResult(ok: true, http_status: 200));

        $this->expect_logs_loaded($deps['logs'], 7, [1, 2], [
            ['id' => '1', 'job_id' => '7', 'line_user_id' => 'U1', 'subscriber_id' => null, 'status' => 'pending', 'error' => null, 'attempts' => '0', 'is_test' => '0', 'created_at' => ''],
            ['id' => '2', 'job_id' => '7', 'line_user_id' => 'U2', 'subscriber_id' => null, 'status' => 'pending', 'error' => null, 'attempts' => '0', 'is_test' => '0', 'created_at' => ''],
        ]);

        Functions\expect('as_schedule_single_action')->never();

        $this->runner($deps)->run(7, [1, 2], 1);
    }

    public function testFiveHundredAttemptOneSchedulesRetryAtThirtySeconds(): void
    {
        $deps = $this->build_deps();

        $deps['jobs']->method('find_by_id')->willReturn($this->job_pending(7));
        $deps['client']->method('send')->willReturn(new MulticastResult(
            ok: false,
            http_status: 502,
            error_code: MulticastClient::ERROR_SERVER,
            error_message: 'Bad Gateway'
        ));

        $this->expect_logs_loaded($deps['logs'], 7, [1, 2], [
            ['id' => '1', 'line_user_id' => 'U1', 'job_id' => '7', 'status' => 'pending'],
            ['id' => '2', 'line_user_id' => 'U2', 'job_id' => '7', 'status' => 'pending'],
        ]);

        $deps['logs']->expects($this->never())->method('mark_status');
        $deps['jobs']->expects($this->never())->method('increment_counts');

        Functions\expect('as_schedule_single_action')->once()
            ->with(
                $this->isType('int'),
                PushJobScheduler::ACTION_RUN_BATCH,
                $this->equalTo([7, [1, 2], 2]),
                PushJobScheduler::GROUP
            );

        $this->runner($deps)->run(7, [1, 2], 1);
    }

    public function testAuthFailureAbortsJobAndDoesNotRetry(): void
    {
        $deps = $this->build_deps();

        $deps['jobs']->method('find_by_id')->willReturn($this->job_pending(7));
        $deps['client']->method('send')->willReturn(new MulticastResult(
            ok: false,
            http_status: 401,
            error_code: MulticastClient::ERROR_AUTH,
            error_message: 'Authentication failed'
        ));

        $this->expect_logs_loaded($deps['logs'], 7, [1], [
            ['id' => '1', 'line_user_id' => 'U1', 'job_id' => '7', 'status' => 'pending'],
        ]);

        $deps['jobs']->expects($this->once())->method('mark_status')
            ->with(7, PushJob::STATUS_ABORTED_AUTH, $this->stringContains('Authentication failed'));
        Functions\expect('as_schedule_single_action')->never();

        $this->runner($deps)->run(7, [1], 1);
    }

    public function testFinalFailureMarksLogsAsFailed(): void
    {
        $deps = $this->build_deps();
        $deps['jobs']->method('find_by_id')->willReturn($this->job_pending(7));
        $deps['client']->method('send')->willReturn(new MulticastResult(
            ok: false,
            http_status: 502,
            error_code: MulticastClient::ERROR_SERVER,
            error_message: '5xx persistent'
        ));

        $this->expect_logs_loaded($deps['logs'], 7, [1, 2], [
            ['id' => '1', 'line_user_id' => 'U1', 'job_id' => '7', 'status' => 'pending'],
            ['id' => '2', 'line_user_id' => 'U2', 'job_id' => '7', 'status' => 'pending'],
        ]);

        $deps['logs']->expects($this->once())->method('mark_status')
            ->with([1, 2], PushLog::STATUS_FAILED, $this->stringContains('5xx persistent'));
        $deps['jobs']->expects($this->once())->method('increment_counts')
            ->with(7, 0, 2);

        Functions\expect('as_schedule_single_action')->never();

        $this->runner($deps)->run(7, [1, 2], 4);
    }

    public function testTerminalJobIsNoOp(): void
    {
        $deps = $this->build_deps();
        $deps['jobs']->method('find_by_id')->willReturn($this->job_with_status(7, PushJob::STATUS_ABORTED_AUTH));

        $deps['client']->expects($this->never())->method('send');
        $deps['logs']->expects($this->never())->method('mark_status');

        $this->runner($deps)->run(7, [1, 2], 1);
    }

    public function testUnconfiguredChannelAbortsJob(): void
    {
        $deps = $this->build_deps(channel_secret: '', access_token: '');
        $deps['jobs']->method('find_by_id')->willReturn($this->job_pending(7));

        $deps['client']->expects($this->never())->method('send');
        $deps['jobs']->expects($this->once())->method('mark_status')
            ->with(7, PushJob::STATUS_FAILED, $this->stringContains('channel'));

        $this->runner($deps)->run(7, [1], 1);
    }

    /** @return array{jobs:PushJobRepository,logs:PushLogRepository,channel:ChannelSettingsRepository,client:MulticastClient,policy:RetryPolicy,messages:MessageBuilder} */
    private function build_deps(string $channel_secret = 'sec', string $access_token = 'tok'): array
    {
        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('1', $channel_secret, $access_token));

        $messages = $this->createMock(MessageBuilder::class);
        $messages->method('build_for_job')->willReturn([['type' => 'text', 'text' => 'hi']]);

        return [
            'jobs'     => $this->createMock(PushJobRepository::class),
            'logs'     => $this->createMock(PushLogRepository::class),
            'channel'  => $channel,
            'client'   => $this->createMock(MulticastClient::class),
            'policy'   => new RetryPolicy(),
            'messages' => $messages,
        ];
    }

    /**
     * @param array<string, mixed> $deps
     */
    private function runner(array $deps): BatchRunner
    {
        return new BatchRunner(
            $deps['jobs'],
            $deps['logs'],
            $deps['channel'],
            $deps['client'],
            $deps['policy'],
            $deps['messages']
        );
    }

    private function job_pending(int $id): PushJob
    {
        return $this->job_with_status($id, PushJob::STATUS_PENDING);
    }

    private function job_with_status(int $id, string $status): PushJob
    {
        return new PushJob(
            id: $id,
            post_id: 100,
            post_type: 'post',
            status: $status,
            recipient_count: 2,
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

    /**
     * @param list<int>                       $ids
     * @param list<array<string, mixed>>      $rows
     */
    private function expect_logs_loaded(PushLogRepository $logs, int $job_id, array $ids, array $rows): void
    {
        $log_objects = array_map(static fn(array $row): PushLog => PushLog::from_row($row), $rows);
        $logs->method('find_by_ids_for_job')
            ->with($job_id, $ids)
            ->willReturn($log_objects);
    }
}
