<?php

namespace BotCat\Service;

use JsonException;

/**
 * Class BotCatAuthService
 *
 * Provides authentication services for the BotCat system.
 */
class BotCatAuthService {

	/**
	 * Check if the provided BOT_CAT_Key matches the configured key.
	 *
	 * @param $request object containing the BOT_CAT_Key header.
	 *
	 * @return bool Returns true if the provided key matches the configured key, false otherwise.
	 * @throws JsonException
	 */
	public function bot_cat_check_key( $request ): bool {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'basic' );

		$BOT_CAT_key = json_encode( $request->get_header( 'BOT_CAT_Key' ), JSON_THROW_ON_ERROR );

		if ( ! $options['key'] ) {
			return false;
		}

		return '"' . $options['key'] . '"' === $BOT_CAT_key;
	}

	/**
	 * Retrieves the user with the given token.
	 *
	 * @param array $request The request data, containing the user token.
	 *
	 * @return array An array of user objects matching the given token.
	 */
	public function bot_cat_get_user_by_token( $request ): array {
		return get_users( [
			'meta_key'   => BOT_CAT_OPTION_PREFIX . 'token',
			'meta_value' => $request['user_token']
		] );
	}
}