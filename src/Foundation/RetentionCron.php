<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Daily job that prunes old `botcat_push_logs` rows.
 *
 * Retention window defaults to 30 days and is filterable via
 * `botcat_log_retention_days`.
 */
final class RetentionCron {

	public const FILTER_DAYS  = 'botcat_log_retention_days';
	public const DEFAULT_DAYS = 30;

	public function run(): void {
		global $wpdb;

		$days = (int) apply_filters( self::FILTER_DAYS, self::DEFAULT_DAYS );
		if ( $days <= 0 ) {
			return;
		}

		$table = $wpdb->prefix . 'botcat_push_logs';
		$sql   = $wpdb->prepare(
			"DELETE FROM {$table} WHERE created_at < DATE_SUB( UTC_TIMESTAMP(), INTERVAL %d DAY )",
			$days
		);
		$wpdb->query( $sql );
	}
}
