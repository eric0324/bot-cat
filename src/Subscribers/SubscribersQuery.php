<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Immutable query parameters for the Subscribers list table.
 *
 * All values are clamped to safe ranges so the caller never has to
 * sanitize before passing this object into SubscriberRepository::search().
 */
final class SubscribersQuery {

	public const ALLOWED_ORDERBY = array( 'followed_at', 'status', 'display_name', 'id' );
	public const MAX_PER_PAGE    = 500;

	public readonly string $search;
	public readonly ?string $status;
	public readonly string $orderby;
	public readonly string $order;
	public readonly int $page;
	public readonly int $per_page;

	public function __construct(
		string $search = '',
		?string $status = null,
		string $orderby = 'followed_at',
		string $order = 'DESC',
		int $page = 1,
		int $per_page = 50
	) {
		$this->search   = trim( $search );
		$this->status   = $status === null || $status === '' ? null : $status;
		$this->orderby  = in_array( $orderby, self::ALLOWED_ORDERBY, true ) ? $orderby : 'followed_at';
		$this->order    = strtoupper( $order ) === 'ASC' ? 'ASC' : 'DESC';
		$this->page     = max( 1, $page );
		$this->per_page = min( self::MAX_PER_PAGE, max( 1, $per_page ) );
	}

	public function offset(): int {
		return ( $this->page - 1 ) * $this->per_page;
	}

	/**
	 * @param array<string, mixed> $request Usually $_GET.
	 */
	public static function from_request( array $request ): self {
		return new self(
			search: isset( $request['s'] ) ? (string) $request['s'] : '',
			status: isset( $request['status'] ) && (string) $request['status'] !== '' ? (string) $request['status'] : null,
			orderby: isset( $request['orderby'] ) ? (string) $request['orderby'] : 'followed_at',
			order: isset( $request['order'] ) ? (string) $request['order'] : 'DESC',
			page: isset( $request['paged'] ) ? (int) $request['paged'] : 1,
			per_page: isset( $request['per_page'] ) ? (int) $request['per_page'] : 50,
		);
	}
}
