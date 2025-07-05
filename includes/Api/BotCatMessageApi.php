<?php

/**
 * Class BotCatMessageApi.
 *
 * This class provides functionality for storing messages in the bot_cat_messages option.
 */

namespace BotCat\Api;


class BotCatMessageApi {

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
	 */
	public function bot_cat_store_messages( $request ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		if ( isset( $request['messages'] ) && is_array( $request['messages'] ) ) {
			update_option( BOT_CAT_OPTION_PREFIX . 'messages', $request['messages'] );
			wp_send_json( [ 'Message' => 'Success' ], 200 );
		} else {
			wp_send_json( [ 'Message' => 'Invalid messages data' ], 400 );
		}
	}
}