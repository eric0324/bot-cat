<?php
/**
 * @package BotCat\Tests\Unit\Analytics
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Analytics;

use BotCat\Analytics\AnalyticsWindow;
use BotCat\Tests\TestCase;

final class AnalyticsWindowTest extends TestCase
{
    public function testDefaultDaysIsThirty(): void
    {
        $this->assertSame(30, (new AnalyticsWindow(0))->days);
        $this->assertSame(30, (new AnalyticsWindow(123))->days);
    }

    public function testAcceptsSeven(): void
    {
        $this->assertSame(7, (new AnalyticsWindow(7))->days);
    }

    public function testStartEndAreSpacedByDays(): void
    {
        $now    = 1_700_000_000;
        $window = new AnalyticsWindow(7, $now);

        $this->assertSame(gmdate('Y-m-d H:i:s', $now), $window->end_gmt);
        $this->assertSame(gmdate('Y-m-d H:i:s', $now - 7 * 86400), $window->start_gmt);
    }
}
