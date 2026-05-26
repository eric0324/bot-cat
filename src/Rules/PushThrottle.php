<?php
/**
 * @package BotCat\Rules
 */

declare(strict_types=1);

namespace BotCat\Rules;

/**
 * Computes when each new push job is allowed to fire so the channel
 * stays under the admin-configured minimum interval.
 *
 * Cascade semantics: each successful call to {@see next_slot()}
 * advances the stored "next allowed" timestamp by one interval, so
 * three publishes within the window fan out to time(0), +interval,
 * +2*interval.
 */
class PushThrottle {

	public const OPTION_INTERVAL_MIN    = 'botcat_push_throttle_minutes';
	public const OPTION_NEXT_ALLOWED_AT = 'botcat_push_next_allowed_at';

	public function interval_seconds(): int {
		$minutes = (int) get_option( self::OPTION_INTERVAL_MIN, 0 );
		return max( 0, $minutes ) * 60;
	}

	public function next_slot( ?int $now = null ): int {
		$now      = $now ?? time();
		$interval = $this->interval_seconds();

		if ( $interval <= 0 ) {
			return $now;
		}

		$stored = (int) get_option( self::OPTION_NEXT_ALLOWED_AT, 0 );
		$slot   = max( $now, $stored );

		update_option( self::OPTION_NEXT_ALLOWED_AT, $slot + $interval );

		return $slot;
	}
}
