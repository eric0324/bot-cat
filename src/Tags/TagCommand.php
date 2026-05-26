<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

/**
 * Parsed representation of a `/tag …` text command received via the
 * LINE webhook.
 */
final class TagCommand {

	public const TYPE_NOT_A_COMMAND = 'not_a_command';
	public const TYPE_SUBSCRIBE     = 'subscribe';
	public const TYPE_UNSUBSCRIBE   = 'unsubscribe';
	public const TYPE_LIST          = 'list';
	public const TYPE_UNKNOWN       = 'unknown';

	public function __construct(
		public readonly string $type,
		public readonly ?string $keyword = null
	) {
	}

	public static function not_a_command(): self {
		return new self( self::TYPE_NOT_A_COMMAND );
	}

	public static function subscribe( string $keyword ): self {
		return new self( self::TYPE_SUBSCRIBE, $keyword );
	}

	public static function unsubscribe( string $keyword ): self {
		return new self( self::TYPE_UNSUBSCRIBE, $keyword );
	}

	public static function list(): self {
		return new self( self::TYPE_LIST );
	}

	public static function unknown(): self {
		return new self( self::TYPE_UNKNOWN );
	}
}
