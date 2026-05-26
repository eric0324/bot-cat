<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Rules\PushThrottle;

/**
 * Creates a pending push job row and queues the Action Scheduler action
 * that will fan it out into multicast batches.
 *
 * Spec hooks (group `bot-cat`):
 *   botcat_push_job_run    — top-level orchestration
 *   botcat_push_batch_run  — single batch of ≤500 recipients
 *
 * When wired with a PushThrottle, real publishes (not test pushes)
 * schedule the run for the next available slot so consecutive
 * publishes cascade by the throttle interval.
 */
class PushJobScheduler {

	public const ACTION_RUN_JOB   = 'botcat_push_job_run';
	public const ACTION_RUN_BATCH = 'botcat_push_batch_run';
	public const GROUP            = 'bot-cat';

	public function __construct(
		private readonly PushJobRepository $jobs,
		private readonly ?PushThrottle $throttle = null
	) {
	}

	public function enqueue( int $post_id, string $post_type, bool $is_test = false ): int {
		$job_id = $this->jobs->create_pending( $post_id, $post_type, $is_test );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			$when = $is_test || $this->throttle === null ? time() : $this->throttle->next_slot();
			as_schedule_single_action( $when, self::ACTION_RUN_JOB, array( $job_id ), self::GROUP );
		}

		return $job_id;
	}

	/**
	 * Enqueue a manual test push to a specific list of LINE user ids.
	 *
	 * @param list<string> $line_user_ids
	 */
	public function enqueue_test( int $post_id, array $line_user_ids ): int {
		$job_id = $this->jobs->create_pending( $post_id, 'test', true );

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				self::ACTION_RUN_JOB,
				array( $job_id, $line_user_ids ),
				self::GROUP
			);
		}

		return $job_id;
	}
}
