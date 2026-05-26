<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\Edition;
use BotCat\Tests\TestCase;
use Brain\Monkey\Filters;

/**
 * Edition reports whether the running build is Free or Pro.
 *
 * The Free distribution MUST NOT bundle Pro code (per license-management spec)
 * — this test guards the public contract used by AdminMenu and (later) the
 * LicenseGate.
 */
final class EditionTest extends TestCase
{
    public function testDefaultEditionIsFree(): void
    {
        Filters\expectApplied('botcat_is_pro')->once()->with(false)->andReturn(false);

        $edition = new Edition();

        $this->assertFalse($edition->is_pro());
    }

    public function testProBuildFlipsViaFilter(): void
    {
        Filters\expectApplied('botcat_is_pro')->once()->with(false)->andReturn(true);

        $edition = new Edition();

        $this->assertTrue($edition->is_pro());
    }
}
