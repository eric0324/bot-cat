<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Public webhook receiving Polar subscription / license-key events.
 *
 * Signature header (`webhook-signature` per Polar docs) is verified
 * with HMAC-SHA256 against a shared secret configured in
 * `botcat_polar_webhook_secret`.
 *
 * Recognised event types:
 *   subscription.cancelled    → set expired on the listed key
 *   subscription.refunded     → same
 *   license_key.revoked       → same
 */
class PolarWebhookEndpoint {

	public const REST_NAMESPACE = 'botcat/v1';
	public const ROUTE          = '/polar-webhook';
	public const SECRET_OPTION  = 'botcat_polar_webhook_secret';

	public function __construct( private readonly LicenseRepository $repository ) {
	}

	public function register(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => static fn(): bool => true,
			)
		);
	}

	public function handle( WP_REST_Request $request ): WP_REST_Response {
		$secret = (string) get_option( self::SECRET_OPTION, '' );
		if ( $secret === '' ) {
			return new WP_REST_Response( null, 503 );
		}

		$body      = (string) $request->get_body();
		$signature = (string) $request->get_header( 'webhook_signature' );

		$expected = base64_encode( hash_hmac( 'sha256', $body, $secret, true ) );
		if ( $signature === '' || ! hash_equals( $expected, $signature ) ) {
			return new WP_REST_Response( null, 403 );
		}

		$payload = json_decode( $body, true );
		if ( ! is_array( $payload ) ) {
			return new WP_REST_Response( null, 200 );
		}

		$type = isset( $payload['type'] ) ? (string) $payload['type'] : '';
		if ( in_array( $type, array( 'subscription.cancelled', 'subscription.refunded', 'license_key.revoked' ), true ) ) {
			$this->mark_expired();
		}

		return new WP_REST_Response( null, 200 );
	}

	private function mark_expired(): void {
		$cache = $this->repository->get_cache();
		$this->repository->save_cache(
			new LicenseCache(
				entitled: false,
				verified_at: $cache->verified_at,
				last_failure_at: time(),
				consecutive_failures: max( 1, $cache->consecutive_failures ),
				plan_name: $cache->plan_name,
				renews_at: $cache->renews_at,
			)
		);
		$this->repository->save_status( LicenseStatus::EXPIRED );
	}
}
