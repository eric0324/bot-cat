<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

/**
 * Builds the `{excerpt}` token replacement.
 *
 * Behavior (per message-template spec):
 *  - If the post has an authored excerpt, use it verbatim.
 *  - Otherwise strip HTML from the content and truncate to the first
 *    100 multibyte chars, suffixed with `…` when truncated.
 */
final class ExcerptFallback {

	public const LIMIT = 100;

	public static function derive( string $authored_excerpt, string $content ): string {
		$authored = trim( $authored_excerpt );
		if ( $authored !== '' ) {
			return $authored;
		}

		$plain = trim( html_entity_decode( wp_strip_all_tags( $content ), ENT_QUOTES, 'UTF-8' ) );
		if ( $plain === '' ) {
			return '';
		}

		if ( mb_strlen( $plain, 'UTF-8' ) <= self::LIMIT ) {
			return $plain;
		}

		return mb_substr( $plain, 0, self::LIMIT, 'UTF-8' ) . '…';
	}
}
