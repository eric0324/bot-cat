<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Rewrites in-message URLs to use the short link endpoint.
 *
 * Scope (per link-shortener spec): only the post's own permalink and
 * any literal URL pointing to the same WordPress site are rewritten.
 * External URLs pass through unchanged.
 */
class LinkRewriter {

	public function __construct(
		private readonly ShortLinkRepository $links,
		private readonly string $site_url,
		private readonly string $short_base
	) {
	}

	public function rewrite( string $body, int $post_id, string $permalink ): string {
		if ( $permalink === '' || $post_id <= 0 ) {
			return $body;
		}

		if ( ! str_contains( $body, $permalink ) ) {
			return $body;
		}

		$link  = $this->links->create_for_post( $post_id, $permalink );
		$short = rtrim( $this->short_base, '/' ) . '/' . $link->code;

		return str_replace( $permalink, $short, $body );
	}
}
