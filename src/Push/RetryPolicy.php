<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Pure-function decision table for batch failures.
 *
 * Backoffs per spec: 30s, 5min, 30min — three attempts then fail.
 * 4xx (non-429) is non-retriable. 401/403 short-circuit the whole job.
 * 429 honors Retry-After when present, otherwise falls back to backoff.
 */
class RetryPolicy {

	public const MAX_ATTEMPTS = 3;
	public const BACKOFFS     = array( 30, 300, 1800 );

	public function decide( int $attempt, MulticastResult $result ): RetryDecision {
		if ( $result->ok ) {
			return RetryDecision::done();
		}

		if ( $result->error_code === MulticastClient::ERROR_AUTH ) {
			return RetryDecision::abort_auth( $result->error_message );
		}

		if ( $result->error_code === MulticastClient::ERROR_CLIENT ) {
			return RetryDecision::fail( $result->error_message );
		}

		if ( $result->error_code === MulticastClient::ERROR_RATE_LIMITED ) {
			if ( $result->retry_after !== null && $result->retry_after > 0 ) {
				return RetryDecision::retry( $result->retry_after );
			}
		}

		if ( $attempt > self::MAX_ATTEMPTS ) {
			return RetryDecision::fail( $result->error_message );
		}

		$delay = self::BACKOFFS[ $attempt - 1 ] ?? null;
		if ( $delay === null ) {
			return RetryDecision::fail( $result->error_message );
		}

		return RetryDecision::retry( $delay );
	}
}
