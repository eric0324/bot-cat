<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * License admin page (Pro only).
 *
 * Shows the current status + activation/deactivation form. The actual
 * Polar handshake lives in {@see LicenseActivationHandler}, invoked
 * through admin-post.
 */
class LicensePage {

	public const PAGE_SLUG         = 'bot-cat-license';
	public const ACTION_ACTIVATE   = 'botcat_license_activate';
	public const ACTION_DEACTIVATE = 'botcat_license_deactivate';
	public const NONCE_ACTIVATE    = 'botcat_license_activate';
	public const NONCE_DEACTIVATE  = 'botcat_license_deactivate';

	public function __construct(
		private readonly LicenseRepository $repository,
		private readonly LicenseStatusEvaluator $evaluator
	) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$status = $this->repository->get_status();
		$cache  = $this->repository->get_cache();
		$now    = time();
		$days   = $this->evaluator->days_since_verified( $cache, $now );

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'License', 'bot-cat' ) . '</h1>';

		$this->render_banner();
		$this->render_status_card( $status, $cache, $days );

		if ( $status === LicenseStatus::ACTIVE ) {
			$this->render_deactivate_form();
		} else {
			$this->render_activate_form( $this->repository->get_key() );
		}

		echo '</div>';
	}

	private function render_status_card( string $status, LicenseCache $cache, int $days ): void {
		echo '<table class="form-table" role="presentation"><tbody>';
		$this->row( __( 'Status', 'bot-cat' ), esc_html( ucfirst( $status ) ) );

		if ( $cache->plan_name !== null ) {
			$this->row( __( 'Plan', 'bot-cat' ), esc_html( $cache->plan_name ) );
		}
		if ( $cache->renews_at !== null ) {
			$this->row( __( 'Renews at', 'bot-cat' ), esc_html( $cache->renews_at ) );
		}
		if ( $cache->verified_at > 0 ) {
			$this->row(
				__( 'Last verified', 'bot-cat' ),
				esc_html(
					sprintf(
						/* translators: %d: number of days. */
						_n( '%d day ago', '%d days ago', max( 1, $days ), 'bot-cat' ),
						$days
					)
				)
			);
		}
		echo '</tbody></table>';
	}

	private function render_activate_form( string $current_key ): void {
		$url = admin_url( 'admin-post.php' );

		echo '<h2>' . esc_html__( 'Activate license', 'bot-cat' ) . '</h2>';
		echo '<form method="post" action="' . esc_url( $url ) . '">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::ACTION_ACTIVATE ) );
		wp_nonce_field( self::NONCE_ACTIVATE );
		printf(
			'<p><input type="text" name="license_key" value="%s" placeholder="%s" class="regular-text code" /></p>',
			esc_attr( $current_key ),
			esc_attr__( 'Paste your Polar license key', 'bot-cat' )
		);
		submit_button( __( 'Activate', 'bot-cat' ) );
		echo '</form>';
	}

	private function render_deactivate_form(): void {
		$url = admin_url( 'admin-post.php' );

		echo '<form method="post" action="' . esc_url( $url ) . '">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::ACTION_DEACTIVATE ) );
		wp_nonce_field( self::NONCE_DEACTIVATE );
		submit_button( __( 'Deactivate', 'bot-cat' ), 'secondary', 'submit', false );
		echo '</form>';
	}

	private function render_banner(): void {
		if ( ! isset( $_GET['botcat_license'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$status  = sanitize_key( wp_unslash( (string) $_GET['botcat_license'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = isset( $_GET['botcat_message'] ) ? sanitize_text_field( rawurldecode( wp_unslash( (string) $_GET['botcat_message'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$class = $status === 'activated' ? 'notice-success' : 'notice-error';
		printf(
			'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $class ),
			esc_html( $message !== '' ? $message : __( 'License update completed.', 'bot-cat' ) )
		);
	}

	private function row( string $label, string $value_html ): void {
		printf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $label ),
			$value_html
		);
	}
}
