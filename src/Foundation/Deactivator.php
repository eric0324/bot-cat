<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Clears scheduled jobs on deactivation. User data is preserved.
 */
final class Deactivator {

	public static function deactivate(): void {
		$next = wp_next_scheduled( Activator::CRON_HOOK );
		if ( $next ) {
			wp_unschedule_event( $next, Activator::CRON_HOOK );
		}
	}
}
