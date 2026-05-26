<?php
/**
 * @package BotCat\License
 */

declare(strict_types=1);

namespace BotCat\License;

/**
 * Activates / deactivates the local license against Polar.
 *
 * Wired as `admin-post` handlers; on completion redirects back to the
 * License page with a status banner so the screen reflects the new
 * state without further user action.
 */
class LicenseActivationHandler {

	public function __construct(
		private readonly LicenseRepository $repository,
		private readonly PolarClient $polar
	) {
	}

	public function handle_activate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( LicensePage::NONCE_ACTIVATE );

		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['license_key'] ) ) : '';

		$result = $this->polar->validate( $key );

		if ( ! $result->ok ) {
			$this->repository->save_status(
				$result->error_code === PolarValidationResult::ERROR_INVALID ? LicenseStatus::INVALID : LicenseStatus::INACTIVE
			);
			$this->redirect( 'failed', (string) $result->error_message );
		}

		if ( ! $result->entitled ) {
			$this->repository->save_status( LicenseStatus::INVALID );
			$this->redirect( 'failed', __( 'Polar reports this key is not entitled.', 'bot-cat' ) );
		}

		$this->repository->save_key( $key );
		$this->repository->save_cache(
			new LicenseCache(
				entitled: true,
				verified_at: time(),
				last_failure_at: null,
				consecutive_failures: 0,
				plan_name: $result->plan_name,
				renews_at: $result->renews_at,
			)
		);
		$this->repository->save_status( LicenseStatus::ACTIVE );

		$this->redirect(
			'activated',
			$result->plan_name !== null
				? sprintf(
					/* translators: 1: plan name, 2: renewal date. */
					__( 'Active — %1$s, renews %2$s', 'bot-cat' ),
					$result->plan_name,
					$result->renews_at ?? '—'
				)
				: __( 'License activated.', 'bot-cat' )
		);
	}

	public function handle_deactivate(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( LicensePage::NONCE_DEACTIVATE );

		$key = $this->repository->get_key();
		$this->polar->deactivate( $key );

		$this->repository->clear();

		$this->redirect( 'deactivated', __( 'License deactivated.', 'bot-cat' ) );
	}

	private function redirect( string $status, string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => LicensePage::PAGE_SLUG,
					'botcat_license' => $status,
					'botcat_message' => rawurlencode( $message ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
