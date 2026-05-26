<?php
/**
 * @package BotCat\Flex
 */

declare(strict_types=1);

namespace BotCat\Flex;

use BotCat\Template\TemplateRenderer;

/**
 * Produces a LINE Flex Message v2 payload (single bubble) from a
 * {@see FlexTemplate} and a post.
 *
 * Output shape:
 *   { type: 'flex', altText: …, contents: { type: 'bubble', hero?, body, footer? } }
 */
class FlexMessageBuilder {

	public const ALT_TEXT_LIMIT = 400;

	public function __construct(
		private readonly TemplateRenderer $renderer,
		private readonly HeroImageResolver $hero
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function build( FlexTemplate $template, object $post ): array {
		$headline = $this->renderer->render( $template->headline, $post );
		$body     = $this->renderer->render( $template->body, $post );

		$post_id  = isset( $post->ID ) ? (int) $post->ID : 0;
		$hero_url = $this->hero->resolve( $template->hero_source, $template->default_image_url, $post_id );

		$bubble = array(
			'type' => 'bubble',
			'body' => $this->build_body( $headline, $body ),
		);

		if ( $hero_url !== null ) {
			$bubble['hero'] = array(
				'type'        => 'image',
				'url'         => $hero_url,
				'size'        => 'full',
				'aspectRatio' => '20:13',
				'aspectMode'  => 'cover',
			);
		}

		$footer = $this->build_footer( $template, $post );
		if ( $footer !== null ) {
			$bubble['footer'] = $footer;
		}

		return array(
			'type'     => 'flex',
			'altText'  => $this->derive_alt_text( $headline, $body ),
			'contents' => $bubble,
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	private function build_body( string $headline, string $body ): array {
		$contents = array(
			array(
				'type'   => 'text',
				'text'   => $headline,
				'weight' => 'bold',
				'size'   => 'xl',
				'wrap'   => true,
			),
		);

		if ( $body !== '' ) {
			$contents[] = array(
				'type'   => 'text',
				'text'   => $body,
				'size'   => 'md',
				'wrap'   => true,
				'color'  => '#666666',
				'margin' => 'md',
			);
		}

		return array(
			'type'     => 'box',
			'layout'   => 'vertical',
			'spacing'  => 'md',
			'contents' => $contents,
		);
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function build_footer( FlexTemplate $template, object $post ): ?array {
		if ( $template->ctas === array() ) {
			return null;
		}

		$buttons = array();
		foreach ( array_slice( $template->ctas, 0, FlexTemplate::MAX_CTAS ) as $cta ) {
			$label = isset( $cta['label'] ) ? (string) $cta['label'] : '';
			$uri   = $this->renderer->render( (string) ( $cta['url_template'] ?? '' ), $post );

			if ( $label === '' || $uri === '' ) {
				continue;
			}

			$buttons[] = array(
				'type'   => 'button',
				'style'  => 'primary',
				'height' => 'sm',
				'action' => array(
					'type'  => 'uri',
					'label' => $label,
					'uri'   => $uri,
				),
			);
		}

		if ( $buttons === array() ) {
			return null;
		}

		return array(
			'type'     => 'box',
			'layout'   => 'vertical',
			'spacing'  => 'sm',
			'contents' => $buttons,
		);
	}

	private function derive_alt_text( string $headline, string $body ): string {
		$raw = trim( $headline . ' ' . $body );
		if ( $raw === '' ) {
			$raw = 'A new post is available.';
		}

		if ( mb_strlen( $raw, 'UTF-8' ) <= self::ALT_TEXT_LIMIT ) {
			return $raw;
		}

		return mb_substr( $raw, 0, self::ALT_TEXT_LIMIT - 1, 'UTF-8' ) . '…';
	}
}
