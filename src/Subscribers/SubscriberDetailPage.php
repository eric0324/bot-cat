<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Renders the per-subscriber detail view: identity, status history, and
 * the last 20 push deliveries (per spec).
 */
class SubscriberDetailPage {

	public function __construct( private readonly SubscriberRepository $repository ) {
	}

	public function render( int $subscriber_id ): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$subscriber = $this->find_by_id( $subscriber_id );

		if ( $subscriber === null ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Subscriber not found', 'bot-cat' ) . '</h1></div>';
			return;
		}

		$logs = $this->repository->recent_logs_for_subscriber( $subscriber->id, 20 );

		echo '<div class="wrap">';
		printf( '<h1>%s</h1>', esc_html( $subscriber->display_name ?? __( '(unknown)', 'bot-cat' ) ) );
		$this->render_identity( $subscriber );
		$this->render_logs( $logs );
		echo '</div>';
	}

	private function find_by_id( int $id ): ?Subscriber {
		// The repository keys on line_user_id; for the detail page the
		// list table already passes the numeric id, so walk the active
		// stream once to locate it. Detail views are infrequent so this
		// is acceptable until a find_by_id() helper is added.
		foreach ( $this->repository->active_ids() as $line_user_id ) {
			$candidate = $this->repository->find_by_line_id( $line_user_id );
			if ( $candidate !== null && $candidate->id === $id ) {
				return $candidate;
			}
		}
		return null;
	}

	private function render_identity( Subscriber $subscriber ): void {
		echo '<table class="form-table" role="presentation"><tbody>';
		$this->row( __( 'LINE User ID', 'bot-cat' ), '<code>' . esc_html( $subscriber->line_user_id ) . '</code>' );
		$this->row( __( 'Display Name', 'bot-cat' ), esc_html( $subscriber->display_name ?? '—' ) );
		if ( $subscriber->picture_url !== null && $subscriber->picture_url !== '' ) {
			$this->row(
				__( 'Picture', 'bot-cat' ),
				'<img src="' . esc_url( $subscriber->picture_url ) . '" alt="" width="64" height="64" />'
			);
		}
		$this->row( __( 'Status', 'bot-cat' ), esc_html( ucfirst( $subscriber->status ) ) );
		$this->row( __( 'Followed at', 'bot-cat' ), esc_html( (string) $subscriber->followed_at ) );
		if ( $subscriber->unfollowed_at !== null ) {
			$this->row( __( 'Unfollowed at', 'bot-cat' ), esc_html( $subscriber->unfollowed_at ) );
		}
		echo '</tbody></table>';
	}

	/**
	 * @param list<PushLogEntry> $logs
	 */
	private function render_logs( array $logs ): void {
		echo '<h2>' . esc_html__( 'Recent push deliveries', 'bot-cat' ) . '</h2>';

		if ( $logs === array() ) {
			echo '<p>' . esc_html__( 'No pushes sent to this subscriber yet.', 'bot-cat' ) . '</p>';
			return;
		}

		echo '<table class="widefat striped"><thead><tr>';
		printf( '<th>%s</th>', esc_html__( 'Sent at', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Post', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Status', 'bot-cat' ) );
		printf( '<th>%s</th>', esc_html__( 'Error', 'bot-cat' ) );
		echo '</tr></thead><tbody>';

		foreach ( $logs as $log ) {
			echo '<tr>';
			printf( '<td>%s</td>', esc_html( $log->created_at ) );
			printf(
				'<td>%s</td>',
				$log->post_id !== null
					? sprintf(
						'<a href="%1$s">#%2$d</a>',
						esc_url( (string) get_edit_post_link( (int) $log->post_id ) ),
						(int) $log->post_id
					)
					: '—'
			);
			printf( '<td>%s</td>', esc_html( $log->status ) );
			printf( '<td>%s</td>', esc_html( $log->error ?? '' ) );
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	private function row( string $label, string $value_html ): void {
		printf(
			'<tr><th scope="row">%1$s</th><td>%2$s</td></tr>',
			esc_html( $label ),
			$value_html // Already escaped at the call site.
		);
	}
}
