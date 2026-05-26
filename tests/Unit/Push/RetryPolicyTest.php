<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\MulticastClient;
use BotCat\Push\MulticastResult;
use BotCat\Push\RetryDecision;
use BotCat\Push\RetryPolicy;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Failure retry with exponential backoff
 * AND Requirement: Respect LINE rate limits.
 */
final class RetryPolicyTest extends TestCase
{
    public function testSuccessReturnsDone(): void
    {
        $policy   = new RetryPolicy();
        $decision = $policy->decide(1, new MulticastResult(ok: true, http_status: 200));

        $this->assertSame(RetryDecision::ACTION_DONE, $decision->action);
    }

    public function testFiveHundredAttemptOneRetriesIn30Seconds(): void
    {
        $decision = (new RetryPolicy())->decide(1, $this->server_error(502));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(30, $decision->delay_seconds);
    }

    public function testFiveHundredAttemptTwoRetriesIn300Seconds(): void
    {
        $decision = (new RetryPolicy())->decide(2, $this->server_error(502));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(300, $decision->delay_seconds);
    }

    public function testFiveHundredAttemptThreeRetriesIn1800Seconds(): void
    {
        $decision = (new RetryPolicy())->decide(3, $this->server_error(502));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(1800, $decision->delay_seconds);
    }

    public function testFiveHundredAfterFourthAttemptIsFailed(): void
    {
        $decision = (new RetryPolicy())->decide(4, $this->server_error(503, 'Service Unavailable'));

        $this->assertSame(RetryDecision::ACTION_FAIL, $decision->action);
        $this->assertSame('Service Unavailable', $decision->error);
    }

    public function testFourOhOneImmediatelyAbortsTheJob(): void
    {
        $decision = (new RetryPolicy())->decide(1, new MulticastResult(
            ok: false,
            http_status: 401,
            error_code: MulticastClient::ERROR_AUTH,
            error_message: 'Authentication failed'
        ));

        $this->assertSame(RetryDecision::ACTION_ABORT_AUTH, $decision->action);
        $this->assertSame('Authentication failed', $decision->error);
    }

    public function testFourHundredErrorFailsWithoutRetry(): void
    {
        $decision = (new RetryPolicy())->decide(1, new MulticastResult(
            ok: false,
            http_status: 400,
            error_code: MulticastClient::ERROR_CLIENT,
            error_message: 'Invalid user ID'
        ));

        $this->assertSame(RetryDecision::ACTION_FAIL, $decision->action);
    }

    public function testFourTwentyNineHonorsRetryAfterHeader(): void
    {
        $decision = (new RetryPolicy())->decide(1, new MulticastResult(
            ok: false,
            http_status: 429,
            retry_after: 120,
            error_code: MulticastClient::ERROR_RATE_LIMITED
        ));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(120, $decision->delay_seconds);
    }

    public function testFourTwentyNineWithoutRetryAfterFallsBackToBackoff(): void
    {
        $decision = (new RetryPolicy())->decide(1, new MulticastResult(
            ok: false,
            http_status: 429,
            error_code: MulticastClient::ERROR_RATE_LIMITED
        ));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(30, $decision->delay_seconds);
    }

    public function testNetworkErrorIsRetriedLikeServerError(): void
    {
        $decision = (new RetryPolicy())->decide(1, new MulticastResult(
            ok: false,
            error_code: MulticastClient::ERROR_NETWORK
        ));

        $this->assertSame(RetryDecision::ACTION_RETRY, $decision->action);
        $this->assertSame(30, $decision->delay_seconds);
    }

    private function server_error(int $code, string $message = 'Bad Gateway'): MulticastResult
    {
        return new MulticastResult(
            ok: false,
            http_status: $code,
            error_code: MulticastClient::ERROR_SERVER,
            error_message: $message
        );
    }
}
