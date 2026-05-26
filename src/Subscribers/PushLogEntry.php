<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * One push delivery attempt as surfaced in the Subscriber detail page.
 *
 * Joins `botcat_push_logs` with `botcat_push_jobs` so the post id (and
 * eventually title) is available without a second query per row.
 */
final class PushLogEntry {

	public function __construct(
		public readonly int $id,
		public readonly int $job_id,
		public readonly ?int $subscriber_id,
		public readonly string $line_user_id,
		public readonly string $status,
		public readonly ?string $error,
		public readonly string $created_at,
		public readonly ?int $post_id
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			job_id: (int) ( $row['job_id'] ?? 0 ),
			subscriber_id: isset( $row['subscriber_id'] ) ? (int) $row['subscriber_id'] : null,
			line_user_id: (string) ( $row['line_user_id'] ?? '' ),
			status: (string) ( $row['status'] ?? '' ),
			error: isset( $row['error'] ) ? (string) $row['error'] : null,
			created_at: (string) ( $row['created_at'] ?? '' ),
			post_id: isset( $row['post_id'] ) ? (int) $row['post_id'] : null,
		);
	}
}
