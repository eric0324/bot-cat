<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Front controller for `bot-cat-logs` admin page: list view by default,
 * detail view when `?job=` is present.
 */
class PushLogsPage {

	public const PAGE_SLUG = 'bot-cat-logs';

	public function __construct(
		private readonly PushJobRepository $jobs,
		private readonly PushLogRepository $logs
	) {
	}

	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$job_id = isset( $_GET['job'] ) ? (int) $_GET['job'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $job_id > 0 ) {
			( new PushJobDetailPage( $this->jobs, $this->logs ) )->render( $job_id );
			return;
		}

		$this->ensure_list_table_loaded();

		$table = new PushJobsListTable( $this->jobs );
		$table->prepare_items();

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Push Logs', 'bot-cat' ) . '</h1>';
		echo '<hr class="wp-header-end" />';
		echo '<form method="get">';
		printf(
			'<input type="hidden" name="page" value="%s" />',
			esc_attr( self::PAGE_SLUG )
		);
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
