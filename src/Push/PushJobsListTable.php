<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Admin list table for Push Logs index.
 *
 * Callers MUST load `wp-admin/includes/class-wp-list-table.php` before
 * referencing this class so the autoloader can satisfy the `extends`
 * parent at parse time.
 */
class PushJobsListTable extends \WP_List_Table {

	public const PAGE_SLUG = 'bot-cat-logs';

	public function __construct( private readonly PushJobRepository $jobs ) {
		parent::__construct(
			array(
				'singular' => 'push_job',
				'plural'   => 'push_jobs',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'post'         => __( 'Post', 'bot-cat' ),
			'triggered_at' => __( 'Triggered', 'bot-cat' ),
			'progress'     => __( 'Sent / Total', 'bot-cat' ),
			'failed'       => __( 'Failed', 'bot-cat' ),
			'status'       => __( 'Status', 'bot-cat' ),
		);
	}

	public function prepare_items(): void {
		$per_page = 50;
		$page     = max( 1, (int) $this->get_pagenum() );

		$this->items = $this->jobs->list_paged( $page, $per_page );
		$total       = $this->jobs->count_non_test();

		$this->_column_headers = array( $this->get_columns(), array(), array() );

		$this->set_pagination_args(
			array(
				'total_items' => $total,
				'per_page'    => $per_page,
				'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
			)
		);
	}

	public function column_post( PushJob $item ): string {
		$title = $item->post_id > 0 ? (string) get_the_title( $item->post_id ) : __( '(test push)', 'bot-cat' );
		if ( $title === '' ) {
			$title = sprintf( '#%d', $item->post_id );
		}

		$detail_url = add_query_arg(
			array(
				'page' => self::PAGE_SLUG,
				'job'  => $item->id,
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong>',
			esc_url( $detail_url ),
			esc_html( $title )
		);
	}

	public function column_triggered_at( PushJob $item ): string {
		return esc_html( (string) $item->triggered_at );
	}

	public function column_progress( PushJob $item ): string {
		return esc_html( sprintf( '%d / %d', $item->sent_count, $item->recipient_count ) );
	}

	public function column_failed( PushJob $item ): string {
		return esc_html( (string) $item->failed_count );
	}

	public function column_status( PushJob $item ): string {
		return sprintf(
			'<span class="botcat-status botcat-status-%1$s">%2$s</span>',
			esc_attr( $item->status ),
			esc_html( str_replace( '_', ' ', $item->status ) )
		);
	}

	public function column_default( $item, $column_name ) {
		return '';
	}

	public function no_items(): void {
		esc_html_e( 'No push jobs yet — publish a post to send your first.', 'bot-cat' );
	}
}
