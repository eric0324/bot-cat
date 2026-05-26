<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Computes the timestamp each batch should be scheduled at so the
 * job stays under LINE's 30-multicast-per-minute ceiling.
 *
 * Strategy: even spacing — `60 / max_per_minute` seconds between
 * consecutive batches.
 */
class RateLimiter {

	public const MAX_PER_MINUTE = 30;

	private const SECONDS_PER_SLOT = 60 / self::MAX_PER_MINUTE;

	public function __construct( private readonly int $base_timestamp ) {
	}

	public function slot_for( int $batch_index ): int {
		return $this->base_timestamp + (int) ceil( $batch_index * self::SECONDS_PER_SLOT );
	}
}
