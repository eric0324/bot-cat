<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Per-job detail view: aggregate counts + per-recipient log rows +
 * Resend to failed action.
 */
class PushJobDetailPage {

	public const RESEND_NONCE  = 'botcat_resend_failed';
	public const RESEND_ACTION = 'botcat_resend_failed';

	public function __construct(
		private readonly PushJobRepository $jobs,
		private readonly PushLogRepository $logs
	) {
	}

	public function render( int $job_id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$job = $this->jobs->find_by_id( $job_id );
		if ( $job === null ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Push job not found', 'bot-cat' ) . '</h1></div>';
			return;
		}

		$rows = $this->logs->list_for_job( $job_id, 1, 500 );

		echo '<div class="wrap">';
		printf(
			'<h1>%s</h1>',
			esc_html(
				sprintf(
				/* translators: %d: push job id. */
					__( 'Push job #%d', 'bot-cat' ),
					$job->id
				)
			)
		);

		$this->render_summary( $job );

		if ( $job->failed_count > 0 ) {
			$this->render_resend_form( $job );
		}

		$this->render_rows( $rows );

		echo '</div>';
	}

	public function handle_resend(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::RESEND_NONCE );

		$job_id = isset( $_REQUEST['job'] ) ? (int) $_REQUEST['job'] : 0;
		if ( $job_id <= 0 ) {
			wp_safe_redirect( admin_url( 'admin.php?page=' . PushJobsListTable::PAGE_SLUG ) );
			exit;
		}

		$failed = $this->logs->failed_for_job( $job_id );
		$ids    = array_map( static fn( PushLog $log ): int => $log->id, $failed );

		if ( $ids !== array() ) {
			$this->logs->reset_for_resend( $ids );

			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action(
					time(),
					PushJobScheduler::ACTION_RUN_BATCH,
					array( $job_id, $ids, 1 ),
					PushJobScheduler::GROUP
				);
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'          => PushJobsListTable::PAGE_SLUG,
					'job'           => $job_id,
					'botcat_resend' => 'queued',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	private function render_summary( PushJob $job ): void {
		echo '<table class="form-table" role="presentation"><tbody>';
		$this->row( __( 'Post ID', 'bot-cat' ), esc_html( (string) $job->post_id ) );
		$this->row( __( 'Status', 'bot-cat' ), esc_html( $job->status ) );
		$this->row( __( 'Triggered at', 'bot-cat' ), esc_html( (string) $job->triggered_at ) );
		$this->row( __( 'Recipients', 'bot-cat' ), esc_html( (string) $job->recipient_count ) );
		$this->row( __( 'Sent', 'bot-cat' ), esc_html( (string) $job->sent_count ) );
		$this->row( __( 'Failed', 'bot-cat' ), esc_html( (string) $job->failed_count ) );
		if ( $job->last_error !== null && $job->last_error !== '' ) {
			$this->row( __( 'Last error', 'bot-cat' ), esc_html( $job->last_error ) );
		}
		echo '</tbody></table>';
	}

	private function render_resend_form( PushJob $job ): void {
		$action_url = admin_url( 'admin-post.php' );

		echo '<form method="post" action="' . esc_url( $action_url ) . '" style="margin: 1em 0;">';
		printf( '<input type="hidden" name="action" value="%s" />', esc_attr( self::RESEND_ACTION ) );
		printf( '<input type="hidden" name="job" value="%d" />', (int) $job->id );
		wp_nonce_field( self::RESEND_NONCE );
		submit_button(
			sprintf(
				/* translators: %d: number of failed recipients. */
				__( 'Resend to %d failed recipients', 'bot-cat' ),
				$job->failed_count
			),
			'primary',
			'submit',
			false
		);
		echo '</form>';
	}

	/**
	 * @param list<PushLog> $rows
	 */
	private function render_rows( array $rows ): void {
		echo '<h2>' . esc_html__( 'Per-recipient log', 'bot-cat' ) . '</h2>';

		if ( $rows === array() ) {
			echo '<p>' . esc_html__( 'No log rows yet.', 'bot-cat' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'LINE User ID', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Status', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Attempts', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Error', 'bot-cat' ) );
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			echo '<tr>';
			printf( '<td><code>%s</code></td>', esc_html( $row->line_user_id ) );
			printf( '<td>%s</td>', esc_html( $row->status ) );
			printf( '<td>%d</td>', (int) $row->attempts );
			printf( '<td>%s</td>', esc_html( $row->error ?? '' ) );
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function row( string $label, string $value ): void {
		printf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $label ),
			$value
		);
	}
}
