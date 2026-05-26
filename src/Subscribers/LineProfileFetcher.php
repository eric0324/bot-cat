<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Calls `GET https://api.line.me/v2/bot/profile/{user_id}` to fetch
 * display name + picture URL.
 *
 * Best-effort: failures are reported via the result object so the caller
 * can decide whether to retry, not by throwing.
 */
class LineProfileFetcher {

	public const ENDPOINT_PREFIX = 'https://api.line.me/v2/bot/profile/';

	public function fetch( string $line_user_id, string $access_token ): LineProfileResult {
		if ( $line_user_id === '' || $access_token === '' ) {
			return new LineProfileResult( ok: false, error_code: 'missing_input' );
		}

		$response = wp_remote_get(
			self::ENDPOINT_PREFIX . rawurlencode( $line_user_id ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $access_token,
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new LineProfileResult( ok: false, error_code: 'network_error' );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new LineProfileResult( ok: false, error_code: 'http_' . $code );
		}

		$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $payload ) ) {
			return new LineProfileResult( ok: false, error_code: 'invalid_response' );
		}

		return new LineProfileResult(
			ok: true,
			display_name: isset( $payload['displayName'] ) ? (string) $payload['displayName'] : null,
			picture_url: isset( $payload['pictureUrl'] ) ? (string) $payload['pictureUrl'] : null
		);
	}
}
