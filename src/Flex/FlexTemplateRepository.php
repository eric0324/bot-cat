<?php
/**
 * @package BotCat\Flex
 */

declare(strict_types=1);

namespace BotCat\Flex;

/**
 * Persists the active Flex Message template.
 *
 * Stored as an associative array option so existing WordPress
 * autoload caching applies. Sanitises the CTA list down to
 * FlexTemplate::MAX_CTAS on save.
 */
class FlexTemplateRepository {

	public const OPTION = 'botcat_flex_template';

	public function get(): FlexTemplate {
		$stored = get_option( self::OPTION, false );

		if ( ! is_array( $stored ) ) {
			return $this->defaults();
		}

		$ctas = array();
		if ( isset( $stored['ctas'] ) && is_array( $stored['ctas'] ) ) {
			foreach ( $stored['ctas'] as $cta ) {
				if ( ! is_array( $cta ) ) {
					continue;
				}
				$ctas[] = array(
					'label'        => isset( $cta['label'] ) ? (string) $cta['label'] : '',
					'url_template' => isset( $cta['url_template'] ) ? (string) $cta['url_template'] : '',
				);
			}
		}

		return new FlexTemplate(
			hero_source: isset( $stored['hero_source'] ) ? (string) $stored['hero_source'] : FlexTemplate::HERO_FEATURED,
			default_image_url: isset( $stored['default_image_url'] ) ? (string) $stored['default_image_url'] : '',
			headline: isset( $stored['headline'] ) ? (string) $stored['headline'] : $this->defaults()->headline,
			body: isset( $stored['body'] ) ? (string) $stored['body'] : $this->defaults()->body,
			ctas: array_slice( $ctas, 0, FlexTemplate::MAX_CTAS )
		);
	}

	public function save( FlexTemplate $template ): void {
		update_option(
			self::OPTION,
			array(
				'hero_source'       => $template->hero_source,
				'default_image_url' => $template->default_image_url,
				'headline'          => $template->headline,
				'body'              => $template->body,
				'ctas'              => array_slice( $template->ctas, 0, FlexTemplate::MAX_CTAS ),
			)
		);
	}

	private function defaults(): FlexTemplate {
		return new FlexTemplate(
			hero_source: FlexTemplate::HERO_FEATURED,
			default_image_url: '',
			headline: '🆕 {title}',
			body: '{excerpt}',
			ctas: array(
				array(
					'label'        => __( 'Read more', 'bot-cat' ),
					'url_template' => '{permalink}',
				),
			)
		);
	}
}
