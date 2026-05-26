<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * Immutable view of a `botcat_tags` row.
 */
final class Tag {

	/**
	 * @param list<int> $mapped_categories
	 */
	public function __construct(
		public readonly int $id,
		public readonly string $slug,
		public readonly string $display_name,
		public readonly string $keyword,
		public readonly array $mapped_categories,
		public readonly string $created_at,
		public readonly string $updated_at
	) {
	}

	/**
	 * @param array<string, mixed> $row
	 */
	public static function from_row( array $row ): self {
		$mapped = isset( $row['mapped_categories'] ) ? (string) $row['mapped_categories'] : '';
		$ids    = $mapped === '' ? array() : (array) json_decode( $mapped, true );

		return new self(
			id: (int) ( $row['id'] ?? 0 ),
			slug: (string) ( $row['slug'] ?? '' ),
			display_name: (string) ( $row['display_name'] ?? '' ),
			keyword: (string) ( $row['keyword'] ?? '' ),
			mapped_categories: array_values( array_map( 'intval', $ids ) ),
			created_at: (string) ( $row['created_at'] ?? '' ),
			updated_at: (string) ( $row['updated_at'] ?? '' ),
		);
	}
}
