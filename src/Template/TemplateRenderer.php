<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

/**
 * Substitutes `{token}` placeholders in a template string with values
 * from {@see TokenResolver}.
 *
 * Unknown tokens are intentionally left as literal text so authors can
 * see (and fix) typos rather than ending up with a silent gap.
 */
class TemplateRenderer {

	public function __construct( private readonly TokenResolver $resolver ) {
	}

	public function render( string $template, object $post ): string {
		if ( $template === '' ) {
			return '';
		}

		$values       = $this->resolver->resolve_for_post( $post );
		$replacements = array();

		foreach ( $values as $name => $value ) {
			$replacements[ '{' . $name . '}' ] = (string) $value;
		}

		return strtr( $template, $replacements );
	}
}
