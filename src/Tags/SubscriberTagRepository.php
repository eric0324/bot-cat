<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

use Generator;

/**
 * Manages the `botcat_subscriber_tags` join table.
 *
 * Composite PK on (subscriber_id, tag_id) makes subscribe idempotent
 * via ON DUPLICATE KEY UPDATE.
 */
class SubscriberTagRepository {

	public const TABLE      = 'botcat_subscriber_tags';
	public const CHUNK_SIZE = 500;

	public function subscribe( int $subscriber_id, int $tag_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$now   = gmdate( 'Y-m-d H:i:s' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (subscriber_id, tag_id, created_at) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE subscriber_id = subscriber_id",
				$subscriber_id,
				$tag_id,
				$now
			)
		);
	}

	public function unsubscribe( int $subscriber_id, int $tag_id ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE subscriber_id = %d AND tag_id = %d",
				$subscriber_id,
				$tag_id
			)
		);
	}

	/**
	 * @return list<int>
	 */
	public function tag_ids_for_subscriber( int $subscriber_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT tag_id FROM {$table} WHERE subscriber_id = %d", $subscriber_id ),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_values(
			array_map(
				static fn( array $row ): int => (int) ( $row['tag_id'] ?? 0 ),
				$rows
			)
		);
	}

	/**
	 * Stream every active subscriber line_user_id who subscribed to any
	 * of $tag_ids.
	 *
	 * @param list<int> $tag_ids
	 * @return Generator<string>
	 */
	public function active_line_user_ids_for_tags( array $tag_ids ): Generator {
		if ( $tag_ids === array() ) {
			return;
		}

		global $wpdb;
		$join         = $wpdb->prefix . self::TABLE;
		$subscribers  = $wpdb->prefix . 'botcat_subscribers';
		$placeholders = implode( ',', array_fill( 0, count( $tag_ids ), '%d' ) );
		$offset       = 0;

		do {
			$sql = sprintf(
				'SELECT DISTINCT s.line_user_id FROM %1$s AS s INNER JOIN %2$s AS st ON st.subscriber_id = s.id WHERE s.status = %%s AND st.tag_id IN (%3$s) ORDER BY s.id LIMIT %%d OFFSET %%d',
				$subscribers,
				$join,
				$placeholders
			);

			$args = array_merge( array( 'active' ), $tag_ids, array( self::CHUNK_SIZE, $offset ) );
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$args ), ARRAY_A );
			if ( ! is_array( $rows ) ) {
				$rows = array();
			}

			foreach ( $rows as $row ) {
				yield (string) ( $row['line_user_id'] ?? '' );
			}

			$offset += self::CHUNK_SIZE;
		} while ( count( $rows ) === self::CHUNK_SIZE );
	}
}
