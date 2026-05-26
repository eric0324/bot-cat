<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\ChannelSettings;
use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\WebhookEndpoint;
use BotCat\Subscribers\FollowHandler;
use BotCat\Subscribers\UnfollowHandler;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Public webhook endpoint registration AND
 * Requirement: Signature verification on every webhook hit.
 */
final class WebhookEndpointTest extends TestCase
{
    public function testRegistersPublicPostRouteUnderBotcatV1Namespace(): void
    {
        $captured = [];
        Functions\expect('register_rest_route')->once()
            ->andReturnUsing(function (...$args) use (&$captured) {
                $captured = $args;
                return true;
            });

        $endpoint = $this->build_endpoint();
        $endpoint->register();

        $this->assertSame('botcat/v1', $captured[0]);
        $this->assertSame('/webhook', $captured[1]);
        $this->assertSame('POST', $captured[2]['methods']);
        $this->assertIsCallable($captured[2]['callback']);
        $this->assertIsCallable($captured[2]['permission_callback']);
        $this->assertTrue(($captured[2]['permission_callback'])(), 'webhook MUST be publicly callable');
    }

    public function testMissingSignatureIsRejectedWithoutInvokingHandlers(): void
    {
        $follow   = $this->createMock(FollowHandler::class);
        $unfollow = $this->createMock(UnfollowHandler::class);
        $follow->expects($this->never())->method('handle');
        $unfollow->expects($this->never())->method('handle');

        $endpoint = $this->build_endpoint($follow, $unfollow, channel_secret: 'sec');
        $status   = $endpoint->handle_request('{"events":[]}', null);

        $this->assertSame(403, $status);
    }

    public function testTamperedBodyIsRejected(): void
    {
        $follow   = $this->createMock(FollowHandler::class);
        $unfollow = $this->createMock(UnfollowHandler::class);
        $follow->expects($this->never())->method('handle');
        $unfollow->expects($this->never())->method('handle');

        $body = '{"events":[{"type":"follow"}]}';
        $sig  = base64_encode(hash_hmac('sha256', $body, 'sec', true));

        $endpoint = $this->build_endpoint($follow, $unfollow, channel_secret: 'sec');
        $status   = $endpoint->handle_request('{"events":[{"type":"unfollow"}]}', $sig);

        $this->assertSame(403, $status);
    }

    public function testValidFollowEventDispatchesToFollowHandler(): void
    {
        $follow   = $this->createMock(FollowHandler::class);
        $unfollow = $this->createMock(UnfollowHandler::class);
        $follow->expects($this->once())->method('handle')
            ->with($this->equalTo('U123'), $this->equalTo(1_715_000_000));
        $unfollow->expects($this->never())->method('handle');

        $body = json_encode([
            'events' => [[
                'type'      => 'follow',
                'timestamp' => 1_715_000_000_000,
                'source'    => ['userId' => 'U123'],
            ]],
        ]);
        $sig = base64_encode(hash_hmac('sha256', $body, 'sec', true));

        $endpoint = $this->build_endpoint($follow, $unfollow, channel_secret: 'sec');
        $status   = $endpoint->handle_request($body, $sig);

        $this->assertSame(200, $status);
    }

    public function testValidUnfollowEventDispatchesToUnfollowHandler(): void
    {
        $follow   = $this->createMock(FollowHandler::class);
        $unfollow = $this->createMock(UnfollowHandler::class);
        $unfollow->expects($this->once())->method('handle')
            ->with($this->equalTo('U456'), $this->isType('int'));
        $follow->expects($this->never())->method('handle');

        $body = json_encode([
            'events' => [[
                'type'      => 'unfollow',
                'timestamp' => 1_715_000_000_000,
                'source'    => ['userId' => 'U456'],
            ]],
        ]);
        $sig = base64_encode(hash_hmac('sha256', $body, 'sec', true));

        $endpoint = $this->build_endpoint($follow, $unfollow, channel_secret: 'sec');
        $status   = $endpoint->handle_request($body, $sig);

        $this->assertSame(200, $status);
    }

    public function testUnconfiguredChannelStillReturns200ButHandlesNothing(): void
    {
        $follow   = $this->createMock(FollowHandler::class);
        $unfollow = $this->createMock(UnfollowHandler::class);
        $follow->expects($this->never())->method('handle');
        $unfollow->expects($this->never())->method('handle');

        $endpoint = $this->build_endpoint($follow, $unfollow, channel_secret: '');
        $status   = $endpoint->handle_request('{"events":[]}', 'any-sig');

        $this->assertSame(200, $status);
    }

    private function build_endpoint(
        ?FollowHandler $follow = null,
        ?UnfollowHandler $unfollow = null,
        string $channel_secret = 'sec'
    ): WebhookEndpoint {
        $channel = $this->createMock(ChannelSettingsRepository::class);
        $channel->method('get')
            ->willReturn(new ChannelSettings('1', $channel_secret, 'tok'));

        return new WebhookEndpoint(
            $channel,
            $follow ?? $this->createMock(FollowHandler::class),
            $unfollow ?? $this->createMock(UnfollowHandler::class)
        );
    }
}
