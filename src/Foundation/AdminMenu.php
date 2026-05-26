<?php
/**
 * @package BotCat\Foundation
 */

declare(strict_types=1);

namespace BotCat\Foundation;

/**
 * Registers the bot-cat top-level admin menu and its submenu pages.
 *
 * The order is fixed: Dashboard, Subscribers, Templates, Push Logs,
 * Settings, License. The License page only registers when the Pro
 * edition is active.
 */
final class AdminMenu {

	public const MENU_SLUG  = 'bot-cat';
	public const CAPABILITY = 'manage_options';

	public function __construct( private readonly Edition $edition ) {
	}

	public function register(): void {
		add_menu_page(
			__( 'bot-cat', 'bot-cat' ),
			__( 'bot-cat', 'bot-cat' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			array( $this, 'render_dashboard' ),
			'dashicons-format-chat',
			30
		);

		$pages = array(
			array( 'bot-cat', __( 'Dashboard', 'bot-cat' ), 'render_dashboard' ),
			array( 'bot-cat-subscribers', __( 'Subscribers', 'bot-cat' ), 'render_subscribers' ),
			array( 'bot-cat-templates', __( 'Templates', 'bot-cat' ), 'render_templates' ),
			array( 'bot-cat-logs', __( 'Push Logs', 'bot-cat' ), 'render_logs' ),
			array( 'bot-cat-settings', __( 'Settings', 'bot-cat' ), 'render_settings' ),
		);

		if ( $this->edition->is_pro() ) {
			$pages[] = array( 'bot-cat-license', __( 'License', 'bot-cat' ), 'render_license' );
		}

		foreach ( $pages as [$slug, $title, $callback] ) {
			add_submenu_page(
				self::MENU_SLUG,
				$title,
				$title,
				self::CAPABILITY,
				$slug,
				array( $this, $callback )
			);
		}
	}

	public function render_dashboard(): void {
		$this->render_placeholder( __( 'Dashboard', 'bot-cat' ) );
	}

	public function render_subscribers(): void {
		$this->render_placeholder( __( 'Subscribers', 'bot-cat' ) );
	}

	public function render_templates(): void {
		$this->render_placeholder( __( 'Templates', 'bot-cat' ) );
	}

	public function render_logs(): void {
		$this->render_placeholder( __( 'Push Logs', 'bot-cat' ) );
	}

	public function render_settings(): void {
		$this->render_placeholder( __( 'Settings', 'bot-cat' ) );
	}

	public function render_license(): void {
		$this->render_placeholder( __( 'License', 'bot-cat' ) );
	}

	private function render_placeholder( string $title ): void {
		printf(
			'<div class="wrap"><h1>%s</h1></div>',
			esc_html( $title )
		);
	}
}
