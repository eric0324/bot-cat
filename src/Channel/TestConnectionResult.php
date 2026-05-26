<?php
/**
 * @package BotCat\Channel
 */

declare(strict_types=1);

namespace BotCat\Channel;

/**
 * Outcome of TestConnection::run() — designed to be consumed directly
 * by the Settings page renderer.
 */
final class TestConnectionResult {

	public function __construct(
		public readonly bool $ok,
		public readonly ?string $display_name = null,
		public readonly ?int $http_status = null,
		public readonly ?string $error_code = null,
		public readonly ?string $error_message = null
	) {
	}
}
