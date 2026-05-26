<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Outcome of one call to Polar's license validation endpoint.
 *
 * - `ok` is true only when Polar returned a 2xx response; network or
 *   parse failures set it false with a non-null error_code.
 * - `entitled` is only meaningful when ok=true.
 */
final class PolarValidationResult {

	public const ERROR_NETWORK  = 'network_error';
	public const ERROR_INVALID  = 'invalid_key';
	public const ERROR_UPSTREAM = 'upstream_error';

	public function __construct(
		public readonly bool $ok,
		public readonly bool $entitled = false,
		public readonly ?string $plan_name = null,
		public readonly ?string $renews_at = null,
		public readonly ?string $error_code = null,
		public readonly ?string $error_message = null
	) {
	}
}
