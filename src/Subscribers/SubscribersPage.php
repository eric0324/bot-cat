<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Hooks the Subscribers list-table render onto the bot-cat-subscribers
 * submenu page registered by AdminMenu.
 *
 * The list table itself is covered by manual acceptance — the data
 * access pipeline it builds on is unit-tested in
 * {@see SubscriberRepositoryTest}.
 */
class SubscribersPage {

	public const PAGE_SLUG = 'bot-cat-subscribers';

	public function __construct( private readonly SubscriberRepository $subscribers ) {
	}

	public function register(): void {
		// AdminMenu owns add_submenu_page; we just attach the renderer.
		add_action( 'load-bot-cat_page_' . self::PAGE_SLUG, array( $this, 'prepare' ) );
	}

	public function prepare(): void {
		// List-table preparation hook (screen options, bulk action handling)
		// will land here when the WP_List_Table subclass is fleshed out.
	}

	public function render(): void {
		printf(
			'<div class="wrap"><h1>%s</h1><p>%s</p></div>',
			esc_html__( 'Subscribers', 'bot-cat' ),
			esc_html(
				sprintf(
					/* translators: %d: number of active subscribers. */
					__( 'Active subscribers: %d', 'bot-cat' ),
					$this->subscribers->count_active()
				)
			)
		);
	}
}
