<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Authenticated encryption for LINE Channel credentials at rest.
 *
 * Uses libsodium's xsalsa20-poly1305 (`sodium_crypto_secretbox`), bundled
 * with PHP 7.2+. The 32-byte key is derived from the site's `AUTH_KEY`
 * (or any other site-specific high-entropy string) via HKDF-SHA256 with
 * a plugin-specific salt, so a database dump cloned to another site
 * cannot decrypt successfully.
 */
final class CredentialEncryptor {

	public const SCHEME = 'botcat1:';

	private const SALT = 'bot-cat:v1:channel-credentials';

	private string $key;

	public function __construct( string $auth_key ) {
		$this->key = hash_hkdf( 'sha256', $auth_key, SODIUM_CRYPTO_SECRETBOX_KEYBYTES, self::SALT );
	}

	public function encrypt( string $plaintext ): string {
		if ( $plaintext === '' ) {
			return '';
		}

		$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = sodium_crypto_secretbox( $plaintext, $nonce, $this->key );

		return self::SCHEME . base64_encode( $nonce . $cipher );
	}

	public function decrypt( string $ciphertext ): ?string {
		if ( $ciphertext === '' ) {
			return '';
		}

		if ( ! str_starts_with( $ciphertext, self::SCHEME ) ) {
			return null;
		}

		$blob = base64_decode( substr( $ciphertext, strlen( self::SCHEME ) ), true );
		if ( $blob === false || strlen( $blob ) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES ) {
			return null;
		}

		$nonce  = substr( $blob, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
		$cipher = substr( $blob, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

		$result = sodium_crypto_secretbox_open( $cipher, $nonce, $this->key );

		return $result === false ? null : $result;
	}
}
