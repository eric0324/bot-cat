<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Data access for `botcat_push_logs`.
 *
 * The push pipeline writes one row per (job, subscriber) recipient,
 * then mutates statuses as batches succeed or fail.
 */
class PushLogRepository {

	public const TABLE = 'botcat_push_logs';

	/**
	 * Create a pending log row per recipient in $line_user_ids.
	 *
	 * @param list<string> $line_user_ids
	 * @param list<?int>   $subscriber_ids Indexed parallel to $line_user_ids; may contain null entries.
	 * @return list<int> Inserted log IDs in the same order.
	 */
	public function create_for_job(
		int $job_id,
		array $line_user_ids,
		array $subscriber_ids = array(),
		bool $is_test = false
	): array {
		global $wpdb;

		$now  = gmdate( 'Y-m-d H:i:s' );
		$ids  = array();
		$test = $is_test ? 1 : 0;

		foreach ( $line_user_ids as $index => $line_user_id ) {
			$subscriber_id = $subscriber_ids[ $index ] ?? null;

			$wpdb->insert(
				$wpdb->prefix . self::TABLE,
				array(
					'job_id'        => $job_id,
					'subscriber_id' => $subscriber_id,
					'line_user_id'  => $line_user_id,
					'status'        => PushLog::STATUS_PENDING,
					'attempts'      => 0,
					'is_test'       => $test,
					'created_at'    => $now,
				),
				array( '%d', $subscriber_id === null ? '%s' : '%d', '%s', '%s', '%d', '%d', '%s' )
			);

			$ids[] = (int) $wpdb->insert_id;
		}

		return $ids;
	}

	/**
	 * @param list<int> $ids
	 */
	public function mark_status( array $ids, string $status, ?string $error = null ): void {
		if ( $ids === array() ) {
			return;
		}

		global $wpdb;
		$table        = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$sent_at      = $status === PushLog::STATUS_SENT ? gmdate( 'Y-m-d H:i:s' ) : null;

		if ( $error !== null ) {
			$sql = sprintf(
				'UPDATE %s SET status = %%s, error = %%s, sent_at = %%s, attempts = attempts + 1 WHERE id IN (%s)',
				$table,
				$placeholders
			);
			$wpdb->query( $wpdb->prepare( $sql, $status, $error, $sent_at, ...$ids ) );
			return;
		}

		$sql = sprintf(
			'UPDATE %s SET status = %%s, sent_at = %%s, attempts = attempts + 1 WHERE id IN (%s)',
			$table,
			$placeholders
		);
		$wpdb->query( $wpdb->prepare( $sql, $status, $sent_at, ...$ids ) );
	}

	public function count_for_job( int $job_id, string $status ): int {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE job_id = %d AND status = %s",
				$job_id,
				$status
			)
		);
	}

	/**
	 * @return list<PushLog>
	 */
	public function failed_for_job( int $job_id ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %d AND status = %s",
				$job_id,
				PushLog::STATUS_FAILED
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( array $row ): PushLog => PushLog::from_row( $row ),
			$rows
		);
	}

	/**
	 * @param list<int> $ids
	 */
	public function reset_for_resend( array $ids ): void {
		if ( $ids === array() ) {
			return;
		}

		global $wpdb;
		$table        = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$sql = sprintf(
			'UPDATE %s SET status = %%s, error = NULL, sent_at = NULL WHERE id IN (%s)',
			$table,
			$placeholders
		);

		$wpdb->query( $wpdb->prepare( $sql, PushLog::STATUS_PENDING, ...$ids ) );
	}

	/**
	 * @param list<int> $ids
	 * @return list<PushLog>
	 */
	public function find_by_ids_for_job( int $job_id, array $ids ): array {
		if ( $ids === array() ) {
			return array();
		}

		global $wpdb;
		$table        = $wpdb->prefix . self::TABLE;
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$sql = sprintf(
			'SELECT * FROM %s WHERE job_id = %%d AND id IN (%s)',
			$table,
			$placeholders
		);

		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $job_id, ...$ids ), ARRAY_A );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( array $row ): PushLog => PushLog::from_row( $row ),
			$rows
		);
	}

	/**
	 * @return list<PushLog>
	 */
	public function list_for_job( int $job_id, int $page = 1, int $per_page = 50 ): array {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE;
		$page  = max( 1, $page );
		$per   = min( 500, max( 1, $per_page ) );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE job_id = %d ORDER BY id DESC LIMIT %d OFFSET %d",
				$job_id,
				$per,
				( $page - 1 ) * $per
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static fn( array $row ): PushLog => PushLog::from_row( $row ),
			$rows
		);
	}
}
