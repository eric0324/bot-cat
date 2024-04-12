<?php

namespace BotCat\Service\Api;

/**
 * Class BotCatTelegramService
 * Represents a service for sending text messages through the Telegram Bot API.
 */

defined( 'ABSPATH' ) || exit;

class BotCatTelegramService {

	private string $bot_api_url;
	private string $send_message_path;

	public function __construct() {
		$this->bot_api_url       = 'https://api.telegram.org/bot';
		$this->send_message_path = '/sendMessage';
	}

	/**
	 * Sends a text message to the specified recipients using the Telegram Bot API.
	 *
	 * @param array $to An array of recipient UUIDs.
	 * @param string $message The text message to send.
	 *
	 * @return void
	 */
	public function bot_cat_send_text_message( array $to, string $message ): void {

		$options = get_option( BOT_CAT_OPTION_PREFIX . 'telegram' );

		foreach ( $to as $uuid ) {
			$url = $this->bot_api_url . $options['api_token'] . $this->send_message_path . "?chat_id=" . $uuid . "&text=" . urlencode( $message );

			wp_remote_post( $url,
				[
					'method'  => 'GET',
					'headers' => [
						'Content-Type' => 'application/x-www-form-urlencoded'
					]
				]
			);
		}
	}
}