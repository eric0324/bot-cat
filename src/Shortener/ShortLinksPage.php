<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Admin list view of `botcat_short_links` (Pro only).
 *
 * Each row shows the code, target post, created date, total clicks,
 * and unique clicks (by ip_hash).
 */
class ShortLinksPage {

	public const PAGE_SLUG = 'bot-cat-short-links';

	public function __construct(
		private readonly ShortLinkRepository $links,
		private readonly ClickLogRepository $clicks,
		private readonly string $short_base
	) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$rows = $this->links->list_paged( 1, 100 );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Short Links', 'bot-cat' ) . '</h1>';

		if ( $rows === array() ) {
			echo '<p>' . esc_html__( 'No short links yet — publish a post to mint one.', 'bot-cat' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Code', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Post', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Short URL', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Created', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Clicks', 'bot-cat' ) );
		echo '</tr></thead><tbody>';

		foreach ( $rows as $link ) {
			$total  = $this->clicks->count_total( $link->id );
			$unique = $this->clicks->count_unique( $link->id );
			$short  = rtrim( $this->short_base, '/' ) . '/' . $link->code;
			$title  = $link->post_id > 0 ? (string) get_the_title( $link->post_id ) : __( '(test push)', 'bot-cat' );

			echo '<tr>';
			printf( '<td><code>%s</code></td>', esc_html( $link->code ) );
			printf( '<td>%s</td>', esc_html( $title ) );
			printf( '<td><a href="%1$s">%1$s</a></td>', esc_url( $short ) );
			printf( '<td>%s</td>', esc_html( $link->created_at ) );
			printf(
				'<td>%s</td>',
				esc_html(
					sprintf(
						/* translators: 1: total clicks, 2: unique clicks. */
						__( '%1$d clicks · %2$d unique', 'bot-cat' ),
						$total,
						$unique
					)
				)
			);
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}
}
