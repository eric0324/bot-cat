<?php
/**
 * @package BotCat\Tests\Unit\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Subscribers;

use BotCat\Channel\ChannelSettings;
use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\ReplyClient;
use BotCat\Subscribers\FollowHandler;
use BotCat\Subscribers\LineProfileFetcher;
use BotCat\Subscribers\LineProfileResult;
use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tags\WelcomeMessageBuilder;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Follow event creates or reactivates a subscriber.
 */
final class FollowHandlerTest extends TestCase
{
    public function testFollowUpsertsSubscriberAndFetchesProfile(): void
    {
        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->expects($this->once())->method('upsert_active')->with(
            $this->equalTo('U123'),
            $this->equalTo('Eric'),
            $this->equalTo('https://line/p.jpg'),
            $this->isType('string')
        );

        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('1', 'sec', 'tok'));

        $profiles = $this->createMock(LineProfileFetcher::class);
        $profiles->expects($this->once())->method('fetch')
            ->with('U123', 'tok')
            ->willReturn(new LineProfileResult(ok: true, display_name: 'Eric', picture_url: 'https://line/p.jpg'));

        Functions\expect('as_enqueue_async_action')->never();

        $handler = new FollowHandler($subscribers, $channel, $profiles);
        $handler->handle('U123', 1_715_000_000);
    }

    public function testFollowStillCreatesSubscriberWhenProfileFetchFails(): void
    {
        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->expects($this->once())->method('upsert_active')->with(
            'U123',
            null,
            null,
            $this->isType('string')
        );

        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('1', 'sec', 'tok'));

        $profiles = $this->createMock(LineProfileFetcher::class);
        $profiles->expects($this->once())->method('fetch')
            ->willReturn(new LineProfileResult(ok: false, error_code: 'http_500'));

        Functions\expect('as_enqueue_async_action')->once()
            ->with(
                'botcat_retry_profile_fetch',
                $this->callback(static fn(array $args): bool => in_array('U123', $args, true)),
                $this->isType('string')
            );

        $handler = new FollowHandler($subscribers, $channel, $profiles);
        $handler->handle('U123', 1_715_000_000);
    }

    public function testFollowSkipsProfileFetchWhenChannelNotConfigured(): void
    {
        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->expects($this->once())->method('upsert_active');

        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('', '', ''));

        $profiles = $this->createMock(LineProfileFetcher::class);
        $profiles->expects($this->never())->method('fetch');

        Functions\expect('as_enqueue_async_action')->never();

        $handler = new FollowHandler($subscribers, $channel, $profiles);
        $handler->handle('U123', 1_715_000_000);
    }

    public function testWelcomeReplyIsSentOnFirstFollowWhenWelcomeBuilderProvided(): void
    {
        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->method('find_by_line_id')->willReturn(null); // brand new
        $subscribers->expects($this->once())->method('upsert_active');

        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('1', 'sec', 'tok'));

        $profiles = $this->createMock(LineProfileFetcher::class);
        $profiles->method('fetch')->willReturn(new LineProfileResult(ok: true, display_name: 'Eric'));

        $welcome = $this->createMock(WelcomeMessageBuilder::class);
        $welcome->method('build')->willReturn('Welcome 🐱');

        $reply_client = $this->createMock(ReplyClient::class);
        $reply_client->expects($this->once())->method('reply')
            ->with('tok', 'rt-abc', 'Welcome 🐱');

        $handler = new FollowHandler($subscribers, $channel, $profiles, $welcome, $reply_client);
        $handler->handle('U123', 1_715_000_000, 'rt-abc');
    }

    public function testWelcomeReplyIsSkippedOnRefollow(): void
    {
        $existing = new \BotCat\Subscribers\Subscriber(
            id: 1, line_user_id: 'U123', display_name: null, picture_url: null,
            status: 'unfollowed', followed_at: null, unfollowed_at: '2026-05-01 00:00:00'
        );

        $subscribers = $this->createMock(SubscriberRepository::class);
        $subscribers->method('find_by_line_id')->willReturn($existing);

        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')->willReturn(new ChannelSettings('1', 'sec', 'tok'));

        $profiles = $this->createMock(LineProfileFetcher::class);
        $profiles->method('fetch')->willReturn(new LineProfileResult(ok: true));

        $welcome = $this->createMock(WelcomeMessageBuilder::class);
        $welcome->expects($this->never())->method('build');

        $reply_client = $this->createMock(ReplyClient::class);
        $reply_client->expects($this->never())->method('reply');

        $handler = new FollowHandler($subscribers, $channel, $profiles, $welcome, $reply_client);
        $handler->handle('U123', 1_715_000_000, 'rt-abc');
    }
}
