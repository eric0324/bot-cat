<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

use BotCat\Subscribers\FollowHandler;
use BotCat\Subscribers\UnfollowHandler;
use BotCat\Tags\MessageEventHandler;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Public LINE webhook endpoint.
 *
 * Verifies the X-Line-Signature header before parsing the body, then
 * dispatches each event to the right handler. Always returns HTTP 200
 * after a valid signature, even if a downstream handler throws — per
 * spec the upstream LINE platform shouldn't retry on our internal
 * failures.
 */
final class WebhookEndpoint {

	public const REST_NAMESPACE = 'botcat/v1';
	public const ROUTE          = '/webhook';

	public function __construct(
		private readonly ChannelSettingsRepository $channel_settings,
		private readonly FollowHandler $follow,
		private readonly UnfollowHandler $unfollow,
		private readonly ?MessageEventHandler $messages = null
	) {
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
		$status = $this->handle_request(
			(string) $request->get_body(),
			$request->get_header( 'x_line_signature' )
		);

		return new WP_REST_Response( null, $status );
	}

	/**
	 * Pure-PHP entry point that's easy to unit-test (no WP_REST_Request).
	 */
	public function handle_request( string $raw_body, ?string $signature ): int {
		$channel = $this->channel_settings->get();

		if ( ! $channel->is_configured() ) {
			return 200;
		}

		$verifier = new SignatureVerifier( $channel->channel_secret() );
		if ( ! $verifier->verify( $raw_body, $signature ) ) {
			return 403;
		}

		$payload = json_decode( $raw_body, true );
		if ( ! is_array( $payload ) || ! isset( $payload['events'] ) || ! is_array( $payload['events'] ) ) {
			return 200;
		}

		foreach ( $payload['events'] as $event ) {
			$this->dispatch_event( is_array( $event ) ? $event : array() );
		}

		return 200;
	}

	/**
	 * @param array<string, mixed> $event
	 */
	private function dispatch_event( array $event ): void {
		$type         = isset( $event['type'] ) ? (string) $event['type'] : '';
		$line_user_id = isset( $event['source']['userId'] ) ? (string) $event['source']['userId'] : '';
		$ts_ms        = isset( $event['timestamp'] ) ? (int) $event['timestamp'] : 0;
		$ts           = $ts_ms > 0 ? intdiv( $ts_ms, 1000 ) : time();

		if ( $line_user_id === '' ) {
			return;
		}

		if ( $type === 'follow' ) {
			$this->follow->handle( $line_user_id, $ts );
			return;
		}

		if ( $type === 'unfollow' ) {
			$this->unfollow->handle( $line_user_id, $ts );
			return;
		}

		if ( $type === 'message' && $this->messages !== null ) {
			$message = isset( $event['message'] ) && is_array( $event['message'] ) ? $event['message'] : array();
			$reply   = isset( $event['replyToken'] ) ? (string) $event['replyToken'] : '';
			$is_text = isset( $message['type'] ) && $message['type'] === 'text';
			$body    = $is_text && isset( $message['text'] ) ? (string) $message['text'] : '';

			if ( $reply !== '' && $body !== '' ) {
				$this->messages->handle( $line_user_id, $reply, $body );
			}
		}
	}
}
