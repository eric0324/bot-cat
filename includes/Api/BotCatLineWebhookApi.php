<?php

/**
 * Class BotCatLineWebhookApi
 *
 * This class handles LINE Bot webhook events for automatic user connection.
 */

namespace BotCat\Api;

use JsonException;

class BotCatLineWebhookApi {

	public function register_rest_route(): void {
		register_rest_route( BOT_CAT_REST_NAMESPACE_PREFIX, '/line/webhook', [
			'methods'             => 'POST',
			'callback'            => [ &$this, 'handle_webhook' ],
			'permission_callback' => '__return_true'
		] );
	}

	/**
	 * Handle LINE Bot webhook events
	 *
	 * @param $request
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function handle_webhook( $request ): void {
		$body = $request->get_body();
		
		if ( empty( $body ) ) {
			wp_send_json( [ 'status' => 'error', 'message' => 'Empty request body' ], 400 );
			return;
		}

		try {
			$data = json_decode( $body, true, 512, JSON_THROW_ON_ERROR );
		} catch ( JsonException $e ) {
			wp_send_json( [ 'status' => 'error', 'message' => 'Invalid JSON' ], 400 );
			return;
		}

		// Validate LINE webhook signature (optional but recommended)
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );
		if ( isset( $options['channel_secret'] ) && ! empty( $options['channel_secret'] ) ) {
			$signature = $request->get_header( 'x-line-signature' );
			if ( ! $this->validate_signature( $body, $signature, $options['channel_secret'] ) ) {
				wp_send_json( [ 'status' => 'error', 'message' => 'Invalid signature' ], 401 );
				return;
			}
		}

		// Process events
		if ( isset( $data['events'] ) && is_array( $data['events'] ) ) {
			foreach ( $data['events'] as $event ) {
				$this->process_event( $event );
			}
		}

		wp_send_json( [ 'status' => 'success' ], 200 );
	}

	/**
	 * Process individual LINE webhook event
	 *
	 * @param array $event
	 * @return void
	 */
	private function process_event( array $event ): void {
		// Only handle message events for now
		if ( ! isset( $event['type'] ) || $event['type'] !== 'message' ) {
			return;
		}

		// Only handle text messages
		if ( ! isset( $event['message']['type'] ) || $event['message']['type'] !== 'text' ) {
			return;
		}

		$user_id = $event['source']['userId'] ?? null;
		if ( empty( $user_id ) ) {
			return;
		}

		// Check if this user ID is already connected to any WordPress user
		$existing_users = get_users( [
			'meta_key'   => BOT_CAT_OPTION_PREFIX . 'line_uuid',
			'meta_value' => $user_id,
			'number'     => 1
		] );

		if ( ! empty( $existing_users ) ) {
			// User already connected
			return;
		}

		// For demo purposes, we'll connect to the first admin user
		// In a real implementation, you might want a different strategy
		$admin_users = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
		
		if ( ! empty( $admin_users ) ) {
			$admin_user = $admin_users[0];
			update_user_meta( $admin_user->ID, BOT_CAT_OPTION_PREFIX . 'line_uuid', $user_id );
			
			// Optionally send a welcome message
			$this->send_welcome_message( $user_id );
		}
	}

	/**
	 * Validate LINE webhook signature
	 *
	 * @param string $body
	 * @param string $signature
	 * @param string $channel_secret
	 * @return bool
	 */
	private function validate_signature( string $body, string $signature, string $channel_secret ): bool {
		if ( empty( $signature ) ) {
			return false;
		}

		$expected_signature = base64_encode( hash_hmac( 'sha256', $body, $channel_secret, true ) );
		return hash_equals( $signature, $expected_signature );
	}

	/**
	 * Send welcome message to newly connected user
	 *
	 * @param string $user_id
	 * @return void
	 */
	private function send_welcome_message( string $user_id ): void {
		$line_service = new \BotCat\Service\Api\BotCatLineService();
		$welcome_message = __( 'Welcome! Your LINE account has been connected to receive notifications.', 'bot-cat' );
		
		try {
			$line_service->bot_cat_send_push_message( $user_id, $welcome_message );
		} catch ( \Exception $e ) {
			error_log( 'BotCat: Failed to send welcome message - ' . $e->getMessage() );
		}
	}
}