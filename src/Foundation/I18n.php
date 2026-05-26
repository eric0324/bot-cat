<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Loads the plugin's translation catalogs.
 *
 * `load()` is wired to `init` by Plugin::register_hooks().
 */
final class I18n {

	public function __construct( private readonly string $plugin_file ) {
	}

	public function load(): void {
		load_plugin_textdomain(
			'bot-cat',
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
		);
	}
}
