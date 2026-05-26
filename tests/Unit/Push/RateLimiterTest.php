<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\RateLimiter;
use BotCat\Tests\TestCase;

/**
 * Covers Requirement: Respect LINE rate limits — at most 30 multicast
 * requests per rolling 60-second window.
 */
final class RateLimiterTest extends TestCase
{
    public function testFirstBatchHasNoDelay(): void
    {
        $base = 1_700_000_000;
        $this->assertSame($base, (new RateLimiter($base))->slot_for(0));
    }

    public function testSecondBatchIsTwoSecondsAfterFirst(): void
    {
        $base = 1_700_000_000;
        $limiter = new RateLimiter($base);

        $this->assertSame($base + 2, $limiter->slot_for(1));
    }

    public function testThirtyFirstBatchSitsAtSixtyOneSecondsToKeepBelowThirtyPerMinute(): void
    {
        $base = 1_700_000_000;
        $limiter = new RateLimiter($base);

        $slot = $limiter->slot_for(30);

        $this->assertGreaterThanOrEqual($base + 60, $slot, '30 requests cannot fit inside the first 60s window');
    }
}
