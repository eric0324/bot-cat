<?php
/**
 * @package BotCat\Tests\Unit\Push
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Push;

use BotCat\Push\EligiblePostTypes;
use BotCat\Tests\TestCase;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

final class EligiblePostTypesTest extends TestCase
{
    public function testDefaultIsPostOnly(): void
    {
        Functions\expect('get_option')->once()
            ->with(EligiblePostTypes::OPTION, ['post'])
            ->andReturn(['post']);
        Filters\expectApplied(EligiblePostTypes::FILTER)->once()->andReturnFirstArg();

        $types = (new EligiblePostTypes())->get();

        $this->assertSame(['post'], $types);
    }

    public function testIsEligibleReturnsTrueForListed(): void
    {
        Functions\expect('get_option')->andReturn(['post', 'event']);
        Filters\expectApplied(EligiblePostTypes::FILTER)->andReturnFirstArg();

        $this->assertTrue((new EligiblePostTypes())->is_eligible('event'));
    }

    public function testIsEligibleReturnsFalseForUnlisted(): void
    {
        Functions\expect('get_option')->andReturn(['post']);
        Filters\expectApplied(EligiblePostTypes::FILTER)->andReturnFirstArg();

        $this->assertFalse((new EligiblePostTypes())->is_eligible('page'));
    }

    public function testFilterCanExtendList(): void
    {
        Functions\expect('get_option')->andReturn(['post']);
        Filters\expectApplied(EligiblePostTypes::FILTER)
            ->once()
            ->with(['post'])
            ->andReturn(['post', 'event']);

        $this->assertSame(['post', 'event'], (new EligiblePostTypes())->get());
    }
}
