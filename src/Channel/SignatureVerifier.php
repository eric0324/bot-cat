<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Timing-safe verification of the `X-Line-Signature` header.
 *
 * Per LINE docs:
 *   `Base64( HMAC-SHA256( channel_secret, raw_request_body ) )`
 */
final class SignatureVerifier {

	public function __construct( private readonly string $channel_secret ) {
	}

	public function verify( string $raw_body, ?string $signature ): bool {
		if ( $this->channel_secret === '' || $signature === null || $signature === '' ) {
			return false;
		}

		$expected = base64_encode( hash_hmac( 'sha256', $raw_body, $this->channel_secret, true ) );

		return hash_equals( $expected, $signature );
	}
}
