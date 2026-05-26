<?php
/**
 * @package BotCat\Tags
 */

declare(strict_types=1);

namespace BotCat\Tags;

use BotCat\Push\PushJob;
use BotCat\Subscribers\SubscriberRepository;
use Generator;

/**
 * Decides which subscribers receive a given push.
 *
 * Algorithm (per tag-segmentation spec):
 *   1. Test jobs yield nothing — caller supplies its own list.
 *   2. Look up the post's categories.
 *   3. Find tags whose mapped_categories overlap.
 *   4. If any tags matched, restrict to subscribers subscribed to
 *      at least one of those tags.
 *   5. Otherwise (no categories, or no tag mapping), fall back to
 *      the full active audience.
 */
class AudienceResolver {

	public function __construct(
		private readonly SubscriberRepository $subscribers,
		private readonly TagRepository $tags,
		private readonly SubscriberTagRepository $joins
	) {
	}

	/**
	 * @return Generator<string>
	 */
	public function iterator_for_job( PushJob $job ): Generator {
		if ( $job->is_test ) {
			return;
		}

		if ( $job->post_id <= 0 ) {
			yield from $this->subscribers->active_ids();
			return;
		}

		$category_ids = wp_get_post_categories( $job->post_id, array( 'fields' => 'ids' ) );
		if ( ! is_array( $category_ids ) || $category_ids === array() ) {
			yield from $this->subscribers->active_ids();
			return;
		}

		$matching = $this->tags->find_for_categories( array_map( 'intval', $category_ids ) );
		if ( $matching === array() ) {
			yield from $this->subscribers->active_ids();
			return;
		}

		$tag_ids = array_map( static fn( Tag $tag ): int => $tag->id, $matching );

		yield from $this->joins->active_line_user_ids_for_tags( $tag_ids );
	}
}
