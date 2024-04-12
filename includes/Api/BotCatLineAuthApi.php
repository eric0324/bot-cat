<?php

/**
 * Class BotCatLineAuthApi
 *
 * This class provides functionality related to LINE authentication for the BotCat plugin.
 */

namespace BotCat\Api;

use BotCat\Service\BotCatAuthService;
use JsonException;


class BotCatLineAuthApi {
	private BotCatAuthService $botCatBasicAuthService;

	public function __construct() {
		$this->botCatBasicAuthService = new BotCatAuthService();
	}

	public function register_rest_route(): void {
		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/line/options', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_token' ],
			'permission_callback' => '__return_true'
		] );

		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/line/uuid', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_uuid' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * @param $request
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_store_token( $request ): void {

		$can_access = $this->botCatBasicAuthService->bot_cat_check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		$options = array_merge( $options, [
			'channel_access_token' => $request['channel_access_token'],
		] );

		update_option( BOT_CAT_OPTION_PREFIX . 'line', $options );

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}

	/**
	 * Store UUID for a specific user.
	 *
	 * @param mixed $request The request object containing the UUID.
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
			update_user_meta( $user_info[0]->ID, BOT_CAT_OPTION_PREFIX . 'line_uuid', $request['uuid'] );
		}

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}
}