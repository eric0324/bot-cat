<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Site-wide KPIs surfaced on the Dashboard top page.
 *
 * Each method runs a single SUM/COUNT against an indexed range so
 * the dashboard hits the <2s budget on 10k log rows.
 */
class DashboardKpiCalculator {

	public function active_subscribers(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'botcat_subscribers WHERE status = %s',
				'active'
			)
		);
	}

	public function deliveries_in_window( AnalyticsWindow $window ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'botcat_push_logs WHERE is_test = 0 AND status = %s AND created_at BETWEEN %s AND %s',
				'sent',
				$window->start_gmt,
				$window->end_gmt
			)
		);
	}

	public function clicks_in_window( AnalyticsWindow $window ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'botcat_short_link_clicks WHERE clicked_at BETWEEN %s AND %s',
				$window->start_gmt,
				$window->end_gmt
			)
		);
	}

	public function ctr_percent( AnalyticsWindow $window ): float {
		$deliveries = $this->deliveries_in_window( $window );
		if ( $deliveries === 0 ) {
			return 0.0;
		}
		return round( $this->clicks_in_window( $window ) * 100 / $deliveries, 1 );
	}

	public function median_click_latency_seconds( AnalyticsWindow $window ): ?int {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT TIMESTAMPDIFF(SECOND, j.triggered_at, c.clicked_at) AS dt '
				. 'FROM ' . $wpdb->prefix . 'botcat_short_link_clicks AS c '
				. 'INNER JOIN ' . $wpdb->prefix . 'botcat_short_links AS l ON l.id = c.short_link_id '
				. 'INNER JOIN ' . $wpdb->prefix . 'botcat_push_jobs AS j ON j.post_id = l.post_id '
				. 'WHERE c.clicked_at BETWEEN %s AND %s AND j.triggered_at IS NOT NULL '
				. 'ORDER BY dt',
				$window->start_gmt,
				$window->end_gmt
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) || $rows === array() ) {
			return null;
		}

		$values = array_map( static fn( array $r ): int => (int) ( $r['dt'] ?? 0 ), $rows );
		sort( $values );
		$mid = (int) ( count( $values ) / 2 );
		return (int) $values[ $mid ];
	}
}
