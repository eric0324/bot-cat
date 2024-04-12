<?php

/**
 * Class BotCatLineNotifyAuthApi
 *
 * This class is responsible for handling Line Notify authentication for BotCat.
 */

namespace BotCat\Api;

use BotCat\Service\BotCatAuthService;
use JsonException;


class BotCatLineNotifyAuthApi {
	private BotCatAuthService $botCatBasicAuthService;

	public function __construct() {
		$this->botCatBasicAuthService = new BotCatAuthService();
	}

	public function register_rest_route(): void {

		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/line_notify/uuid', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_uuid' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * Stores a UUID for a user.
	 *
	 * @param array $request The request data, including the UUID.
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
			update_user_meta( $user_info[0]->ID, BOT_CAT_OPTION_PREFIX . 'line_notify_uuid', $request['uuid'] );
		}

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}
}