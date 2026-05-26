<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Immutable view of a `botcat_short_links` row.
 */
final class ShortLink {

	public function __construct(
		public readonly int $id,
		public readonly string $code,
		public readonly int $post_id,
		public readonly string $original_url,
		public readonly string $created_at
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			code: (string) ( $row['code'] ?? '' ),
			post_id: (int) ( $row['post_id'] ?? 0 ),
			original_url: (string) ( $row['original_url'] ?? '' ),
			created_at: (string) ( $row['created_at'] ?? '' ),
		);
	}
}
