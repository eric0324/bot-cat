<?php
/**
 * @package BotCat\Flex
 */

declare(strict_types=1);

namespace BotCat\Flex;

/**
 * Immutable Pro-edition Flex Message template definition.
 *
 * `headline`, `body`, and each CTA url template support the same
 * tokens as the free-edition text template.
 */
final class FlexTemplate {

	public const MAX_CTAS = 3;

	public const HERO_FEATURED = 'featured';
	public const HERO_ATTACHED = 'attached';
	public const HERO_DEFAULT  = 'default';
	public const HERO_NONE     = 'none';

	/**
	 * @param list<array{label:string, url_template:string}> $ctas
	 */
	public function __construct(
		public readonly string $hero_source,
		public readonly string $default_image_url,
		public readonly string $headline,
		public readonly string $body,
		public readonly array $ctas
	) {
	}

	public function with_ctas( array $ctas ): self {
		return new self(
			$this->hero_source,
			$this->default_image_url,
			$this->headline,
			$this->body,
			array_slice( $ctas, 0, self::MAX_CTAS )
		);
	}
}
