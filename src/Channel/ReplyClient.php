<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Thin wrapper around `POST /v2/bot/message/reply`.
 *
 * The replyToken returned with each LINE event is single-use and
 * expires within ~30 minutes — fire-and-forget is fine; failures are
 * logged but never throw to the caller.
 */
class ReplyClient {

	public const ENDPOINT = 'https://api.line.me/v2/bot/message/reply';

	public function reply( string $access_token, string $reply_token, string $text ): bool {
		if ( $access_token === '' || $reply_token === '' || $text === '' ) {
			return false;
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 10,
				'body'    => wp_json_encode(
					array(
						'replyToken' => $reply_token,
						'messages'   => array(
							array(
								'type' => 'text',
								'text' => $text,
							),
						),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return (int) wp_remote_retrieve_response_code( $response ) === 200;
	}
}
