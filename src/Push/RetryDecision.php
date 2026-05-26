<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Outcome of {@see RetryPolicy::decide()}.
 *
 * - ACTION_DONE       — request succeeded; no follow-up needed
 * - ACTION_RETRY      — schedule another attempt in `delay_seconds`
 * - ACTION_FAIL       — permanent failure for this batch
 * - ACTION_ABORT_AUTH — credentials problem; abort the whole job
 */
final class RetryDecision {

	public const ACTION_DONE       = 'done';
	public const ACTION_RETRY      = 'retry';
	public const ACTION_FAIL       = 'fail';
	public const ACTION_ABORT_AUTH = 'abort_auth';

	private function __construct(
		public readonly string $action,
		public readonly int $delay_seconds = 0,
		public readonly ?string $error = null
	) {
	}

	public static function done(): self {
		return new self( self::ACTION_DONE );
	}

	public static function retry( int $delay_seconds ): self {
		return new self( self::ACTION_RETRY, max( 0, $delay_seconds ) );
	}

	public static function fail( ?string $error = null ): self {
		return new self( self::ACTION_FAIL, 0, $error );
	}

	public static function abort_auth( ?string $error = null ): self {
		return new self( self::ACTION_ABORT_AUTH, 0, $error );
	}
}
