<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\EligiblePostTypes;
use BotCat\Push\PostPublishObserver;
use BotCat\Push\PushJobScheduler;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Trigger a push job on post publish.
 */
final class PostPublishObserverTest extends TestCase
{
    public function testDraftToPublishOfEligiblePostTypeEnqueues(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $eligible->method('is_eligible')->with('post')->willReturn(true);

        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->once())->method('enqueue')
            ->with($this->equalTo(42), $this->equalTo('post'));

        $observer = new PostPublishObserver($eligible, $scheduler);
        $observer->on_transition('publish', 'draft', $this->post(42, 'post'));
    }

    public function testRepublishingDoesNotEnqueue(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $eligible->expects($this->never())->method('is_eligible');

        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->never())->method('enqueue');

        (new PostPublishObserver($eligible, $scheduler))
            ->on_transition('publish', 'publish', $this->post(42, 'post'));
    }

    public function testIneligiblePostTypeIsSkipped(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $eligible->method('is_eligible')->with('event')->willReturn(false);

        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->never())->method('enqueue');

        (new PostPublishObserver($eligible, $scheduler))
            ->on_transition('publish', 'draft', $this->post(99, 'event'));
    }

    public function testNonPublishTransitionIsSkipped(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $eligible->expects($this->never())->method('is_eligible');

        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->never())->method('enqueue');

        (new PostPublishObserver($eligible, $scheduler))
            ->on_transition('draft', 'auto-draft', $this->post(1, 'post'));
    }

    public function testScheduledPostBecomingPublishedEnqueues(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $eligible->method('is_eligible')->with('post')->willReturn(true);

        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->once())->method('enqueue')
            ->with($this->equalTo(7), $this->equalTo('post'));

        (new PostPublishObserver($eligible, $scheduler))
            ->on_transition('publish', 'future', $this->post(7, 'post'));
    }

    public function testMissingPostObjectIsTolerated(): void
    {
        $eligible = $this->createMock(EligiblePostTypes::class);
        $scheduler = $this->createMock(PushJobScheduler::class);
        $scheduler->expects($this->never())->method('enqueue');

        (new PostPublishObserver($eligible, $scheduler))
            ->on_transition('publish', 'draft', null);
    }

    private function post(int $id, string $type): object
    {
        $post = new \stdClass();
        $post->ID = $id;
        $post->post_type = $type;
        return $post;
    }
}
