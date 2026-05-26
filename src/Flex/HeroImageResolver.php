<?php
/**
 * @package BotCat\Flex
 */

declare(strict_types=1);

namespace BotCat\Flex;

/**
 * Resolves the hero image URL for a Flex Message at render time.
 *
 * Strategy order — always falls through to the next source on miss:
 *   featured  → attached → default URL → null (omit hero block)
 */
class HeroImageResolver {

	public function resolve( string $strategy, string $default_url, int $post_id ): ?string {
		if ( $strategy === FlexTemplate::HERO_NONE ) {
			return null;
		}

		if ( $post_id > 0 ) {
			$featured = $this->featured( $post_id );
			if ( $featured !== null ) {
				return $featured;
			}

			$attached = $this->first_attached( $post_id );
			if ( $attached !== null ) {
				return $attached;
			}
		}

		return $default_url !== '' ? $default_url : null;
	}

	private function featured( int $post_id ): ?string {
		if ( ! function_exists( 'has_post_thumbnail' ) || ! has_post_thumbnail( $post_id ) ) {
			return null;
		}

		$thumbnail_id = (int) get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id <= 0 ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $thumbnail_id, 'large' );
		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return null;
		}

		return (string) $src[0];
	}

	private function first_attached( int $post_id ): ?string {
		if ( ! function_exists( 'get_attached_media' ) ) {
			return null;
		}

		$media = get_attached_media( 'image', $post_id );
		if ( ! is_array( $media ) || $media === array() ) {
			return null;
		}

		$first = reset( $media );
		if ( ! is_object( $first ) || ! isset( $first->ID ) ) {
			return null;
		}

		$src = wp_get_attachment_image_src( (int) $first->ID, 'large' );
		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return null;
		}

		return (string) $src[0];
	}
}
