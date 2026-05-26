<?php
/**
 * @package BotCat\Tests\Unit\Foundation
 */

declare(strict_types=1);

namespace BotCat\Tests\Unit\Foundation;

use BotCat\Foundation\I18n;
use BotCat\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Covers Requirement: Internationalization scaffolding (loader part).
 */
final class I18nTest extends TestCase
{
    public function testLoadsTextDomainFromLanguagesDirectory(): void
    {
        Functions\expect('load_plugin_textdomain')->once()
            ->with('bot-cat', false, 'bot-cat/languages');

        Functions\stubs([
            'plugin_basename' => static fn($path) => 'bot-cat/' . basename($path),
        ]);

        $i18n = new I18n('/wp-content/plugins/bot-cat/bot-cat.php');
        $i18n->load();

        $this->assertTrue(true);
    }
}
