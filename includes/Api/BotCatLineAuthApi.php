<?php

/**
 * Class BotCatLineAuthApi
 *
 * This class provides functionality related to LINE authentication for the BotCat plugin.
 */

namespace BotCat\Api;


class BotCatLineAuthApi {

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
	 * Store LINE Bot access token
	 *
	 * @param $request
	 *
	 * @return void
	 */
	public function bot_cat_store_token( $request ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		$options = array_merge( $options, [
			'channel_access_token' => sanitize_text_field( $request['channel_access_token'] ),
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
	 */
	public function bot_cat_store_uuid( $request ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json( [ 'Message' => 'Unauthorized' ], 401 );
		}

		$user_id = get_current_user_id();
		if ( $user_id && isset( $request['uuid'] ) ) {
			update_user_meta( $user_id, BOT_CAT_OPTION_PREFIX . 'line_uuid', sanitize_text_field( $request['uuid'] ) );
		}

		wp_send_json( [ 'Message' => 'Success' ], 200 );
	}
}