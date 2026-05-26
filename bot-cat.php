<?php
/**
 * Plugin Name:       bot-cat
 * Plugin URI:        https://bot-cat.com
 * Description:       LINE Official Account push-notification plugin for content sites. Sends a LINE message to your subscribers every time a post is published.
 * Version:           1.0.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            bot-cat
 * Author URI:        https://bot-cat.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       bot-cat
 * Domain Path:       /languages
 *
 * @package BotCat
 */

defined( 'ABSPATH' ) || exit;

if ( defined( 'BOT_CAT_FILE' ) ) {
	return;
}

define( 'BOT_CAT_FILE', __FILE__ );
define( 'BOT_CAT_DIR', plugin_dir_path( __FILE__ ) );
define( 'BOT_CAT_URL', plugin_dir_url( __FILE__ ) );
define( 'BOT_CAT_VERSION', '1.1.0' );
define( 'BOT_CAT_MIN_PHP', '8.1' );
define( 'BOT_CAT_MIN_WP', '7.0' );

if ( ! file_exists( BOT_CAT_DIR . 'vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function () {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html__( 'bot-cat: Composer dependencies are missing. Run "composer install" inside the plugin directory.', 'bot-cat' )
			);
		}
	);
	return;
}

require_once BOT_CAT_DIR . 'vendor/autoload.php';

register_activation_hook( __FILE__, array( \BotCat\Foundation\Activator::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( \BotCat\Foundation\Deactivator::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\BotCat\Foundation\Plugin::boot( BOT_CAT_FILE );
	}
);
