<?php
/**
 * @package BotCat\Push
 */

declare(strict_types=1);

namespace BotCat\Push;

use BotCat\Channel\ChannelSettingsRepository;

/**
 * Executes one multicast batch: load the assigned log rows, call LINE,
 * update statuses, and consult RetryPolicy on failure.
 *
 * Listens for `botcat_push_batch_run` action args `[job_id, log_ids, attempt=1]`.
 */
class BatchRunner {

	public function __construct(
		private readonly PushJobRepository $jobs,
		private readonly PushLogRepository $logs,
		private readonly ChannelSettingsRepository $channel,
		private readonly MulticastClient $client,
		private readonly RetryPolicy $policy,
		private readonly MessageBuilder $messages
	) {
	}

	/**
	 * @param list<int> $log_ids
	 */
	public function run( int $job_id, array $log_ids, int $attempt = 1 ): void {
		$job = $this->jobs->find_by_id( $job_id );
		if ( $job === null || $job->is_terminal() ) {
			return;
		}

		if ( $log_ids === array() ) {
			return;
		}

		$settings = $this->channel->get();
		if ( ! $settings->is_configured() ) {
			$this->jobs->mark_status(
				$job_id,
				PushJob::STATUS_FAILED,
				__( 'channel is not configured', 'bot-cat' )
			);
			return;
		}

		$logs = $this->logs->find_by_ids_for_job( $job_id, $log_ids );
		if ( $logs === array() ) {
			return;
		}

		$line_user_ids = array_values( array_map( static fn( PushLog $log ): string => $log->line_user_id, $logs ) );
		$messages      = $this->messages->build_for_job( $job );

		$result   = $this->client->send( $settings->access_token(), $line_user_ids, $messages );
		$decision = $this->policy->decide( $attempt, $result );

		switch ( $decision->action ) {
			case RetryDecision::ACTION_DONE:
				$this->logs->mark_status( $log_ids, PushLog::STATUS_SENT );
				$this->jobs->increment_counts( $job_id, count( $log_ids ), 0 );
				$this->maybe_finalize( $job_id );
				return;

			case RetryDecision::ACTION_RETRY:
				if ( function_exists( 'as_schedule_single_action' ) ) {
					as_schedule_single_action(
						time() + $decision->delay_seconds,
						PushJobScheduler::ACTION_RUN_BATCH,
						array( $job_id, $log_ids, $attempt + 1 ),
						PushJobScheduler::GROUP
					);
				}
				return;

			case RetryDecision::ACTION_FAIL:
				$this->logs->mark_status( $log_ids, PushLog::STATUS_FAILED, $decision->error ?? $result->error_message );
				$this->jobs->increment_counts( $job_id, 0, count( $log_ids ) );
				$this->maybe_finalize( $job_id );
				return;

			case RetryDecision::ACTION_ABORT_AUTH:
				$this->jobs->mark_status( $job_id, PushJob::STATUS_ABORTED_AUTH, $decision->error ?? '' );
				return;
		}
	}

	private function maybe_finalize( int $job_id ): void {
		$pending = $this->logs->count_for_job( $job_id, PushLog::STATUS_PENDING );
		if ( $pending > 0 ) {
			return;
		}

		$job = $this->jobs->find_by_id( $job_id );
		if ( $job === null ) {
			return;
		}

		if ( $job->failed_count === 0 ) {
			$this->jobs->mark_status( $job_id, PushJob::STATUS_SENT );
		} elseif ( $job->sent_count === 0 ) {
			$this->jobs->mark_status( $job_id, PushJob::STATUS_FAILED );
		} else {
			$this->jobs->mark_status( $job_id, PushJob::STATUS_PARTIAL );
		}
	}
}
