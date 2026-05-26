<?php
/**
 * @package BotCat\Template
 */

declare(strict_types=1);

namespace BotCat\Template;

/**
 * Resolves the standard template tokens against a single post.
 *
 * Pure function: returns a `token-name => value` map. Knows nothing
 * about the template string itself — substitution is in
 * {@see TemplateRenderer}.
 */
class TokenResolver {

	public const TOKENS = array( 'title', 'excerpt', 'permalink', 'author', 'category', 'date', 'site_name' );

	/**
	 * @return array<string, string>
	 */
	public function resolve_for_post( object $post ): array {
		$post_id = isset( $post->ID ) ? (int) $post->ID : 0;

		return array(
			'title'     => (string) get_the_title( $post_id ),
			'excerpt'   => ExcerptFallback::derive(
				isset( $post->post_excerpt ) ? (string) $post->post_excerpt : '',
				isset( $post->post_content ) ? (string) $post->post_content : ''
			),
			'permalink' => (string) get_permalink( $post_id ),
			'author'    => (string) get_the_author_meta( 'display_name', isset( $post->post_author ) ? (int) $post->post_author : 0 ),
			'category'  => $this->resolve_category( $post_id ),
			'date'      => $this->resolve_date( $post ),
			'site_name' => (string) get_bloginfo( 'name' ),
		);
	}

	private function resolve_category( int $post_id ): string {
		/** @var list<string> $names */
		$names = (array) wp_get_post_categories( $post_id, array( 'fields' => 'names' ) );

		return isset( $names[0] ) ? (string) $names[0] : '';
	}

	private function resolve_date( object $post ): string {
		$gmt = isset( $post->post_date_gmt ) ? (string) $post->post_date_gmt : '';
		if ( $gmt === '' ) {
			return '';
		}

		return (string) get_date_from_gmt( $gmt, 'Y-m-d H:i' );
	}
}
