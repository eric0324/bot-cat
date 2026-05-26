<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Front controller for the bot-cat-subscribers admin page.
 *
 * Dispatches to {@see SubscriberDetailPage} when `?subscriber=` is
 * present in the URL, otherwise renders the full list-table view.
 */
class SubscribersPage {

	public const PAGE_SLUG = 'bot-cat-subscribers';

	public function __construct( private readonly SubscriberRepository $repository ) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$subscriber_id = isset( $_GET['subscriber'] ) ? (int) $_GET['subscriber'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $subscriber_id > 0 ) {
			( new SubscriberDetailPage( $this->repository ) )->render( $subscriber_id );
			return;
		}

		$this->ensure_list_table_loaded();

		$table = new SubscriberListTable( $this->repository );
		$table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Subscribers', 'bot-cat' ) . '</h1>';
		echo '<hr class="wp-header-end" />';
		echo '<form method="post">';
		printf(
			'<input type="hidden" name="page" value="%s" />',
			esc_attr( self::PAGE_SLUG )
		);
		wp_nonce_field( SubscriberListTable::BULK_NONCE_ACTION );
		$table->search_box( __( 'Search subscribers', 'bot-cat' ), 'subscriber-search' );
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	private function ensure_list_table_loaded(): void {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
	}
}
