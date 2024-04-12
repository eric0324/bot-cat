<?php

namespace BotCat\Api;

use BotCat\Service\BotCatAuthService;
use JsonException;

/**
 * Class BotCatTelegramAuthApi
 *
 * This class is responsible for registering REST routes and storing API token, chat ID, and UUID for the Telegram bot.
 */

defined( 'ABSPATH' ) || exit;

class BotCatTelegramAuthApi {
	private BotCatAuthService $botCatBasicAuthService;

	public function __construct() {
		$this->botCatBasicAuthService = new BotCatAuthService();
	}

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
	 * @throws JsonException
	 */
	public function bot_cat_store_token( $request ): void {

		$can_access = $this->botCatBasicAuthService->bot_cat_check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'telegram' );

		$options = array_merge( $options, [
			'api_token' => $request['api_token'],
			'chat_id'   => $request['chat_id'],
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
	 * @throws JsonException
	 */
	public function bot_cat_store_uuid( $request ): void {

		$can_access = $this->botCatBasicAuthService->bot_cat_check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$user_info = $this->botCatBasicAuthService->bot_cat_get_user_by_token( $request );

		if ( $user_info[0] && isset( $user_info[0]->ID ) ) {
			update_user_meta( $user_info[0]->ID, BOT_CAT_OPTION_PREFIX . 'telegram_uuid', $request['uuid'] );
		}

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}
}