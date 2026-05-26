<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Pure-PHP request handler for `GET /l/{code}`.
 *
 * Looks up the code, verifies the (optional) subscriber token, hashes
 * the client IP with a site-specific salt, writes a click row, and
 * returns the destination URL.
 *
 * Returns `['status' => 301|404, 'location' => ?string]` — the caller
 * is responsible for the actual HTTP response.
 */
class ClickRedirectHandler {

	public function __construct(
		private readonly ShortLinkRepository $links,
		private readonly ClickLogRepository $clicks,
		private readonly SubscriberTokenSigner $signer,
		private readonly string $ip_salt
	) {
	}

	/**
	 * @return array{status:int, location:?string}
	 */
	public function handle( string $code, ?string $token, string $ip, ?string $user_agent, ?string $referer ): array {
		$link = $this->links->find_by_code( $code );

		if ( $link === null ) {
			return array(
				'status'   => 404,
				'location' => null,
			);
		}

		$subscriber_id = null;
		if ( $token !== null && $token !== '' ) {
			$subscriber_id = $this->signer->verify( $token );
		}

		$ip_hash = hash( 'sha256', $ip . $this->ip_salt );

		$this->clicks->log(
			$link->id,
			$subscriber_id,
			$ip_hash,
			$user_agent,
			$referer
		);

		return array(
			'status'   => 301,
			'location' => $link->original_url,
		);
	}
}
