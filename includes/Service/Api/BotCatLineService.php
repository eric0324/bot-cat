<?php

/**
 * Class BotCatLineService
 *
 * This class is responsible for sending text messages to the LINE messaging platform.
 */

namespace BotCat\Service\Api;

use JsonException;


class BotCatLineService {

	private string $multicast_message_url;
	private string $push_message_url;

	public function __construct() {
		$this->multicast_message_url = 'https://api.line.me/v2/bot/message/multicast';
		$this->push_message_url      = 'https://api.line.me/v2/bot/message/push';
	}

	/**
	 * Sends a text message through the LINE Messaging API to multiple recipients.
	 *
	 * @param array $to Array of recipient LINE User IDs.
	 * @param string $message The text message to send.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_send_text_message( array $to, string $message ): void {
		// Skip if no recipients
		if ( empty( $to ) ) {
			return;
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		// Check if access token is configured
		if ( ! isset( $options['channel_access_token'] ) || empty( $options['channel_access_token'] ) ) {
			error_log( 'BotCat: LINE Channel Access Token not configured' );
			return;
		}

		$request_body = [
			'to'       => $to,
			'messages' => [
				[
					'type' => 'text',
					'text' => $message
				]
			]
		];

		$response = wp_remote_post( $this->multicast_message_url, [
			'method'  => 'POST',
			'headers' => [
				'Content-Type'  => 'application/json; charset=utf-8',
				'Authorization' => 'Bearer ' . $options['channel_access_token']
			],
			'body'    => json_encode( $request_body, JSON_THROW_ON_ERROR ),
			'timeout' => 30
		] );

		// Handle response
		if ( is_wp_error( $response ) ) {
			error_log( 'BotCat LINE API Error: ' . $response->get_error_message() );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'BotCat LINE API Error: HTTP ' . $response_code . ' - ' . $response_body );
		}
	}

	/**
	 * Send a text message to a single LINE user
	 *
	 * @param string $to LINE User ID
	 * @param string $message Text message to send
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_send_push_message( string $to, string $message ): void {
		if ( empty( $to ) ) {
			return;
		}

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		// Check if access token is configured
		if ( ! isset( $options['channel_access_token'] ) || empty( $options['channel_access_token'] ) ) {
			error_log( 'BotCat: LINE Channel Access Token not configured' );
			return;
		}

		$request_body = [
			'to'       => $to,
			'messages' => [
				[
					'type' => 'text',
					'text' => $message
				]
			]
		];

		$response = wp_remote_post( $this->push_message_url, [
			'method'  => 'POST',
			'headers' => [
				'Content-Type'  => 'application/json; charset=utf-8',
				'Authorization' => 'Bearer ' . $options['channel_access_token']
			],
			'body'    => json_encode( $request_body, JSON_THROW_ON_ERROR ),
			'timeout' => 30
		] );

		// Handle response
		if ( is_wp_error( $response ) ) {
			error_log( 'BotCat LINE API Error: ' . $response->get_error_message() );
			return;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			$response_body = wp_remote_retrieve_body( $response );
			error_log( 'BotCat LINE API Error: HTTP ' . $response_code . ' - ' . $response_body );
		}
	}
}