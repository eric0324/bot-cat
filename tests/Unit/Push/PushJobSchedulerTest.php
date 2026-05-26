<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\PushJobRepository;
use BotCat\Push\PushJobScheduler;
use BotCat\Rules\PushThrottle;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class PushJobSchedulerTest extends TestCase
{
    public function testEnqueueCreatesPendingRowAndQueuesActionSchedulerAction(): void
    {
        $repo = $this->createMock(PushJobRepository::class);
        $repo->expects($this->once())->method('create_pending')
            ->with($this->equalTo(42), $this->equalTo('post'), $this->equalTo(false))
            ->willReturn(99);

        Functions\expect('as_schedule_single_action')->once()
            ->with(
                $this->isType('int'),
                PushJobScheduler::ACTION_RUN_JOB,
                $this->equalTo([99]),
                PushJobScheduler::GROUP
            );

        $scheduler = new PushJobScheduler($repo);
        $id = $scheduler->enqueue(42, 'post');

        $this->assertSame(99, $id);
    }

    public function testThrottleScheduledTimeReplacesImmediateTime(): void
    {
        $repo = $this->createMock(PushJobRepository::class);
        $repo->method('create_pending')->willReturn(99);

        $throttle = $this->createMock(PushThrottle::class);
        $throttle->expects($this->once())->method('next_slot')->willReturn(1_800_000_000);

        Functions\expect('as_schedule_single_action')->once()
            ->with(
                $this->equalTo(1_800_000_000),
                PushJobScheduler::ACTION_RUN_JOB,
                $this->equalTo([99]),
                PushJobScheduler::GROUP
            );

        (new PushJobScheduler($repo, $throttle))->enqueue(42, 'post');
    }

    public function testTestPushBypassesThrottle(): void
    {
        $repo = $this->createMock(PushJobRepository::class);
        $repo->method('create_pending')->willReturn(99);

        $throttle = $this->createMock(PushThrottle::class);
        $throttle->expects($this->never())->method('next_slot');

        Functions\expect('as_schedule_single_action')->once();

        (new PushJobScheduler($repo, $throttle))->enqueue(42, 'post', is_test: true);

        $this->assertTrue(true);
    }

    public function testEnqueueTestPushFlagsTheJob(): void
    {
        $repo = $this->createMock(PushJobRepository::class);
        $repo->expects($this->once())->method('create_pending')
            ->with($this->equalTo(0), $this->equalTo('test'), $this->equalTo(true))
            ->willReturn(7);

        Functions\expect('as_schedule_single_action')->once();

        (new PushJobScheduler($repo))->enqueue_test(0, ['U1', 'U2']);

        $this->assertTrue(true);
    }
}
