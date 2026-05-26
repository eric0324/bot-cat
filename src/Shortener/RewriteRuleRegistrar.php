<?php
/**
 * @package BotCat\Shortener
 */

declare(strict_types=1);

namespace BotCat\Shortener;

/**
 * Owns the `/l/{code}` WordPress rewrite rule and dispatches matching
 * requests to {@see ClickRedirectHandler}.
 *
 * Only wired on Pro builds — the Free edition leaves `/l/anything`
 * unrouted so WordPress returns its own 404.
 */
class RewriteRuleRegistrar {

	public const QUERY_VAR = 'botcat_short';

	public function __construct( private readonly ClickRedirectHandler $handler ) {
	}

	public function register_rewrite(): void {
		add_rewrite_rule(
			'^l/([0-9A-Za-z]{6})/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
		add_rewrite_tag( '%' . self::QUERY_VAR . '%', '([0-9A-Za-z]{6})' );
	}

	public function register_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public function dispatch(): void {
		$code = (string) get_query_var( self::QUERY_VAR );
		if ( $code === '' ) {
			return;
		}

		$token   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['s'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ip      = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		$ua      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) : null;
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? (string) wp_unslash( $_SERVER['HTTP_REFERER'] ) : null;

		$result = $this->handler->handle( $code, $token, $ip, $ua, $referer );

		if ( $result['status'] === 404 ) {
			status_header( 404 );
			nocache_headers();
			printf( '<p>%s</p>', esc_html__( 'This short link is invalid.', 'bot-cat' ) );
			exit;
		}

		if ( $result['location'] !== null ) {
			wp_redirect( $result['location'], 301 ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			exit;
		}
	}
}
