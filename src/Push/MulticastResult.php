<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Outcome of one call to LINE's multicast endpoint.
 *
 * `retry_after` is populated only when LINE returned an explicit
 * `Retry-After` header (typically with a 429 response).
 */
final class MulticastResult {

	public function __construct(
		public readonly bool $ok,
		public readonly ?int $http_status = null,
		public readonly ?int $retry_after = null,
		public readonly ?string $error_code = null,
		public readonly ?string $error_message = null
	) {
	}
}
