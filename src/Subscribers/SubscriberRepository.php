<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

use Generator;

/**
 * Data access for `botcat_subscribers`.
 *
 * Read methods stream in pages to keep memory bounded — required by the
 * subscriber-management spec (10 000 subscribers, <8 MB peak growth).
 */
class SubscriberRepository {

	public const TABLE      = 'botcat_subscribers';
	public const CHUNK_SIZE = 500;

	public function find_by_line_id( string $line_user_id ): ?Subscriber {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$sql = $wpdb->prepare(
			"SELECT id, line_user_id, display_name, picture_url, status, followed_at, unfollowed_at FROM {$table} WHERE line_user_id = %s LIMIT 1",
			$line_user_id
		);

		$row = $wpdb->get_row( $sql, ARRAY_A );
		if ( ! is_array( $row ) || $row === array() ) {
			return null;
		}

		return Subscriber::from_row( $row );
	}

	public function count_active(): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE status = %s",
			Subscriber::STATUS_ACTIVE
		);

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Stream every active subscriber's LINE user id.
	 *
	 * @return Generator<string>
	 */
	public function active_ids(): Generator {
		global $wpdb;
		$table  = $wpdb->prefix . self::TABLE;
		$offset = 0;

		do {
			$sql = $wpdb->prepare(
				"SELECT line_user_id FROM {$table} WHERE status = %s ORDER BY id LIMIT %d OFFSET %d",
				Subscriber::STATUS_ACTIVE,
				self::CHUNK_SIZE,
				$offset
			);

			$rows = $wpdb->get_results( $sql, ARRAY_A );
			if ( ! is_array( $rows ) ) {
				$rows = array();
			}

			foreach ( $rows as $row ) {
				yield (string) ( $row['line_user_id'] ?? '' );
			}

			$offset += self::CHUNK_SIZE;
		} while ( count( $rows ) === self::CHUNK_SIZE );
	}

	/**
	 * Tag-aware variant. For W1 (free) it falls back to all active ids;
	 * tag-segmentation (Pro / W4) overrides this via the
	 * `botcat_active_ids_for_tags` filter.
	 *
	 * @param list<int> $tag_ids
	 * @return Generator<string>
	 */
	public function active_ids_for_tags( array $tag_ids ): Generator {
		/** @var iterable<string>|null $override */
		$override = apply_filters( 'botcat_active_ids_for_tags', null, $tag_ids );

		if ( $override !== null ) {
			yield from $override;
			return;
		}

		yield from $this->active_ids();
	}

	public function upsert_active(
		string $line_user_id,
		?string $display_name,
		?string $picture_url,
		string $followed_at
	): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$now   = gmdate( 'Y-m-d H:i:s' );

		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (line_user_id, display_name, picture_url, status, followed_at, unfollowed_at, created_at, updated_at) "
			. 'VALUES (%s, %s, %s, %s, %s, NULL, %s, %s) '
			. 'ON DUPLICATE KEY UPDATE '
			. 'display_name = COALESCE(VALUES(display_name), display_name), '
			. 'picture_url = COALESCE(VALUES(picture_url), picture_url), '
			. 'status = VALUES(status), '
			. 'followed_at = VALUES(followed_at), '
			. 'unfollowed_at = NULL, '
			. 'updated_at = VALUES(updated_at)',
			$line_user_id,
			$display_name,
			$picture_url,
			Subscriber::STATUS_ACTIVE,
			$followed_at,
			$now,
			$now
		);

		$wpdb->query( $sql );
	}

	public function mark_unfollowed( string $line_user_id, string $unfollowed_at ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$now   = gmdate( 'Y-m-d H:i:s' );

		$sql = $wpdb->prepare(
			"UPDATE {$table} SET status = %s, unfollowed_at = %s, updated_at = %s WHERE line_user_id = %s",
			Subscriber::STATUS_UNFOLLOWED,
			$unfollowed_at,
			$now,
			$line_user_id
		);

		$wpdb->query( $sql );
	}

	/**
	 * @param list<int> $subscriber_ids
	 */
	public function delete_many( array $subscriber_ids ): int {
		if ( $subscriber_ids === array() ) {
			return 0;
		}

		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$logs  = $wpdb->prefix . 'botcat_push_logs';

		$placeholders = implode( ',', array_fill( 0, count( $subscriber_ids ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$logs} SET subscriber_id = NULL WHERE subscriber_id IN ({$placeholders})",
				...$subscriber_ids
			)
		);

		return (int) $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id IN ({$placeholders})",
				...$subscriber_ids
			)
		);
	}
}
