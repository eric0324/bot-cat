<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Possible states of `botcat_license_status`.
 */
final class LicenseStatus {

	public const INACTIVE = 'inactive';
	public const ACTIVE   = 'active';
	public const EXPIRED  = 'expired';
	public const INVALID  = 'invalid';
	public const PENDING  = 'pending';

	public const ALL = array(
		self::INACTIVE,
		self::ACTIVE,
		self::EXPIRED,
		self::INVALID,
		self::PENDING,
	);
}
