<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

/**
 * Handles the LINE `unfollow` webhook event.
 *
 * Sets the subscriber's status to `unfollowed`; the row remains so
 * `push_logs` historical joins stay valid.
 */
class UnfollowHandler {

	public function __construct( private readonly SubscriberRepository $subscribers ) {
	}

	public function handle( string $line_user_id, int $event_timestamp ): void {
		$this->subscribers->mark_unfollowed(
			$line_user_id,
			gmdate( 'Y-m-d H:i:s', $event_timestamp )
		);
	}
}
