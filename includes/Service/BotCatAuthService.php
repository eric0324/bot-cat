<?php

class BotCatAuthService {

	/**
	 * @param $request
	 *
	 * @return bool
	 * @throws JsonException
	 */
	public function bot_cat_check_key( $request ): bool {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'basic' );

		$BOT_CAT_key = json_encode( $request->get_header( 'BOT_CAT_Key' ), JSON_THROW_ON_ERROR );

		if (!$options['key']) {
			return false;
		}

		return '"' . $options['key'] . '"' === $BOT_CAT_key;
	}

	/**
	 * @param $request
	 *
	 * @return array
	 */
	public function bot_cat_get_user_by_token( $request ): array {
		return get_users( [
			'meta_key'   => BOT_CAT_OPTION_PREFIX . 'token',
			'meta_value' => $request['user_token']
		] );
	}
}