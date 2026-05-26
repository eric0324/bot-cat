<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Subscribers\SubscriberRepository;
use BotCat\Tags\AudienceResolver;

/**
 * Top-level orchestrator for `botcat_push_job_run`.
 *
 * Responsibilities (per W2 spec):
 *  - Idempotency: a second job for the same post_id collapses to
 *    skipped_duplicate without making LINE calls.
 *  - Chunk recipients into batches of ≤500 (LINE multicast cap).
 *  - Insert one pending push_logs row per recipient.
 *  - Schedule one `botcat_push_batch_run` action per batch, spaced
 *    via RateLimiter so we stay under 30 multicasts / minute.
 */
class PushJobRunner {

	public const BATCH_SIZE = 500;

	public function __construct(
		private readonly PushJobRepository $jobs,
		private readonly PushLogRepository $logs,
		private readonly SubscriberRepository $subscribers,
		private readonly ?AudienceResolver $audience = null
	) {
	}

	/**
	 * @param list<string> $custom_user_ids Non-empty list for manual test pushes.
	 */
	public function run( int $job_id, array $custom_user_ids = array() ): void {
		$job = $this->jobs->find_by_id( $job_id );
		if ( $job === null || $job->status !== PushJob::STATUS_PENDING ) {
			return;
		}

		if ( ! $job->is_test && $this->is_duplicate( $job ) ) {
			$this->jobs->mark_status( $job_id, PushJob::STATUS_SKIPPED_DUPLICATE );
			return;
		}

		$batches = $this->collect_batches( $job, $custom_user_ids );

		$recipient_count = array_sum( array_map( static fn( array $b ): int => count( $b['line_user_ids'] ), $batches ) );
		$this->jobs->mark_running( $job_id, $recipient_count );

		if ( $batches === array() ) {
			$this->jobs->mark_status( $job_id, PushJob::STATUS_SENT );
			return;
		}

		$limiter = new RateLimiter( time() );

		foreach ( $batches as $index => $batch ) {
			$ids = $this->logs->create_for_job(
				$job_id,
				$batch['line_user_ids'],
				$batch['subscriber_ids'],
				$job->is_test
			);

			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action(
					$limiter->slot_for( (int) $index ),
					PushJobScheduler::ACTION_RUN_BATCH,
					array( $job_id, $ids, 1 ),
					PushJobScheduler::GROUP
				);
			}
		}
	}

	private function is_duplicate( PushJob $job ): bool {
		$prior = $this->jobs->find_completed_for_post( $job->post_id );
		return $prior !== null && $prior->id !== $job->id;
	}

	/**
	 * @param list<string> $custom_user_ids
	 * @return list<array{line_user_ids: list<string>, subscriber_ids: list<?int>}>
	 */
	private function collect_batches( PushJob $job, array $custom_user_ids ): array {
		$batches = array();
		$current = array(
			'line_user_ids'  => array(),
			'subscriber_ids' => array(),
		);

		if ( $job->is_test && $custom_user_ids !== array() ) {
			$iterator = ( function () use ( $custom_user_ids ) {
				foreach ( $custom_user_ids as $id ) {
					yield (string) $id;
				}
			} )();
		} elseif ( $this->audience !== null ) {
			$iterator = $this->audience->iterator_for_job( $job );
		} else {
			$iterator = $this->subscribers->active_ids();
		}

		foreach ( $iterator as $line_user_id ) {
			$current['line_user_ids'][]  = $line_user_id;
			$current['subscriber_ids'][] = null;

			if ( count( $current['line_user_ids'] ) === self::BATCH_SIZE ) {
				$batches[] = $current;
				$current   = array(
					'line_user_ids'  => array(),
					'subscriber_ids' => array(),
				);
			}
		}

		if ( $current['line_user_ids'] !== array() ) {
			$batches[] = $current;
		}

		return $batches;
	}
}
