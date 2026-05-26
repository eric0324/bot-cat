<?php
/**
 * @package BotCat\Tests\Unit\Tags
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Tags;

use BotCat\Push\PushJob;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tags\AudienceResolver;
use BotCat\Tags\SubscriberTagRepository;
use BotCat\Tags\Tag;
use BotCat\Tags\TagRepository;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class AudienceResolverTest extends TestCase
{
    public function testCategorizedPostNarrowsAudienceToTaggedSubscribers(): void
    {
        Functions\when('wp_get_post_categories')->alias(static fn() => [3]);

        $tech = new Tag(7, 'tech', '科技', '科技', [3], '', '');

        $tags = $this->createMock(TagRepository::class);
        $tags->method('find_for_categories')->with([3])->willReturn([$tech]);

        $subs = $this->createMock(SubscriberRepository::class);
        $subs->expects($this->never())->method('active_ids');

        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->expects($this->once())->method('active_line_user_ids_for_tags')->with([7])
            ->willReturnCallback(fn() => yield 'U1');

        $resolver = new AudienceResolver($subs, $tags, $joins);
        $audience = iterator_to_array($resolver->iterator_for_job($this->job(100)), false);

        $this->assertSame(['U1'], $audience);
    }

    public function testUncategorizedPostFallsBackToFullAudience(): void
    {
        Functions\when('wp_get_post_categories')->alias(static fn() => []);

        $tags  = $this->createMock(TagRepository::class);
        $tags->expects($this->never())->method('find_for_categories');

        $subs  = $this->createMock(SubscriberRepository::class);
        $subs->method('active_ids')->willReturnCallback(fn() => yield 'U_all');

        $joins = $this->createMock(SubscriberTagRepository::class);

        $audience = iterator_to_array(
            (new AudienceResolver($subs, $tags, $joins))->iterator_for_job($this->job(100)),
            false
        );

        $this->assertSame(['U_all'], $audience);
    }

    public function testPostWithoutAnyMatchingTagsFallsBack(): void
    {
        Functions\when('wp_get_post_categories')->alias(static fn() => [99]);

        $tags = $this->createMock(TagRepository::class);
        $tags->method('find_for_categories')->willReturn([]);

        $subs = $this->createMock(SubscriberRepository::class);
        $subs->method('active_ids')->willReturnCallback(fn() => yield 'U_all');

        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->expects($this->never())->method('active_line_user_ids_for_tags');

        $audience = iterator_to_array(
            (new AudienceResolver($subs, $tags, $joins))->iterator_for_job($this->job(100)),
            false
        );

        $this->assertSame(['U_all'], $audience);
    }

    public function testTestJobSkipsAudienceResolution(): void
    {
        $subs  = $this->createMock(SubscriberRepository::class);
        $subs->expects($this->never())->method('active_ids');
        $tags  = $this->createMock(TagRepository::class);
        $joins = $this->createMock(SubscriberTagRepository::class);

        $resolver = new AudienceResolver($subs, $tags, $joins);
        $audience = iterator_to_array(
            $resolver->iterator_for_job($this->test_job()),
            false
        );

        $this->assertSame([], $audience);
    }

    private function job(int $post_id): PushJob
    {
        return new PushJob(
            id: 1, post_id: $post_id, post_type: 'post', status: 'pending',
            recipient_count: 0, sent_count: 0, failed_count: 0,
            is_test: false, last_error: null,
            created_at: '', triggered_at: null, started_at: null, finished_at: null
        );
    }

    private function test_job(): PushJob
    {
        return new PushJob(
            id: 1, post_id: 0, post_type: 'test', status: 'pending',
            recipient_count: 0, sent_count: 0, failed_count: 0,
            is_test: true, last_error: null,
            created_at: '', triggered_at: null, started_at: null, finished_at: null
        );
    }
}
