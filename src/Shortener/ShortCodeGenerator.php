<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Produces 6-character base-62 codes for short URLs.
 *
 * 62⁶ ≈ 56 billion combinations — collision retries are vanishingly
 * rare at any realistic site volume.
 */
class ShortCodeGenerator {

	public const ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	public const LENGTH   = 6;

	public function generate(): string {
		$max  = strlen( self::ALPHABET ) - 1;
		$code = '';
		for ( $i = 0; $i < self::LENGTH; $i++ ) {
			$code .= self::ALPHABET[ random_int( 0, $max ) ];
		}
		return $code;
	}
}
