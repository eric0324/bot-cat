<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Immutable view of a row in `botcat_subscribers`.
 */
final class Subscriber {

	public const STATUS_ACTIVE     = 'active';
	public const STATUS_UNFOLLOWED = 'unfollowed';

	public function __construct(
		public readonly int $id,
		public readonly string $line_user_id,
		public readonly ?string $display_name,
		public readonly ?string $picture_url,
		public readonly string $status,
		public readonly ?string $followed_at,
		public readonly ?string $unfollowed_at
	) {
	}

	public function is_active(): bool {
		return $this->status === self::STATUS_ACTIVE;
	}

	public function is_unfollowed(): bool {
		return $this->status === self::STATUS_UNFOLLOWED;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			line_user_id: (string) ( $row['line_user_id'] ?? '' ),
			display_name: isset( $row['display_name'] ) ? (string) $row['display_name'] : null,
			picture_url: isset( $row['picture_url'] ) ? (string) $row['picture_url'] : null,
			status: (string) ( $row['status'] ?? self::STATUS_ACTIVE ),
			followed_at: isset( $row['followed_at'] ) ? (string) $row['followed_at'] : null,
			unfollowed_at: isset( $row['unfollowed_at'] ) ? (string) $row['unfollowed_at'] : null
		);
	}
}
