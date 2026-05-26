<?php
/**
 * @package BotCat\Tests\Unit\Rules
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Rules;

use BotCat\Rules\PushThrottle;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class PushThrottleTest extends TestCase
{
    public function testZeroIntervalReturnsCurrentTime(): void
    {
        Functions\when('get_option')->alias(static fn($name, $default) => $name === PushThrottle::OPTION_INTERVAL_MIN ? 0 : $default);

        $slot = (new PushThrottle())->next_slot(now: 1_000);

        $this->assertSame(1_000, $slot);
    }

    public function testThrottleDelaysSubsequentPushesByIntervalSeconds(): void
    {
        Functions\when('get_option')->alias(static fn($name, $default) => match ($name) {
            PushThrottle::OPTION_INTERVAL_MIN    => 30,
            PushThrottle::OPTION_NEXT_ALLOWED_AT => 0,
            default                              => $default,
        });
        Functions\expect('update_option')->with(PushThrottle::OPTION_NEXT_ALLOWED_AT, $this->isType('int'));

        $slot = (new PushThrottle())->next_slot(now: 1_000);

        $this->assertSame(1_000, $slot, 'first push when no prior slot stored fires immediately');
    }

    public function testSecondPushWithinWindowIsPushedToWindowEnd(): void
    {
        Functions\when('get_option')->alias(static fn($name, $default) => match ($name) {
            PushThrottle::OPTION_INTERVAL_MIN    => 30,
            PushThrottle::OPTION_NEXT_ALLOWED_AT => 1_300,
            default                              => $default,
        });
        $stored = null;
        Functions\expect('update_option')
            ->with(PushThrottle::OPTION_NEXT_ALLOWED_AT, \Mockery::on(function ($v) use (&$stored) {
                $stored = $v;
                return true;
            }));

        $slot = (new PushThrottle())->next_slot(now: 1_100);

        $this->assertSame(1_300, $slot);
        $this->assertSame(1_300 + 30 * 60, $stored, 'next allowed slot must move forward by one interval');
    }

    public function testThirdPushCascadesAfterSecond(): void
    {
        $interval_sec = 30 * 60;
        Functions\when('get_option')->alias(static fn($name, $default) => match ($name) {
            PushThrottle::OPTION_INTERVAL_MIN    => 30,
            PushThrottle::OPTION_NEXT_ALLOWED_AT => 1_000 + $interval_sec,
            default                              => $default,
        });
        Functions\expect('update_option');

        $slot = (new PushThrottle())->next_slot(now: 1_400);

        $this->assertSame(1_000 + $interval_sec, $slot);
    }
}
