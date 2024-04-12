<?php

/**
 * Class BotCatSlackService
 *
 * This class provides methods for sending text messages to a Slack channel.
 */

namespace BotCat\Service\Api;

use JsonException;

defined( 'ABSPATH' ) || exit;


class BotCatSlackService {


	/**
	 * @throws JsonException
	 */
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