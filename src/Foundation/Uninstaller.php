<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Permanently removes every botcat_* option, table, and scheduled job.
 */
final class Uninstaller {

	/**
	 * Options touched by the plugin. Keep this list authoritative — anything
	 * the plugin writes with option_name starting `botcat_` SHALL be listed
	 * here so `uninstall` is a true clean slate.
	 */
	public const OPTIONS = array(
		SchemaVersion::OPTION,
		'botcat_channel_settings',
	);

	public static function uninstall(): void {
		global $wpdb;

		$schema = new Schema();
		foreach ( $schema->tables() as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		foreach ( self::OPTIONS as $option ) {
			delete_option( $option );
		}

		wp_clear_scheduled_hook( Activator::CRON_HOOK );
	}
}
