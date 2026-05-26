<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * HMAC-signed token encoding a subscriber ID for click attribution.
 *
 * Note: LINE multicast cannot per-recipient substitute, so this signer
 * is wired up for the (future) /push endpoint and for manual test
 * pushes; multicast batches send the same URL to all 500 recipients
 * and clicks are attributed anonymously via ip_hash.
 */
class SubscriberTokenSigner {

	private const SIG_LENGTH = 16;
	private const SALT       = 'bot-cat:v1:short-link';

	private string $key;

	public function __construct( string $secret ) {
		$this->key = hash_hkdf( 'sha256', $secret, 32, self::SALT );
	}

	public function sign( int $subscriber_id ): string {
		$payload   = (string) $subscriber_id;
		$signature = substr( hash_hmac( 'sha256', $payload, $this->key ), 0, self::SIG_LENGTH );
		return $this->base64url_encode( $payload . '.' . $signature );
	}

	public function verify( string $token ): ?int {
		if ( $token === '' ) {
			return null;
		}

		$decoded = $this->base64url_decode( $token );
		if ( $decoded === null ) {
			return null;
		}

		$parts = explode( '.', $decoded, 2 );
		if ( count( $parts ) !== 2 ) {
			return null;
		}

		[ $payload, $signature ] = $parts;
		if ( $payload === '' || $signature === '' || ! ctype_digit( $payload ) ) {
			return null;
		}

		$expected = substr( hash_hmac( 'sha256', $payload, $this->key ), 0, self::SIG_LENGTH );
		if ( ! hash_equals( $expected, $signature ) ) {
			return null;
		}

		return (int) $payload;
	}

	private function base64url_encode( string $bytes ): string {
		return rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' );
	}

	private function base64url_decode( string $encoded ): ?string {
		$padded  = str_pad( strtr( $encoded, '-_', '+/' ), strlen( $encoded ) + ( 4 - strlen( $encoded ) % 4 ) % 4, '=' );
		$decoded = base64_decode( $padded, true );
		return $decoded === false ? null : $decoded;
	}
}
