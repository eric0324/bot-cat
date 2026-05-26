<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Subscribers admin list table.
 *
 * The data path (search / status filter / pagination / bulk delete)
 * is exercised by {@see SubscriberSearchTest} and
 * {@see SubscriberRepositoryTest}; this class is a thin adapter onto
 * WordPress's WP_List_Table.
 *
 * Callers MUST load `wp-admin/includes/class-wp-list-table.php` before
 * referencing this class so the autoloader can satisfy the `extends`
 * parent at parse time.
 */
class SubscriberListTable extends \WP_List_Table {

	public const BULK_NONCE_ACTION = 'bulk-subscribers';
	public const PAGE_SLUG         = 'bot-cat-subscribers';

	public function __construct( private readonly SubscriberRepository $repository ) {
		parent::__construct(
			array(
				'singular' => 'subscriber',
				'plural'   => 'subscribers',
				'ajax'     => false,
			)
		);
	}

	public function get_columns(): array {
		return array(
			'cb'           => '<input type="checkbox" />',
			'display_name' => __( 'Display Name', 'bot-cat' ),
			'line_user_id' => __( 'LINE User ID', 'bot-cat' ),
			'status'       => __( 'Status', 'bot-cat' ),
			'followed_at'  => __( 'Followed', 'bot-cat' ),
		);
	}

	protected function get_sortable_columns(): array {
		return array(
			'status'      => array( 'status', false ),
			'followed_at' => array( 'followed_at', true ),
		);
	}

	public function get_bulk_actions(): array {
		return array(
			'delete' => __( 'Delete', 'bot-cat' ),
		);
	}

	public function prepare_items(): void {
		$this->process_bulk_action();

		$query = SubscribersQuery::from_request( $this->sanitized_request_args() );
		$page  = $this->repository->search( $query );

		$this->items           = $page->rows;
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $page->total,
				'per_page'    => $query->per_page,
				'total_pages' => max( 1, (int) ceil( $page->total / max( 1, $query->per_page ) ) ),
			)
		);
	}

	public function process_bulk_action(): void {
		if ( 'delete' !== $this->current_action() ) {
			return;
		}

		check_admin_referer( self::BULK_NONCE_ACTION );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'bot-cat' ), '', array( 'response' => 403 ) );
		}

		$ids = isset( $_REQUEST['subscriber'] )
			? array_map( 'intval', (array) wp_unslash( $_REQUEST['subscriber'] ) )
			: array();

		if ( $ids === array() ) {
			return;
		}

		$this->repository->delete_many( $ids );
	}

	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="subscriber[]" value="%d" />',
			(int) $item->id
		);
	}

	public function column_display_name( Subscriber $item ): string {
		$name = $item->display_name ?? __( '(unknown)', 'bot-cat' );

		$detail_url = add_query_arg(
			array(
				'page'       => self::PAGE_SLUG,
				'subscriber' => (int) $item->id,
			),
			admin_url( 'admin.php' )
		);

		return sprintf(
			'<strong><a href="%1$s">%2$s</a></strong>',
			esc_url( $detail_url ),
			esc_html( $name )
		);
	}

	public function column_line_user_id( Subscriber $item ): string {
		$short = substr( $item->line_user_id, 0, 8 );
		return '<code title="' . esc_attr( $item->line_user_id ) . '">' . esc_html( $short ) . '…</code>';
	}

	public function column_status( Subscriber $item ): string {
		$label = $item->status === Subscriber::STATUS_ACTIVE
			? __( 'Active', 'bot-cat' )
			: __( 'Unfollowed', 'bot-cat' );

		return sprintf(
			'<span class="botcat-status botcat-status-%1$s">%2$s</span>',
			esc_attr( $item->status ),
			esc_html( $label )
		);
	}

	public function column_followed_at( Subscriber $item ): string {
		return esc_html( (string) $item->followed_at );
	}

	public function column_default( $item, $column_name ) {
		return '';
	}

	public function no_items(): void {
		esc_html_e( 'No subscribers yet — share your LINE OA link so people can add it as a friend.', 'bot-cat' );
	}

	protected function extra_tablenav( $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$current = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( (string) $_REQUEST['status'] ) ) : '';
		?>
		<div class="alignleft actions">
			<label class="screen-reader-text" for="filter-by-status"><?php esc_html_e( 'Filter by status', 'bot-cat' ); ?></label>
			<select name="status" id="filter-by-status">
				<option value=""<?php selected( $current, '' ); ?>><?php esc_html_e( 'All statuses', 'bot-cat' ); ?></option>
				<option value="active"<?php selected( $current, 'active' ); ?>><?php esc_html_e( 'Active', 'bot-cat' ); ?></option>
				<option value="unfollowed"<?php selected( $current, 'unfollowed' ); ?>><?php esc_html_e( 'Unfollowed', 'bot-cat' ); ?></option>
			</select>
			<?php submit_button( __( 'Filter', 'bot-cat' ), '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * @return array<string, mixed>
	 */
	private function sanitized_request_args(): array {
		$args = array();

		foreach ( array( 's', 'status', 'orderby', 'order', 'paged' ) as $key ) {
			if ( isset( $_REQUEST[ $key ] ) ) {
				$args[ $key ] = sanitize_text_field( wp_unslash( (string) $_REQUEST[ $key ] ) );
			}
		}

		return $args;
	}
}
