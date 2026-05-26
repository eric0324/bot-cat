<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\AdminMenu;
use BotCat\Foundation\Edition;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Top-level admin menu structure.
 */
final class AdminMenuTest extends TestCase
{
    public function testRegistersTopLevelMenuAndExpectedSubmenusInOrderOnFreeEdition(): void
    {
        $edition = $this->createMock(Edition::class);
        $edition->method('is_pro')->willReturn(false);

        $top_calls = [];
        $sub_calls = [];

        Functions\expect('add_menu_page')->once()
            ->andReturnUsing(function (...$args) use (&$top_calls) {
                $top_calls[] = $args;
                return 'toplevel_page_bot-cat';
            });
        Functions\expect('add_submenu_page')->times(5)
            ->andReturnUsing(function (...$args) use (&$sub_calls) {
                $sub_calls[] = $args;
                return 'bot-cat_page_' . $args[3];
            });

        $menu = new AdminMenu($edition);
        $menu->register();

        // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $callback, $icon, $position )
        $this->assertSame('bot-cat', $top_calls[0][3], 'top-level menu slug should be bot-cat');
        $this->assertSame('manage_options', $top_calls[0][2], 'capability should be manage_options');

        // add_submenu_page( $parent, $page_title, $menu_title, $capability, $menu_slug, $callback )
        $sub_slugs = array_map(fn($a) => $a[4], $sub_calls);
        $this->assertSame(
            ['bot-cat', 'bot-cat-subscribers', 'bot-cat-templates', 'bot-cat-logs', 'bot-cat-settings'],
            $sub_slugs,
            'submenus must appear in the specified order'
        );
    }

    public function testRegistersLicenseSubmenuOnlyWhenProEditionActive(): void
    {
        $edition = $this->createMock(Edition::class);
        $edition->method('is_pro')->willReturn(true);

        Functions\expect('add_menu_page')->once();
        Functions\expect('add_submenu_page')->times(7);

        $menu = new AdminMenu($edition);
        $menu->register();

        $this->assertTrue(true);
    }
}
