<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Calls Polar's license-validate / deactivate endpoints.
 *
 * Endpoints are constants here so a test or a downstream theme can
 * override via DI if Polar's hostname changes.
 */
class PolarClient {

	public const ENDPOINT_VALIDATE   = 'https://api.polar.sh/v1/license-keys/validate';
	public const ENDPOINT_DEACTIVATE = 'https://api.polar.sh/v1/license-keys/deactivate';

	public function validate( string $key ): PolarValidationResult {
		if ( $key === '' ) {
			return new PolarValidationResult(
				ok: false,
				error_code: PolarValidationResult::ERROR_INVALID,
				error_message: __( 'License key is empty.', 'bot-cat' )
			);
		}

		$response = wp_remote_post(
			self::ENDPOINT_VALIDATE,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'timeout' => 15,
				'body'    => wp_json_encode( array( 'key' => $key ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return new PolarValidationResult(
				ok: false,
				error_code: PolarValidationResult::ERROR_NETWORK,
				error_message: __( 'Could not reach Polar.', 'bot-cat' )
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		/** @var array<string, mixed>|null $payload */
		$payload = json_decode( $body, true );

		if ( $code === 401 || $code === 403 || $code === 404 ) {
			return new PolarValidationResult(
				ok: false,
				error_code: PolarValidationResult::ERROR_INVALID,
				error_message: is_array( $payload ) && isset( $payload['detail'] ) ? (string) $payload['detail'] : $body
			);
		}

		if ( $code < 200 || $code >= 300 || ! is_array( $payload ) ) {
			return new PolarValidationResult(
				ok: false,
				error_code: PolarValidationResult::ERROR_UPSTREAM,
				error_message: $body
			);
		}

		$entitled  = ! empty( $payload['valid'] ) || ! empty( $payload['entitled'] );
		$plan_name = $this->extract_plan_name( $payload );
		$renews_at = isset( $payload['expires_at'] ) ? (string) $payload['expires_at'] : null;

		return new PolarValidationResult(
			ok: true,
			entitled: $entitled,
			plan_name: $plan_name,
			renews_at: $renews_at
		);
	}

	public function deactivate( string $key ): bool {
		if ( $key === '' ) {
			return false;
		}

		$response = wp_remote_post(
			self::ENDPOINT_DEACTIVATE,
			array(
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'timeout' => 15,
				'body'    => wp_json_encode( array( 'key' => $key ) ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 300;
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private function extract_plan_name( array $payload ): ?string {
		foreach ( array( 'plan_name', 'product_name', 'tier' ) as $key ) {
			if ( isset( $payload[ $key ] ) && (string) $payload[ $key ] !== '' ) {
				return (string) $payload[ $key ];
			}
		}

		if ( isset( $payload['benefit'] ) && is_array( $payload['benefit'] ) && isset( $payload['benefit']['description'] ) ) {
			return (string) $payload['benefit']['description'];
		}

		return null;
	}
}
