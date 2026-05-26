<?php
/**
 * @package BotCat\Analytics
 */

declare(strict_types=1);

namespace BotCat\Analytics;

/**
 * Immutable representation of the dashboard's selected time window.
 *
 * Days = 7 / 30 / 90; defaults to 30 per spec. Clamp prevents abuse.
 */
final class AnalyticsWindow {

	public const ALLOWED_DAYS = array( 7, 30, 90 );
	public const DEFAULT_DAYS = 30;

	public readonly int $days;
	public readonly string $start_gmt;
	public readonly string $end_gmt;

	public function __construct( int $days, ?int $now = null ) {
		$this->days      = in_array( $days, self::ALLOWED_DAYS, true ) ? $days : self::DEFAULT_DAYS;
		$end             = $now ?? time();
		$this->end_gmt   = gmdate( 'Y-m-d H:i:s', $end );
		$this->start_gmt = gmdate( 'Y-m-d H:i:s', $end - $this->days * 86400 );
	}
}
