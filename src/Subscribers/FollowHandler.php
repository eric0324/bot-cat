<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

use BotCat\Channel\ChannelSettingsRepository;

/**
 * Handles the LINE `follow` webhook event.
 *
 * Upserts the subscriber row with `status = active`, then best-effort
 * fetches the LINE profile (display name, picture). If the profile fetch
 * fails the subscriber still exists; a retry is enqueued via Action
 * Scheduler.
 */
class FollowHandler {

	public const RETRY_HOOK = 'botcat_retry_profile_fetch';

	public function __construct(
		private readonly SubscriberRepository $subscribers,
		private readonly ChannelSettingsRepository $channel_settings,
		private readonly LineProfileFetcher $profiles
	) {
	}

	public function handle( string $line_user_id, int $event_timestamp ): void {
		$followed_at = gmdate( 'Y-m-d H:i:s', $event_timestamp );
		$channel     = $this->channel_settings->get();

		if ( ! $channel->is_configured() ) {
			$this->subscribers->upsert_active( $line_user_id, null, null, $followed_at );
			return;
		}

		$profile = $this->profiles->fetch( $line_user_id, $channel->access_token() );

		if ( $profile->ok ) {
			$this->subscribers->upsert_active(
				$line_user_id,
				$profile->display_name,
				$profile->picture_url,
				$followed_at
			);
			return;
		}

		$this->subscribers->upsert_active( $line_user_id, null, null, $followed_at );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action(
				self::RETRY_HOOK,
				array( $line_user_id ),
				'bot-cat'
			);
		}
	}
}
