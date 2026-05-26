<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Deactivator;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Scenario: Deactivation preserves user data.
 */
final class DeactivatorTest extends TestCase
{
    public function testDeactivationClearsBotcatCronEventsButPreservesData(): void
    {
        Functions\expect('wp_next_scheduled')
            ->with('botcat_cleanup_old_logs')
            ->andReturn(1_700_000_000);
        Functions\expect('wp_unschedule_event')->once()
            ->with(1_700_000_000, 'botcat_cleanup_old_logs');

        Deactivator::deactivate();

        $this->assertTrue(true);
    }

    public function testDeactivationSkipsUnscheduledCron(): void
    {
        Functions\expect('wp_next_scheduled')
            ->with('botcat_cleanup_old_logs')
            ->andReturn(false);
        Functions\expect('wp_unschedule_event')->never();

        Deactivator::deactivate();

        $this->assertTrue(true);
    }
}
