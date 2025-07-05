<?php
/**
 * Class BotCatNotificationService
 *
 * This class is responsible for sending notifications for various events.
 */


namespace BotCat\Service;

use BotCat\Service\Api\BotCatLineService;
use BotCat\Service\Api\BotCatSlackService;
use BotCat\Service\Api\BotCatTelegramService;
use JsonException;
use WP_Post;


class BotCatNotificationService {
	private BotCatRoleService $bot_cat_role_service;
	private array $enable_service;
	private BotCatMessageService $bot_cat_message_service;
	private BotCatLineService $bot_cat_line_service;
	private BotCatTelegramService $bot_cat_telegram_service;
	private BotCatSlackService $bot_cat_slack_service;

	public function __construct() {
		$this->bot_cat_role_service = new BotCatRoleService();
		$this->enable_service       = $this->bot_cat_role_service->get_enable_services();

		$this->bot_cat_message_service     = new BotCatMessageService();
		$this->bot_cat_line_service        = new BotCatLineService();
		$this->bot_cat_telegram_service    = new BotCatTelegramService();
		$this->bot_cat_slack_service       = new BotCatSlackService();
	}

	/**
	 * Sends a notification when a post is published.
	 *
	 * @param int $post_ID The ID of the published post.
	 * @param WP_Post $post The published post object.
	 * @param bool $update Whether this is an existing post being updated or a new post being published.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_post_publish_alert( int $post_ID, WP_Post $post, bool $update ): void {
		if ( $post->post_type !== 'post' ) {
			return;
		}

		if ( $post->post_status !== 'publish' ) {
			return;
		}

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_post_type_uuids( 'publish_post', $post );

		$messages = $this->bot_cat_message_service->bot_cat_generate_post_type_text( 'publish_post', $post );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Sends a text message to the users who can receive it through various services.
	 *
	 * @param array $uuids The UUIDs of the users who can receive the message for each service.
	 * @param array $messages The messages to be sent for each service.
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function bot_cat_send_text_message( array $uuids, array $messages ): void {
		foreach ( $this->enable_service as $service ) {
			if ( in_array( $service, $this->enable_service, false ) ) {
				if ( $service === 'line' ) {
					if ( isset( $uuids[ $service ]['admin'] ) && count( $uuids[ $service ]['admin'] ) > 0 ) {
						$this->bot_cat_line_service->bot_cat_send_text_message( $uuids[ $service ]['admin'], $messages['admin'] );
					}

					if ( isset( $uuids[ $service ]['user'] ) && count( $uuids[ $service ]['user'] ) > 0 ) {
						$this->bot_cat_line_service->bot_cat_send_text_message( $uuids[ $service ]['user'], $messages['user'] );
					}
				}

				if ( $service === 'telegram' ) {
					if ( isset( $uuids[ $service ]['admin'] ) && count( $uuids[ $service ]['admin'] ) > 0 ) {
						$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids[ $service ]['admin'], $messages['admin'] );
					}

					if ( isset( $uuids[ $service ]['user'] ) && count( $uuids[ $service ]['user'] ) > 0 ) {
						$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids[ $service ]['user'], $messages['user'] );
					}
				}

				if ( $service === 'slack' ) {
					// For Slack, we use webhook so we don't need specific user UUIDs
					// We send both admin and user messages if there are any recipients configured
					$has_recipients = ( isset( $uuids[ $service ]['admin'] ) && count( $uuids[ $service ]['admin'] ) > 0 ) ||
					                  ( isset( $uuids[ $service ]['user'] ) && count( $uuids[ $service ]['user'] ) > 0 );
					
					if ( $has_recipients ) {
						// Send admin message if there are admin recipients
						if ( isset( $uuids[ $service ]['admin'] ) && count( $uuids[ $service ]['admin'] ) > 0 ) {
							$this->bot_cat_slack_service->bot_cat_send_text_message( [], $messages['admin'] );
						}
						// Send user message if there are user recipients (and it's different from admin message)
						if ( isset( $uuids[ $service ]['user'] ) && count( $uuids[ $service ]['user'] ) > 0 && $messages['admin'] !== $messages['user'] ) {
							$this->bot_cat_slack_service->bot_cat_send_text_message( [], $messages['user'] );
						}
					}
				}
			}
		}
	}

	/**
	 * Sends a post review alert.
	 *
	 * @param int $post_ID The ID of the post.
	 * @param WP_Post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 *
	 * @return void
	 * @throws JsonException If there is an error encoding the message into JSON.
	 */
	public function bot_cat_post_review_alert( int $post_ID, WP_Post $post, bool $update ): void {
		if ( $post->post_type !== 'post' ) {
			return;
		}

		if ( $post->post_status !== 'pending' ) {
			return;
		}

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_post_type_uuids( 'review_post', $post );

		$messages = $this->bot_cat_message_service->bot_cat_generate_post_type_text( 'review_post', $post );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Alerts the bot about a new comment.
	 *
	 * @param int $comment_ID The ID of the comment.
	 *
	 * @return void
	 * @throws JsonException if there is an error while generating the text message.
	 */
	public function bot_cat_new_comment_alert( int $comment_ID ): void {
		$comment = get_comment( $comment_ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_comment_type_uuids( 'new_comment', $comment );

		$messages = $this->bot_cat_message_service->bot_cat_generate_comment_type_text( 'new_comment', $comment );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Sends a new user alert to specified UUIDs.
	 *
	 * @param int $user_ID The ID of the user.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_user_alert( int $user_ID ): void {
		$user = get_userdata( $user_ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_user_type_uuids( 'new_user', $user );

		$messages = $this->bot_cat_message_service->bot_cat_generate_user_type_text( 'new_user', $user );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}
}