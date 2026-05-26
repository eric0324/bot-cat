<?php
/**
 * bot-cat uninstall script.
 *
 * Fires when an administrator deletes the plugin from the WordPress admin.
 * Removes every botcat_* option, table, and scheduled job.
 *
 * @package BotCat
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

\BotCat\Foundation\Uninstaller::uninstall();
