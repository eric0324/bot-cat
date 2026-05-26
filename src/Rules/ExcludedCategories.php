<?php
/**
 * @package BotCat\Rules
 */

declare(strict_types=1);

namespace BotCat\Rules;

/**
 * Categories whose presence on a post suppresses the push.
 *
 * Evaluated against the post's full category list (not just the
 * primary) per spec.
 */
class ExcludedCategories {

	public const OPTION = 'botcat_excluded_categories';

	/**
	 * @return list<int>
	 */
	public function get(): array {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		return array_values( array_unique( array_map( 'intval', $stored ) ) );
	}

	/**
	 * @param array<int|string> $term_ids
	 */
	public function save( array $term_ids ): void {
		update_option(
			self::OPTION,
			array_values(
				array_unique(
					array_map( 'intval', $term_ids )
				)
			)
		);
	}

	public function is_excluded( int $post_id ): bool {
		$excluded = $this->get();
		if ( $excluded === array() ) {
			return false;
		}

		$post_categories = wp_get_post_categories( $post_id, array( 'fields' => 'ids' ) );
		if ( ! is_array( $post_categories ) ) {
			return false;
		}

		return array_intersect( $excluded, array_map( 'intval', $post_categories ) ) !== array();
	}
}
