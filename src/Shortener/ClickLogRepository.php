<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Append-only store of `botcat_short_link_clicks` rows.
 *
 * Raw IP is never written — callers pass a pre-computed hash. UA and
 * referer are truncated to 255 chars to stay within the DDL.
 */
class ClickLogRepository {

	public const TABLE        = 'botcat_short_link_clicks';
	public const STRING_LIMIT = 255;

	public function log(
		int $short_link_id,
		?int $subscriber_id,
		string $ip_hash,
		?string $user_agent,
		?string $referer
	): void {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'short_link_id' => $short_link_id,
				'subscriber_id' => $subscriber_id,
				'ip_hash'       => $ip_hash,
				'user_agent'    => $this->truncate( $user_agent ),
				'referer'       => $this->truncate( $referer ),
				'clicked_at'    => gmdate( 'Y-m-d H:i:s' ),
			),
			array( '%d', $subscriber_id === null ? '%s' : '%d', '%s', '%s', '%s', '%s' )
		);
	}

	public function count_total( int $short_link_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . $wpdb->prefix . self::TABLE . ' WHERE short_link_id = %d',
				$short_link_id
			)
		);
	}

	public function count_unique( int $short_link_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(DISTINCT ip_hash) FROM ' . $wpdb->prefix . self::TABLE . ' WHERE short_link_id = %d',
				$short_link_id
			)
		);
	}

	private function truncate( ?string $value ): ?string {
		if ( $value === null ) {
			return null;
		}
		return mb_substr( $value, 0, self::STRING_LIMIT, 'UTF-8' );
	}
}
