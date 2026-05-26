<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Computes a daily series of net active subscribers across the window.
 *
 * Naive approach (acceptable for ≤90-day windows): one COUNT per day.
 * For higher volumes the data layer can swap in a materialized
 * snapshot table.
 */
class SubscriberGrowthQuery {

	/**
	 * @return list<array{date:string, active:int}>
	 */
	public function run( AnalyticsWindow $window ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'botcat_subscribers';

		$series = array();
		$end_ts = strtotime( $window->end_gmt );
		if ( $end_ts === false ) {
			return $series;
		}

		for ( $day = 0; $day < $window->days; $day++ ) {
			$cursor    = gmdate( 'Y-m-d 23:59:59', $end_ts - ( $window->days - 1 - $day ) * 86400 );
			$day_label = gmdate( 'Y-m-d', $end_ts - ( $window->days - 1 - $day ) * 86400 );

			$count = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} "
					. 'WHERE followed_at IS NOT NULL AND followed_at <= %s '
					. 'AND (unfollowed_at IS NULL OR unfollowed_at > %s)',
					$cursor,
					$cursor
				)
			);

			$series[] = array(
				'date'   => $day_label,
				'active' => $count,
			);
		}

		return $series;
	}
}
