<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Thin wrapper around `POST /v2/bot/message/multicast`.
 *
 * Caller is responsible for chunking the recipient list to ≤500 IDs
 * per call (LINE's hard cap) — see {@see PushJobRunner}.
 */
class MulticastClient {

	public const ENDPOINT = 'https://api.line.me/v2/bot/message/multicast';

	public const ERROR_NETWORK       = 'network_error';
	public const ERROR_AUTH          = 'auth';
	public const ERROR_RATE_LIMITED  = 'rate_limited';
	public const ERROR_CLIENT        = 'client_error';
	public const ERROR_SERVER        = 'server_error';
	public const ERROR_INVALID_INPUT = 'invalid_input';

	/**
	 * @param list<string>              $line_user_ids
	 * @param list<array<string,mixed>> $messages       LINE Messaging API message objects.
	 */
	public function send( string $access_token, array $line_user_ids, array $messages ): MulticastResult {
		if ( $line_user_ids === array() || $messages === array() || $access_token === '' ) {
			return new MulticastResult(
				ok: false,
				error_code: self::ERROR_INVALID_INPUT,
				error_message: __( 'multicast requires access token, recipients, and at least one message', 'bot-cat' )
			);
		}

		$response = wp_remote_post(
			self::ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
				'body'    => wp_json_encode(
					array(
						'to'       => array_values( $line_user_ids ),
						'messages' => array_values( $messages ),
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new MulticastResult(
				ok: false,
				error_code: self::ERROR_NETWORK,
				error_message: __( 'Could not reach LINE.', 'bot-cat' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		if ( $code >= 200 && $code < 300 ) {
			return new MulticastResult( ok: true, http_status: $code );
		}

		return $this->classify_error( $code, $body, $response );
	}

	/**
	 * @param mixed $response The raw wp_remote_* return — opaque object passed through to wp_remote_retrieve_header.
	 */
	private function classify_error( int $code, string $body, $response ): MulticastResult {
		$payload = json_decode( $body, true );
		$message = is_array( $payload ) && isset( $payload['message'] )
			? (string) $payload['message']
			: $body;

		if ( $code === 401 || $code === 403 ) {
			return new MulticastResult(
				ok: false,
				http_status: $code,
				error_code: self::ERROR_AUTH,
				error_message: $message
			);
		}

		if ( $code === 429 ) {
			$retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );

			return new MulticastResult(
				ok: false,
				http_status: $code,
				retry_after: $retry_after > 0 ? $retry_after : null,
				error_code: self::ERROR_RATE_LIMITED,
				error_message: $message
			);
		}

		if ( $code >= 500 ) {
			return new MulticastResult(
				ok: false,
				http_status: $code,
				error_code: self::ERROR_SERVER,
				error_message: $message
			);
		}

		return new MulticastResult(
			ok: false,
			http_status: $code,
			error_code: self::ERROR_CLIENT,
			error_message: $message
		);
	}
}
