<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

/**
 * Persists the active message template and reports length status for
 * the editor's live counter.
 *
 * Length thresholds (per spec):
 *   green   : ≤ 4000
 *   yellow  : 4001 – 4999
 *   red     : ≥ 5000   (save is blocked at this band)
 */
class TemplateRepository {

	public const OPTION              = 'botcat_message_template';
	public const DEFAULT_TEMPLATE    = "{title}\n\n{excerpt}\n\n{permalink}";
	public const MAX_RENDERED_LENGTH = 5000;
	public const YELLOW_THRESHOLD    = 4000;

	public const STATUS_GREEN  = 'green';
	public const STATUS_YELLOW = 'yellow';
	public const STATUS_RED    = 'red';

	public function get(): string {
		$stored = get_option( self::OPTION, self::DEFAULT_TEMPLATE );
		return is_string( $stored ) && $stored !== '' ? $stored : self::DEFAULT_TEMPLATE;
	}

	public function save( string $template ): void {
		update_option( self::OPTION, $template );
	}

	/**
	 * @return string|null Error message when rendered preview is too long, null otherwise.
	 */
	public function length_check( string $rendered_preview ): ?string {
		if ( mb_strlen( $rendered_preview, 'UTF-8' ) <= self::MAX_RENDERED_LENGTH ) {
			return null;
		}

		return sprintf(
			/* translators: %d: LINE's max message length. */
			__( 'Rendered message exceeds LINE\'s %d-character limit.', 'bot-cat' ),
			self::MAX_RENDERED_LENGTH
		);
	}

	public function status_for_length( int $length ): string {
		if ( $length >= self::MAX_RENDERED_LENGTH ) {
			return self::STATUS_RED;
		}
		if ( $length >= self::YELLOW_THRESHOLD ) {
			return self::STATUS_YELLOW;
		}
		return self::STATUS_GREEN;
	}
}
