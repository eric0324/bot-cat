<?php
/**
 * @package BotCat\Tests\Unit\Rules
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Rules;

use BotCat\Rules\PerPostOptOut;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

final class PerPostOptOutTest extends TestCase
{
    public function testDefaultForExistingPostFollowsGlobalSetting(): void
    {
        Functions\expect('get_post_meta')->with(42, PerPostOptOut::META_KEY, true)->andReturn('');
        Functions\expect('get_option')->with(PerPostOptOut::DEFAULT_OPTION, true)->andReturn(true);

        $this->assertTrue((new PerPostOptOut())->is_opted_in(42));
    }

    public function testGlobalDefaultOffMakesOptOut(): void
    {
        Functions\expect('get_post_meta')->andReturn('');
        Functions\expect('get_option')->andReturn(false);

        $this->assertFalse((new PerPostOptOut())->is_opted_in(42));
    }

    public function testMetaExplicitlyOneMeansOptedIn(): void
    {
        Functions\expect('get_post_meta')->andReturn('1');

        $this->assertTrue((new PerPostOptOut())->is_opted_in(42));
    }

    public function testMetaExplicitlyZeroMeansOptedOutRegardlessOfGlobal(): void
    {
        Functions\expect('get_post_meta')->andReturn('0');

        $this->assertFalse((new PerPostOptOut())->is_opted_in(42));
    }

    public function testSaveForPostWritesOneOrZero(): void
    {
        Functions\expect('update_post_meta')->once()
            ->with(42, PerPostOptOut::META_KEY, '1');

        (new PerPostOptOut())->save_for_post(42, true);

        $this->assertTrue(true);
    }

    public function testSaveForPostFalseWritesZero(): void
    {
        Functions\expect('update_post_meta')->once()
            ->with(42, PerPostOptOut::META_KEY, '0');

        (new PerPostOptOut())->save_for_post(42, false);

        $this->assertTrue(true);
    }
}
