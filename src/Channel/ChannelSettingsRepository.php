<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Persists channel credentials in the `botcat_channel_settings` option,
 * encrypting the secret + token on save and decrypting on read.
 */
class ChannelSettingsRepository {

	public const OPTION = 'botcat_channel_settings';

	public function __construct( private readonly CredentialEncryptor $encryptor ) {
	}

	public function get(): ChannelSettings {
		$raw = get_option( self::OPTION, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		return new ChannelSettings(
			(string) ( $raw['channel_id'] ?? '' ),
			(string) ( $this->encryptor->decrypt( (string) ( $raw['channel_secret'] ?? '' ) ) ?? '' ),
			(string) ( $this->encryptor->decrypt( (string) ( $raw['access_token'] ?? '' ) ) ?? '' )
		);
	}

	public function save( ChannelSettings $settings ): void {
		update_option(
			self::OPTION,
			array(
				'channel_id'     => $settings->channel_id(),
				'channel_secret' => $this->encryptor->encrypt( $settings->channel_secret() ),
				'access_token'   => $this->encryptor->encrypt( $settings->access_token() ),
			)
		);
	}
}
