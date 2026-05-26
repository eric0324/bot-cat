<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Owns the three license-related options.
 *
 *  - botcat_license_key    : the raw key the admin pasted
 *  - botcat_license_status : the current LicenseStatus string
 *  - botcat_license_cache  : JSON snapshot from Polar (LicenseCache)
 *
 * No other class reads these options directly — Pro features
 * consult LicenseGate.
 */
class LicenseRepository {

	public const OPTION_KEY    = 'botcat_license_key';
	public const OPTION_STATUS = 'botcat_license_status';
	public const OPTION_CACHE  = 'botcat_license_cache';

	public function get_key(): string {
		return (string) get_option( self::OPTION_KEY, '' );
	}

	public function save_key( string $key ): void {
		update_option( self::OPTION_KEY, $key );
	}

	public function get_status(): string {
		$stored = (string) get_option( self::OPTION_STATUS, LicenseStatus::INACTIVE );
		return in_array( $stored, LicenseStatus::ALL, true ) ? $stored : LicenseStatus::INACTIVE;
	}

	public function save_status( string $status ): void {
		if ( ! in_array( $status, LicenseStatus::ALL, true ) ) {
			$status = LicenseStatus::INACTIVE;
		}
		update_option( self::OPTION_STATUS, $status );
	}

	public function get_cache(): LicenseCache {
		$stored = get_option( self::OPTION_CACHE, array() );
		if ( ! is_array( $stored ) ) {
			return LicenseCache::empty();
		}
		return LicenseCache::from_array( $stored );
	}

	public function save_cache( LicenseCache $cache ): void {
		update_option( self::OPTION_CACHE, $cache->to_array() );
	}

	public function clear(): void {
		delete_option( self::OPTION_KEY );
		delete_option( self::OPTION_STATUS );
		delete_option( self::OPTION_CACHE );
	}
}
