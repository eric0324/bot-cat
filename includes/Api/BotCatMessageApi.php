<?php

/**
 * Class BotCatMessageApi.
 *
 * This class provides functionality for storing messages in the bot_cat_messages option.
 */

class BotCatMessageApi {
	private BotCatAuthService $botCatBasicAuthService;

	public function __construct() {
		$this->botCatBasicAuthService = new BotCatAuthService();
	}

	public function register_rest_route(): void {
		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/messages', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'bot_cat_store_messages' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * Stores messages in the bot_cat_messages option.
	 *
	 * @param array $request The request data containing messages.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_store_messages( $request ): void {
		$can_access = $this->botCatBasicAuthService->bot_cat_check_key( $request );

		if ( ! $can_access ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		update_option( BOT_CAT_OPTION_PREFIX . 'messages', $request['messages'] );
	}


}