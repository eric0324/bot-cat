<?php

/**
 * Class BotCatTelegramAuthApi
 *
 * This class is responsible for registering REST routes and storing API token, chat ID, and UUID for the Telegram bot.
 */

namespace BotCat\Api;


class BotCatTelegramAuthApi {

	/**
	 * Register the REST routes for the BOT_CAT plugin.
	 *
	 * @return void
	 */
	public function register_rest_route(): void {
		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/telegram/options', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_token' ],
			'permission_callback' => '__return_true'
		] );

		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/telegram/uuid', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_uuid' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * Store the API token and chat ID for the Telegram bot.
	 *
	 * @param array $request The request data containing the API token and chat ID.
	 *
	 * @return void
	 */
	public function bot_cat_store_token( $request ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'telegram' );

		$options = array_merge( $options, [
			'api_token' => sanitize_text_field( $request['api_token'] ),
			'chat_id'   => sanitize_text_field( $request['chat_id'] ),
		] );

		update_option( BOT_CAT_OPTION_PREFIX . 'telegram', $options );

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}

	/**
	 * Store the UUID for a user.
	 *
	 * @param array $request The request data containing the UUID.
	 *
	 * @return void
	 */
	public function bot_cat_store_uuid( $request ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$user_id = get_current_user_id();
		if ( $user_id && isset( $request['uuid'] ) ) {
			update_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'telegram_uuid', sanitize_text_field( $request['uuid'] ) );
		}

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}
}