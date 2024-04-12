<?php

namespace BotCat\Service\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Class BotCatSlackService
 *
 * This class provides functionality to send text messages to Slack using the Bot Cat API.
 *
 * @package YourPackageNameHere
 */
class BotCatSlackService {

	private $bot_api_url;
	private $send_message_path;


	public function bot_cat_send_text_message( $to, $message ) {
		$response = wp_remote_post( $to,
			[
				'method'  => 'POST',
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
				],
				'body'    => json_encode( [ 'text' => $message ], JSON_THROW_ON_ERROR )
			],
		);

		$json = $response['body'] ?? [];

		return json_decode( $json, false, 512, JSON_THROW_ON_ERROR );
	}
}