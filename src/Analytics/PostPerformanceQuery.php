<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Builds the per-post performance table for the analytics dashboard.
 *
 * Returns rows ordered by CTR DESC (per spec) so the highest-engagement
 * posts surface first.
 */
class PostPerformanceQuery {

	/**
	 * @return list<array{post_id:int, post_title:string, triggered_at:string, delivered:int, failed:int, clicks:int, ctr:float}>
	 */
	public function run( AnalyticsWindow $window, int $limit = 100 ): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			'SELECT j.post_id, j.triggered_at, j.sent_count AS delivered, j.failed_count AS failed, '
			. '(SELECT COUNT(*) FROM ' . $wpdb->prefix . 'botcat_short_link_clicks AS c '
			. 'INNER JOIN ' . $wpdb->prefix . 'botcat_short_links AS l ON l.id = c.short_link_id '
			. 'WHERE l.post_id = j.post_id AND c.clicked_at BETWEEN %s AND %s) AS clicks '
			. 'FROM ' . $wpdb->prefix . 'botcat_push_jobs AS j '
			. 'WHERE j.is_test = 0 AND j.triggered_at BETWEEN %s AND %s '
			. 'ORDER BY j.triggered_at DESC LIMIT %d',
			$window->start_gmt,
			$window->end_gmt,
			$window->start_gmt,
			$window->end_gmt,
			$limit
		);

		$rows = $wpdb->get_results( $sql, ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$out = array();
		foreach ( $rows as $row ) {
			$delivered = (int) ( $row['delivered'] ?? 0 );
			$clicks    = (int) ( $row['clicks'] ?? 0 );
			$ctr       = $delivered > 0 ? round( $clicks * 100 / $delivered, 1 ) : 0.0;

			$out[] = array(
				'post_id'      => (int) ( $row['post_id'] ?? 0 ),
				'post_title'   => $row['post_id'] !== null ? (string) get_the_title( (int) $row['post_id'] ) : '',
				'triggered_at' => (string) ( $row['triggered_at'] ?? '' ),
				'delivered'    => $delivered,
				'failed'       => (int) ( $row['failed'] ?? 0 ),
				'clicks'       => $clicks,
				'ctr'          => $ctr,
			);
		}

		// Default sort: CTR descending (spec scenario).
		usort( $out, static fn( array $a, array $b ): int => $b['ctr'] <=> $a['ctr'] );

		return $out;
	}
}
