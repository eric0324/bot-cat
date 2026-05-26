<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Calls LINE's `GET /v2/bot/info` to confirm a Channel Access Token works
 * and reports the OA's display name.
 */
final class TestConnection {

	public const ENDPOINT = 'https://api.line.me/v2/bot/info';

	public function run( ChannelSettings $settings ): TestConnectionResult {
		if ( ! $settings->is_configured() ) {
			return new TestConnectionResult(
				ok: false,
				error_code: 'not_configured',
				error_message: __( 'Connect your LINE channel first.', 'bot-cat' )
			);
		}

		$response = wp_remote_get(
			self::ENDPOINT,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $settings->access_token(),
				),
				'timeout' => 10,
			)
		);

		if ( is_wp_error( $response ) ) {
			return new TestConnectionResult(
				ok: false,
				error_code: 'network_error',
				error_message: __( 'Could not reach LINE.', 'bot-cat' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );

		/** @var array<string, mixed>|null $payload */
		$payload = json_decode( $body, true );

		if ( $code === 200 && is_array( $payload ) ) {
			return new TestConnectionResult(
				ok: true,
				display_name: isset( $payload['displayName'] ) ? (string) $payload['displayName'] : '',
				http_status: 200
			);
		}

		$message = is_array( $payload ) && isset( $payload['message'] )
			? (string) $payload['message']
			: $body;

		return new TestConnectionResult(
			ok: false,
			http_status: $code,
			error_code: 'upstream_error',
			error_message: $message
		);
	}
}
