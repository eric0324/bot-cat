<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\MulticastClient;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class MulticastClientTest extends TestCase
{
    public function testSendPostsToCorrectEndpointWithBearerAuthAndJsonBody(): void
    {
        $captured_args = null;
        Functions\expect('wp_remote_post')->once()
            ->andReturnUsing(function (string $url, array $args) use (&$captured_args) {
                $captured_args = ['url' => $url, 'args' => $args];
                return ['ok'];
            });
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(200);
        Functions\expect('wp_remote_retrieve_body')->andReturn('{}');
        Functions\expect('wp_remote_retrieve_header')->andReturn('');

        $client = new MulticastClient();
        $result = $client->send('tok', ['U1', 'U2'], [['type' => 'text', 'text' => 'Hi']]);

        $this->assertTrue($result->ok);
        $this->assertSame(200, $result->http_status);

        $this->assertSame(MulticastClient::ENDPOINT, $captured_args['url']);
        $this->assertSame('Bearer tok', $captured_args['args']['headers']['Authorization']);
        $this->assertSame('application/json', $captured_args['args']['headers']['Content-Type']);

        $body = json_decode((string) $captured_args['args']['body'], true);
        $this->assertSame(['U1', 'U2'], $body['to']);
        $this->assertSame([['type' => 'text', 'text' => 'Hi']], $body['messages']);
    }

    public function testAuthFailureSurfacesAuthErrorCode(): void
    {
        Functions\expect('wp_remote_post')->andReturn(['ok']);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(401);
        Functions\expect('wp_remote_retrieve_body')->andReturn('{"message":"Authentication failed"}');
        Functions\expect('wp_remote_retrieve_header')->andReturn('');

        $result = (new MulticastClient())->send('tok', ['U1'], [['type' => 'text', 'text' => 'hi']]);

        $this->assertFalse($result->ok);
        $this->assertSame(401, $result->http_status);
        $this->assertSame(MulticastClient::ERROR_AUTH, $result->error_code);
        $this->assertSame('Authentication failed', $result->error_message);
    }

    public function testServerErrorIsRetriable(): void
    {
        Functions\expect('wp_remote_post')->andReturn(['ok']);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(502);
        Functions\expect('wp_remote_retrieve_body')->andReturn('Bad Gateway');
        Functions\expect('wp_remote_retrieve_header')->andReturn('');

        $result = (new MulticastClient())->send('tok', ['U1'], [['type' => 'text', 'text' => 'hi']]);

        $this->assertFalse($result->ok);
        $this->assertSame(502, $result->http_status);
        $this->assertSame(MulticastClient::ERROR_SERVER, $result->error_code);
    }

    public function testTooManyRequestsExtractsRetryAfterHeader(): void
    {
        Functions\expect('wp_remote_post')->andReturn(['ok']);
        Functions\expect('is_wp_error')->andReturn(false);
        Functions\expect('wp_remote_retrieve_response_code')->andReturn(429);
        Functions\expect('wp_remote_retrieve_body')->andReturn('Too many');
        Functions\expect('wp_remote_retrieve_header')->once()
            ->with(\Mockery::any(), 'retry-after')
            ->andReturn('120');

        $result = (new MulticastClient())->send('tok', ['U1'], [['type' => 'text', 'text' => 'hi']]);

        $this->assertFalse($result->ok);
        $this->assertSame(429, $result->http_status);
        $this->assertSame(MulticastClient::ERROR_RATE_LIMITED, $result->error_code);
        $this->assertSame(120, $result->retry_after);
    }

    public function testNetworkErrorSurfacesAsErrorCodeWithoutHttpStatus(): void
    {
        $wp_error = new \stdClass();
        Functions\expect('wp_remote_post')->andReturn($wp_error);
        Functions\expect('is_wp_error')->once()->with($wp_error)->andReturn(true);
        Functions\expect('wp_remote_retrieve_response_code')->never();

        $result = (new MulticastClient())->send('tok', ['U1'], [['type' => 'text', 'text' => 'hi']]);

        $this->assertFalse($result->ok);
        $this->assertNull($result->http_status);
        $this->assertSame(MulticastClient::ERROR_NETWORK, $result->error_code);
    }

    public function testEmptyRecipientListShortCircuits(): void
    {
        Functions\expect('wp_remote_post')->never();

        $result = (new MulticastClient())->send('tok', [], [['type' => 'text']]);

        $this->assertFalse($result->ok);
        $this->assertSame(MulticastClient::ERROR_INVALID_INPUT, $result->error_code);
    }
}
