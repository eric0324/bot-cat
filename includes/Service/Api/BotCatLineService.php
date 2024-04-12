<?php

/**
 * Class BotCatLineService
 *
 * This class is responsible for sending text messages to the LINE messaging platform.
 */

namespace BotCat\Service\Api;

use JsonException;

defined( 'ABSPATH' ) || exit;

class BotCatLineService {

	private string $multicast_message_url;

	public function __construct() {
		$this->multicast_message_url = 'https://api.line.me/v2/bot/message/multicast';
	}

	/**
	 * Sends a text message through the LINE Messaging API.
	 *
	 * @param string $to The ID of the recipient.
	 * @param string $message The text message to send.
	 *
	 * @return void The decoded JSON response from the API.
	 * @throws JsonException
	 */
	public function bot_cat_send_text_message( string $to, string $message ): void {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'line' );

		$response = wp_remote_post( $this->multicast_message_url,
			[
				'method'      => 'POST',
				'headers'     => [
					'Content-Type'  => 'application/json; charset=utf-8',
					'Authorization' => 'Bearer ' . $options['channel_access_token']
				],
				'data_format' => 'body',
				'body'        => json_encode( [
					'to'       => $to,
					'messages' => [
						0 => [
							'type' => 'text',
							'text' => $message
						]
					]
				], JSON_THROW_ON_ERROR )
			],
		);

		$json = $response['body'] ?? [];

		json_decode( $json, false, 512, JSON_THROW_ON_ERROR );
	}
}