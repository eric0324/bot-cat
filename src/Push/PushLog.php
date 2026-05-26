<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Immutable view of a `botcat_push_logs` row in the push pipeline.
 *
 * For admin display joined with push_jobs / wp_posts, see
 * {@see \BotCat\Subscribers\PushLogEntry} which carries the extra
 * `post_id` column from the JOIN.
 */
final class PushLog {

	public const STATUS_PENDING = 'pending';
	public const STATUS_SENT    = 'sent';
	public const STATUS_FAILED  = 'failed';
	public const STATUS_SKIPPED = 'skipped';

	public function __construct(
		public readonly int $id,
		public readonly int $job_id,
		public readonly ?int $subscriber_id,
		public readonly string $line_user_id,
		public readonly string $status,
		public readonly ?string $error,
		public readonly int $attempts,
		public readonly bool $is_test,
		public readonly string $created_at
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			job_id: (int) ( $row['job_id'] ?? 0 ),
			subscriber_id: isset( $row['subscriber_id'] ) && $row['subscriber_id'] !== null ? (int) $row['subscriber_id'] : null,
			line_user_id: (string) ( $row['line_user_id'] ?? '' ),
			status: (string) ( $row['status'] ?? self::STATUS_PENDING ),
			error: isset( $row['error'] ) && $row['error'] !== null ? (string) $row['error'] : null,
			attempts: (int) ( $row['attempts'] ?? 0 ),
			is_test: ! empty( $row['is_test'] ),
			created_at: (string) ( $row['created_at'] ?? '' ),
		);
	}
}
