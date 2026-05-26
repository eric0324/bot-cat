<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Pure-function decision: given the cached Polar response and the
 * current time, what is the effective license state?
 *
 * Grace policy (per spec): the plugin keeps Pro enabled for up to
 * seven days past the most recent confirmed-entitled response. After
 * that window, network outages AND revoked entitlements both flow
 * into EXPIRED.
 */
class LicenseStatusEvaluator {

	public const GRACE_DAYS    = 7;
	public const GRACE_SECONDS = self::GRACE_DAYS * 86400;

	public function evaluate( LicenseCache $cache, int $now ): string {
		if ( $cache->verified_at === 0 ) {
			return LicenseStatus::INACTIVE;
		}

		if ( $now - $cache->verified_at <= self::GRACE_SECONDS ) {
			return LicenseStatus::ACTIVE;
		}

		return LicenseStatus::EXPIRED;
	}

	public function days_since_verified( LicenseCache $cache, int $now ): int {
		if ( $cache->verified_at === 0 ) {
			return 0;
		}
		return (int) floor( ( $now - $cache->verified_at ) / 86400 );
	}
}
