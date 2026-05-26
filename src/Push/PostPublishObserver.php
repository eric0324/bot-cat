<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

/**
 * Listens to `transition_post_status` and forwards eligible
 * non-publish → publish transitions to {@see PushJobScheduler}.
 *
 * Re-publishing (publish → publish) does not trigger — only the first
 * time a post becomes publicly visible.
 */
class PostPublishObserver {

	public function __construct(
		private readonly EligiblePostTypes $eligible,
		private readonly PushJobScheduler $scheduler
	) {
	}

	public function on_transition( string $new_status, string $old_status, mixed $post ): void {
		if ( $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->ID, $post->post_type ) ) {
			return;
		}

		$post_type = (string) $post->post_type;
		if ( ! $this->eligible->is_eligible( $post_type ) ) {
			return;
		}

		$this->scheduler->enqueue( (int) $post->ID, $post_type );
	}
}
