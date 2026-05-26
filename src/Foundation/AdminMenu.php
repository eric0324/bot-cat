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
class AdminMenu {

	public const MENU_SLUG  = 'bot-cat';
	public const CAPABILITY = 'manage_options';

	/**
	 * @param array<string, callable> $renderers Map of submenu slug => render callback. Slugs not present fall back to a placeholder.
	 */
	public function __construct(
		private readonly Edition $edition,
		private readonly array $renderers = array()
	) {
	}

	public function register(): void {
		add_menu_page(
			__( 'bot-cat', 'bot-cat' ),
			__( 'bot-cat', 'bot-cat' ),
			self::CAPABILITY,
			self::MENU_SLUG,
			$this->renderer_for( self::MENU_SLUG ),
			'dashicons-format-chat',
			30
		);

		foreach ( $this->pages() as $page ) {
			[ $slug, $title ] = $page;
			add_submenu_page(
				self::MENU_SLUG,
				$title,
				$title,
				self::CAPABILITY,
				$slug,
				$this->renderer_for( $slug )
			);
		}
	}

	/**
	 * @return list<array{0:string,1:string}>
	 */
	private function pages(): array {
		$pages = array(
			array( 'bot-cat', __( 'Dashboard', 'bot-cat' ) ),
			array( 'bot-cat-subscribers', __( 'Subscribers', 'bot-cat' ) ),
			array( 'bot-cat-templates', __( 'Templates', 'bot-cat' ) ),
			array( 'bot-cat-logs', __( 'Push Logs', 'bot-cat' ) ),
			array( 'bot-cat-settings', __( 'Settings', 'bot-cat' ) ),
		);

		if ( $this->edition->is_pro() ) {
			$pages[] = array( 'bot-cat-license', __( 'License', 'bot-cat' ) );
		}

		return $pages;
	}

	private function renderer_for( string $slug ): callable {
		if ( isset( $this->renderers[ $slug ] ) ) {
			return $this->renderers[ $slug ];
		}

		return function () use ( $slug ): void {
			printf(
				'<div class="wrap"><h1>%s</h1><p>%s</p></div>',
				esc_html( $slug ),
				esc_html__( 'This page is not implemented yet.', 'bot-cat' )
			);
		};
	}
}
