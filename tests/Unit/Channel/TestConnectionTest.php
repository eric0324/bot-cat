<?php
/**
 * @package BotCat\Tests\Unit\Channel
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Channel;

use BotCat\Channel\ChannelSettings;
use BotCat\Channel\TestConnection;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Test-connection action verifies the credentials.
 */
final class TestConnectionTest extends TestCase
{
    public function testSuccessfulConnectionReturnsDisplayName(): void
    {
        Functions\expect('wp_remote_get')->once()
            ->with(
                'https://api.line.me/v2/bot/info',
                \Mockery::on(function (array $args): bool {
                    return isset($args['headers']['Authorization'])
                        && $args['headers']['Authorization'] === 'Bearer tok'
                        && ($args['timeout'] ?? 0) >= 5;
                })
            )
            ->andReturn(['mock-response']);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')
            ->andReturn('{"displayName":"My Cat OA","basicId":"@cat","userId":"U123"}');

        $tester = new TestConnection();
        $result = $tester->run(new ChannelSettings('1', 'sec', 'tok'));

        $this->assertTrue($result->ok);
        $this->assertSame('My Cat OA', $result->display_name);
    }

    public function testInvalidTokenSurfacesUpstreamError(): void
    {
        Functions\expect('wp_remote_get')->once()->andReturn(['mock-response']);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(401);
        Functions\expect('wp_remote_retrieve_body')
            ->andReturn('{"message":"Authentication failed"}');

        $tester = new TestConnection();
        $result = $tester->run(new ChannelSettings('1', 'sec', 'bad-tok'));

        $this->assertFalse($result->ok);
        $this->assertSame(401, $result->http_status);
        $this->assertStringContainsString('Authentication failed', $result->error_message);
    }

    public function testNetworkFailureDegradesGracefully(): void
    {
        $wp_error = new \stdClass();

        Functions\expect('wp_remote_get')->once()->andReturn($wp_error);
        Functions\expect('is_wp_error')->once()->with($wp_error)->andReturn(true);
        Functions\expect('wp_remote_retrieve_response_code')->never();

        $tester = new TestConnection();
        $result = $tester->run(new ChannelSettings('1', 'sec', 'tok'));

        $this->assertFalse($result->ok);
        $this->assertNull($result->http_status);
        $this->assertSame('network_error', $result->error_code);
    }

    public function testUnconfiguredSettingsShortCircuitWithoutHttpCall(): void
    {
        Functions\expect('wp_remote_get')->never();

        $tester = new TestConnection();
        $result = $tester->run(new ChannelSettings('', '', ''));

        $this->assertFalse($result->ok);
        $this->assertSame('not_configured', $result->error_code);
    }
}
