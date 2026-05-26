<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Data access for `botcat_push_jobs`.
 */
class PushJobRepository {

	public const TABLE = 'botcat_push_jobs';

	public function create_pending( int $post_id, string $post_type, bool $is_test = false ): int {
		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );

		$wpdb->insert(
			$wpdb->prefix . self::TABLE,
			array(
				'post_id'      => $post_id,
				'post_type'    => $post_type,
				'status'       => PushJob::STATUS_PENDING,
				'is_test'      => $is_test ? 1 : 0,
				'created_at'   => $now,
				'triggered_at' => $now,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public function find_by_id( int $id ): ?PushJob {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . self::TABLE . ' WHERE id = %d LIMIT 1',
				$id
			),
			ARRAY_A
		);

		return is_array( $row ) ? PushJob::from_row( $row ) : null;
	}

	public function find_completed_for_post( int $post_id ): ?PushJob {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $wpdb->prefix . self::TABLE
				. ' WHERE post_id = %d AND status IN (%s, %s) ORDER BY id DESC LIMIT 1',
				$post_id,
				PushJob::STATUS_SENT,
				PushJob::STATUS_PARTIAL
			),
			ARRAY_A
		);

		return is_array( $row ) ? PushJob::from_row( $row ) : null;
	}

	public function mark_running( int $id, int $recipient_count ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			array(
				'status'          => PushJob::STATUS_RUNNING,
				'recipient_count' => $recipient_count,
				'started_at'      => gmdate( 'Y-m-d H:i:s' ),
			),
			array( 'id' => $id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);
	}

	public function mark_status( int $id, string $status, ?string $last_error = null ): void {
		global $wpdb;

		$data    = array(
			'status'      => $status,
			'finished_at' => in_array( $status, PushJob::TERMINAL_STATUSES, true ) ? gmdate( 'Y-m-d H:i:s' ) : null,
		);
		$formats = array( '%s', '%s' );

		if ( $last_error !== null ) {
			$data['last_error'] = $last_error;
			$formats[]          = '%s';
		}

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);
	}

	public function set_last_error( int $id, string $error ): void {
		global $wpdb;

		$wpdb->update(
			$wpdb->prefix . self::TABLE,
			array( 'last_error' => $error ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public function increment_counts( int $id, int $sent_delta, int $failed_delta ): void {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET sent_count = sent_count + %d, failed_count = failed_count + %d WHERE id = %d",
				$sent_delta,
				$failed_delta,
				$id
			)
		);
	}

	/**
	 * @return list<PushJob>
	 */
	public function list_paged( int $page = 1, int $per_page = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$page  = max( 1, $page );
		$per   = min( 500, max( 1, $per_page ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE is_test = 0 ORDER BY triggered_at DESC LIMIT %d OFFSET %d",
				$per,
				( $page - 1 ) * $per
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( array $row ): PushJob => PushJob::from_row( $row ),
			$rows
		);
	}

	public function count_non_test(): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_test = 0" );
	}
}
