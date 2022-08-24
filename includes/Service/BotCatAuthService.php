<?php

class BotCatAuthService {

	/**
	 * @param $request
	 *
	 * @return bool
	 * @throws JsonException
	 */
	public function check_key( $request ): bool {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'basic' );

		$BOT_CAT_key = json_encode( $request->get_header( 'BOT_CAT_key' ), JSON_THROW_ON_ERROR );

		return '"' . $options['key'] . '"' === $BOT_CAT_key;
	}

	/**
	 * @param $request
	 *
	 * @return array
	 */
	public function get_user_by_token( $request ): array {
		$users = get_users( [
			'meta_key'   => BOT_CAT_OPTION_PREFIX . 'token',
			'meta_value' => $request['user_token']
		] );

		return $users;
	}
}