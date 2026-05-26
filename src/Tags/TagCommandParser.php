<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * Translates the text body of a LINE message event into a {@see TagCommand}.
 *
 * Recognised forms (all case-insensitive, leading whitespace tolerated):
 *   /tag 訂閱 <keyword>  | /tag subscribe <keyword>
 *   /tag 取消 <keyword>  | /tag unsubscribe <keyword>
 *   /tag 列表           | /tag list
 */
class TagCommandParser {

	public function parse( string $text ): TagCommand {
		$trimmed = trim( $text );

		if ( ! str_starts_with( strtolower( $trimmed ), '/tag' ) ) {
			return TagCommand::not_a_command();
		}

		if ( preg_match( '#^/tag\s+(?:訂閱|subscribe)\s+(.+)$#iu', $trimmed, $m ) ) {
			return TagCommand::subscribe( trim( $m[1] ) );
		}

		if ( preg_match( '#^/tag\s+(?:取消|unsubscribe)\s+(.+)$#iu', $trimmed, $m ) ) {
			return TagCommand::unsubscribe( trim( $m[1] ) );
		}

		if ( preg_match( '#^/tag\s+(?:列表|list)\s*$#iu', $trimmed ) ) {
			return TagCommand::list();
		}

		return TagCommand::unknown();
	}
}
