<?php
/**
 * @package BotCat\Tests\Unit\Tags
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Tags;

use BotCat\Subscribers\Subscriber;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tags\SubscriberTagRepository;
use BotCat\Tags\Tag;
use BotCat\Tags\TagCommand;
use BotCat\Tags\TagCommandHandler;
use BotCat\Tags\TagRepository;
use BotCat\Tests\TestCase;

final class TagCommandHandlerTest extends TestCase
{
    public function testNonCommandReturnsNullReply(): void
    {
        $handler = $this->handler();

        $this->assertNull($handler->handle('U1', TagCommand::not_a_command()));
    }

    public function testSubscribeByKeyword(): void
    {
        $tag = $this->build_tag(7, 'tech', '科技', '科技');

        $tags = $this->createMock(TagRepository::class);
        $tags->method('find_by_keyword')->with('科技')->willReturn($tag);
        $tags->method('list_all')->willReturn([$tag]);

        $subs = $this->createMock(SubscriberRepository::class);
        $subs->method('find_by_line_id')->willReturn($this->subscriber(101));

        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->expects($this->once())->method('subscribe')->with(101, 7);

        $reply = (new TagCommandHandler($tags, $joins, $subs))
            ->handle('U1', TagCommand::subscribe('科技'));

        $this->assertNotNull($reply);
        $this->assertStringContainsString('科技', $reply);
    }

    public function testUnsubscribeByKeyword(): void
    {
        $tag = $this->build_tag(7, 'tech', '科技', '科技');

        $tags = $this->createMock(TagRepository::class);
        $tags->method('find_by_keyword')->willReturn($tag);
        $tags->method('list_all')->willReturn([$tag]);

        $subs = $this->createMock(SubscriberRepository::class);
        $subs->method('find_by_line_id')->willReturn($this->subscriber(101));

        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->expects($this->once())->method('unsubscribe')->with(101, 7);

        $reply = (new TagCommandHandler($tags, $joins, $subs))
            ->handle('U1', TagCommand::unsubscribe('科技'));

        $this->assertNotNull($reply);
        $this->assertStringContainsString('科技', $reply);
    }

    public function testUnknownKeywordRepliesWithAvailableList(): void
    {
        $tags = $this->createMock(TagRepository::class);
        $tags->method('find_by_keyword')->willReturn(null);
        $tags->method('list_all')->willReturn([
            $this->build_tag(7, 'tech', '科技', '科技'),
            $this->build_tag(8, 'finance', '財經', '財經'),
        ]);

        $subs  = $this->createMock(SubscriberRepository::class);
        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->expects($this->never())->method('subscribe');

        $reply = (new TagCommandHandler($tags, $joins, $subs))
            ->handle('U1', TagCommand::subscribe('unknown'));

        $this->assertNotNull($reply);
        $this->assertStringContainsString('科技', $reply);
        $this->assertStringContainsString('財經', $reply);
    }

    public function testListShowsCurrentSubscriptions(): void
    {
        $tech    = $this->build_tag(7, 'tech', '科技', '科技');
        $finance = $this->build_tag(8, 'finance', '財經', '財經');

        $tags = $this->createMock(TagRepository::class);
        $tags->method('list_all')->willReturn([$tech, $finance]);

        $subs = $this->createMock(SubscriberRepository::class);
        $subs->method('find_by_line_id')->willReturn($this->subscriber(101));

        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->method('tag_ids_for_subscriber')->with(101)->willReturn([8]);

        $reply = (new TagCommandHandler($tags, $joins, $subs))
            ->handle('U1', TagCommand::list());

        $this->assertNotNull($reply);
        $this->assertStringContainsString('財經', $reply);
        $this->assertStringNotContainsString('科技', $reply);
    }

    public function testListWithNoSubscriptionsReturnsNoneMessage(): void
    {
        $tags  = $this->createMock(TagRepository::class);
        $tags->method('list_all')->willReturn([]);
        $subs  = $this->createMock(SubscriberRepository::class);
        $subs->method('find_by_line_id')->willReturn($this->subscriber(101));
        $joins = $this->createMock(SubscriberTagRepository::class);
        $joins->method('tag_ids_for_subscriber')->willReturn([]);

        $reply = (new TagCommandHandler($tags, $joins, $subs))
            ->handle('U1', TagCommand::list());

        $this->assertNotNull($reply);
    }

    private function handler(): TagCommandHandler
    {
        return new TagCommandHandler(
            $this->createMock(TagRepository::class),
            $this->createMock(SubscriberTagRepository::class),
            $this->createMock(SubscriberRepository::class)
        );
    }

    private function build_tag(int $id, string $slug, string $display, string $keyword): Tag
    {
        return new Tag($id, $slug, $display, $keyword, [], '', '');
    }

    private function subscriber(int $id): Subscriber
    {
        return new Subscriber(
            id: $id, line_user_id: 'U1', display_name: null, picture_url: null,
            status: 'active', followed_at: '', unfollowed_at: null
        );
    }
}
