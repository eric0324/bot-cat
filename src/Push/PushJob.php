<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Immutable view of a `botcat_push_jobs` row.
 *
 * Status state machine (per W2 spec):
 *
 *   pending → running → sent
 *                     → partial
 *                     → failed
 *                     → aborted_auth
 *           → skipped_duplicate     (idempotency hit; never runs)
 */
final class PushJob {

	public const STATUS_PENDING           = 'pending';
	public const STATUS_RUNNING           = 'running';
	public const STATUS_SENT              = 'sent';
	public const STATUS_PARTIAL           = 'partial';
	public const STATUS_SKIPPED_DUPLICATE = 'skipped_duplicate';
	public const STATUS_ABORTED_AUTH      = 'aborted_auth';
	public const STATUS_FAILED            = 'failed';

	public const TERMINAL_STATUSES = array(
		self::STATUS_SENT,
		self::STATUS_PARTIAL,
		self::STATUS_SKIPPED_DUPLICATE,
		self::STATUS_ABORTED_AUTH,
		self::STATUS_FAILED,
	);

	public function __construct(
		public readonly int $id,
		public readonly int $post_id,
		public readonly string $post_type,
		public readonly string $status,
		public readonly int $recipient_count,
		public readonly int $sent_count,
		public readonly int $failed_count,
		public readonly bool $is_test,
		public readonly ?string $last_error,
		public readonly string $created_at,
		public readonly ?string $triggered_at,
		public readonly ?string $started_at,
		public readonly ?string $finished_at
	) {
	}

	public function is_terminal(): bool {
		return in_array( $this->status, self::TERMINAL_STATUSES, true );
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			post_id: (int) ( $row['post_id'] ?? 0 ),
			post_type: (string) ( $row['post_type'] ?? 'post' ),
			status: (string) ( $row['status'] ?? self::STATUS_PENDING ),
			recipient_count: (int) ( $row['recipient_count'] ?? 0 ),
			sent_count: (int) ( $row['sent_count'] ?? 0 ),
			failed_count: (int) ( $row['failed_count'] ?? 0 ),
			is_test: ! empty( $row['is_test'] ),
			last_error: isset( $row['last_error'] ) && $row['last_error'] !== null ? (string) $row['last_error'] : null,
			created_at: (string) ( $row['created_at'] ?? '' ),
			triggered_at: isset( $row['triggered_at'] ) ? (string) $row['triggered_at'] : null,
			started_at: isset( $row['started_at'] ) ? (string) $row['started_at'] : null,
			finished_at: isset( $row['finished_at'] ) ? (string) $row['finished_at'] : null,
		);
	}
}
