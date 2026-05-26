<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Rules\ExcludedCategories;
use BotCat\Rules\PerPostOptOut;

/**
 * Listens to `transition_post_status` and forwards eligible
 * non-publish → publish transitions to {@see PushJobScheduler}.
 *
 * Consults push-rules capability filters when wired:
 *   - EligiblePostTypes::is_eligible
 *   - ExcludedCategories::is_excluded
 *   - PerPostOptOut::is_opted_in
 */
class PostPublishObserver {

	public function __construct(
		private readonly EligiblePostTypes $eligible,
		private readonly PushJobScheduler $scheduler,
		private readonly ?ExcludedCategories $excluded = null,
		private readonly ?PerPostOptOut $opt_out = null
	) {
	}

	public function on_transition( string $new_status, string $old_status, mixed $post ): void {
		if ( $new_status !== 'publish' || $old_status === 'publish' ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->ID, $post->post_type ) ) {
			return;
		}

		$post_id   = (int) $post->ID;
		$post_type = (string) $post->post_type;

		if ( ! $this->eligible->is_eligible( $post_type ) ) {
			return;
		}

		if ( $this->excluded !== null && $this->excluded->is_excluded( $post_id ) ) {
			return;
		}

		if ( $this->opt_out !== null && ! $this->opt_out->is_opted_in( $post_id ) ) {
			return;
		}

		$this->scheduler->enqueue( $post_id, $post_type );
	}
}
