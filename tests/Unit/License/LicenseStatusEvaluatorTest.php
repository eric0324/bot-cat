<?php
/**
 * @package BotCat\Tests\Unit\License
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\License;

use BotCat\License\LicenseCache;
use BotCat\License\LicenseStatus;
use BotCat\License\LicenseStatusEvaluator;
use BotCat\Tests\TestCase;

final class LicenseStatusEvaluatorTest extends TestCase
{
    public function testNeverActivatedReturnsInactive(): void
    {
        $status = (new LicenseStatusEvaluator())->evaluate(LicenseCache::empty(), 1_700_000_000);
        $this->assertSame(LicenseStatus::INACTIVE, $status);
    }

    public function testRecentlyVerifiedEntitledIsActive(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(
            entitled: true,
            verified_at: $now - 86400, // 1 day ago
            last_failure_at: null,
            consecutive_failures: 0,
            plan_name: 'Pro',
            renews_at: '2026-12-01'
        );

        $this->assertSame(LicenseStatus::ACTIVE, (new LicenseStatusEvaluator())->evaluate($cache, $now));
    }

    public function testOutageWithinGracePeriodKeepsActive(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(
            entitled: true,
            verified_at: $now - 3 * 86400, // 3 days ago
            last_failure_at: $now - 86400,
            consecutive_failures: 1,
            plan_name: 'Pro',
            renews_at: null
        );

        $this->assertSame(LicenseStatus::ACTIVE, (new LicenseStatusEvaluator())->evaluate($cache, $now));
    }

    public function testOutageBeyondGracePeriodExpires(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(
            entitled: true,
            verified_at: $now - 8 * 86400, // 8 days ago
            last_failure_at: $now - 86400,
            consecutive_failures: 6,
            plan_name: 'Pro',
            renews_at: null
        );

        $this->assertSame(LicenseStatus::EXPIRED, (new LicenseStatusEvaluator())->evaluate($cache, $now));
    }

    public function testRevokedLicenseFlowsToExpiredAfterGrace(): void
    {
        $now   = 1_700_000_000;
        // Once Polar starts saying entitled=false, verified_at stops advancing.
        // After 7 days, the cache crosses the grace threshold → EXPIRED.
        $cache = new LicenseCache(
            entitled: false,
            verified_at: $now - 8 * 86400,
            last_failure_at: $now,
            consecutive_failures: 7,
            plan_name: 'Pro',
            renews_at: null
        );

        $this->assertSame(LicenseStatus::EXPIRED, (new LicenseStatusEvaluator())->evaluate($cache, $now));
    }

    public function testWithinGracePeriodWindowEdgeCase(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(
            entitled: true,
            verified_at: $now - LicenseStatusEvaluator::GRACE_SECONDS,
            last_failure_at: null,
            consecutive_failures: 0,
            plan_name: 'Pro',
            renews_at: null
        );

        $this->assertSame(LicenseStatus::ACTIVE, (new LicenseStatusEvaluator())->evaluate($cache, $now));
    }
}
