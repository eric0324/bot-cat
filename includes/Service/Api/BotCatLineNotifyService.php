<?php
/**
 * This class provides functionality to send text messages using the Line Notify API.
 */

namespace BotCat\Service\Api;


defined( 'ABSPATH' ) || exit;

class BotCatLineNotifyService {

	private string $notify_url;

	public function __construct() {
		$this->notify_url = "https://notify-api.line.me/api/";
	}

	/**
	 * Sends a text message to one or more recipients.
	 *
	 * @param array $to An array of recipient UUIDs.
	 * @param string $text The text message to be sent.
	 *
	 * @return void
	 */
	public function bot_cat_send_text_message( array $to, string $text ): void {

		foreach ( $to as $uuid ) {
			$request_params = [
				"headers" => "Authorization: Bearer " . $uuid,
				"body"    => [
					"message" => $text
				]
			];

			wp_remote_post( $this->notify_url . 'notify', $request_params );
		}
	}
}