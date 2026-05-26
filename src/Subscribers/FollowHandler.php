<?php
/**
 * @package BotCat\Subscribers
 */

declare(strict_types=1);

namespace BotCat\Subscribers;

use BotCat\Channel\ChannelSettingsRepository;
use BotCat\Channel\ReplyClient;
use BotCat\Tags\WelcomeMessageBuilder;

/**
 * Handles the LINE `follow` webhook event.
 *
 * Upserts the subscriber row with `status = active`, best-effort
 * fetches the LINE profile, and (when wired with WelcomeMessageBuilder)
 * sends a one-time welcome reply on first follow.
 */
class FollowHandler {

	public const RETRY_HOOK = 'botcat_retry_profile_fetch';

	public function __construct(
		private readonly SubscriberRepository $subscribers,
		private readonly ChannelSettingsRepository $channel_settings,
		private readonly LineProfileFetcher $profiles,
		private readonly ?WelcomeMessageBuilder $welcome = null,
		private readonly ?ReplyClient $reply_client = null
	) {
	}

	public function handle( string $line_user_id, int $event_timestamp, ?string $reply_token = null ): void {
		$existing    = $this->subscribers->find_by_line_id( $line_user_id );
		$is_new      = $existing === null;
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
		} else {
			$this->subscribers->upsert_active( $line_user_id, null, null, $followed_at );

			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action(
					self::RETRY_HOOK,
					array( $line_user_id ),
					'bot-cat'
				);
			}
		}

		if ( $is_new && $reply_token !== null && $reply_token !== '' && $this->welcome !== null && $this->reply_client !== null ) {
			$message = $this->welcome->build();
			if ( $message !== null ) {
				$this->reply_client->reply( $channel->access_token(), $reply_token, $message );
			}
		}
	}
}
