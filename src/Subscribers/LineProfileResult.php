<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Outcome of {@see LineProfileFetcher::fetch()}.
 */
final class LineProfileResult {

	public function __construct(
		public readonly bool $ok,
		public readonly ?string $display_name = null,
		public readonly ?string $picture_url = null,
		public readonly ?string $error_code = null
	) {
	}
}
