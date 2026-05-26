<?php
/**
 * @package BotCat\Tests\Unit\License
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\License;

use BotCat\License\LicenseCache;
use BotCat\License\LicenseGate;
use BotCat\License\LicenseRepository;
use BotCat\License\LicenseStatusEvaluator;
use BotCat\Tests\TestCase;

final class LicenseGateTest extends TestCase
{
    public function testReturnsTrueWhenStatusIsActive(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(true, $now - 86400, null, 0, 'Pro', null);

        $repo = $this->createMock(LicenseRepository::class);
        $repo->method('get_cache')->willReturn($cache);

        $gate = new LicenseGate($repo, new LicenseStatusEvaluator(), fn() => $now);
        $this->assertTrue($gate->is_pro_active());
    }

    public function testReturnsFalseWhenNeverActivated(): void
    {
        $repo = $this->createMock(LicenseRepository::class);
        $repo->method('get_cache')->willReturn(LicenseCache::empty());

        $gate = new LicenseGate($repo, new LicenseStatusEvaluator(), fn() => 1_700_000_000);
        $this->assertFalse($gate->is_pro_active());
    }

    public function testReturnsFalseWhenExpiredBeyondGrace(): void
    {
        $now   = 1_700_000_000;
        $cache = new LicenseCache(true, $now - 9 * 86400, null, 5, 'Pro', null);

        $repo = $this->createMock(LicenseRepository::class);
        $repo->method('get_cache')->willReturn($cache);

        $gate = new LicenseGate($repo, new LicenseStatusEvaluator(), fn() => $now);
        $this->assertFalse($gate->is_pro_active());
    }

    public function testFilterCallbackOrsExistingValue(): void
    {
        $now   = 1_700_000_000;
        $repo  = $this->createMock(LicenseRepository::class);
        $repo->method('get_cache')->willReturn(LicenseCache::empty());

        $gate = new LicenseGate($repo, new LicenseStatusEvaluator(), fn() => $now);

        // No license + filter starts true (e.g. dev override) → still true.
        $this->assertTrue($gate->filter_is_pro(true));
        // No license + filter starts false → false.
        $this->assertFalse($gate->filter_is_pro(false));
    }
}
