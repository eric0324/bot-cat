<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Reads and saves the list of post types that trigger a push on publish.
 *
 * Default: ['post'] (per spec). Admins can extend via the Settings page;
 * developers can override at runtime via the `botcat_eligible_post_types`
 * filter.
 */
class EligiblePostTypes {

	public const OPTION  = 'botcat_eligible_post_types';
	public const FILTER  = 'botcat_eligible_post_types';
	public const DEFAULT = array( 'post' );

	/**
	 * @return list<string>
	 */
	public function get(): array {
		$stored = get_option( self::OPTION, self::DEFAULT );
		if ( ! is_array( $stored ) || $stored === array() ) {
			$stored = self::DEFAULT;
		}

		/** @var list<string> $filtered */
		$filtered = apply_filters( self::FILTER, array_values( array_map( 'strval', $stored ) ) );

		return is_array( $filtered ) ? array_values( $filtered ) : self::DEFAULT;
	}

	public function is_eligible( string $post_type ): bool {
		return in_array( $post_type, $this->get(), true );
	}

	/**
	 * @param list<string> $types
	 */
	public function save( array $types ): void {
		update_option(
			self::OPTION,
			array_values(
				array_unique(
					array_filter(
						array_map( 'strval', $types ),
						static fn( string $t ): bool => $t !== ''
					)
				)
			)
		);
	}
}
