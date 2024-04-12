<?php
/**
 * Class BotCatNotificationService
 *
 * This class is responsible for sending notifications for various events.
 */


namespace BotCat\Service;

use BotCat\Service\Api\BotCatLineNotifyService;
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
	private BotCatLineNotifyService $bot_cat_line_notify_service;
	private BotCatSlackService $bot_cat_slack_service;

	public function __construct() {
		$this->bot_cat_role_service = new BotCatRoleService();
		$this->enable_service       = $this->bot_cat_role_service->get_enable_services();

		$this->bot_cat_message_service     = new BotCatMessageService();
		$this->bot_cat_line_service        = new BotCatLineService();
		$this->bot_cat_line_notify_service = new BotCatLineNotifyService();
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

				if ( $service === 'line_notify' ) {
					if ( isset( $uuids[ $service ]['admin'] ) && count( $uuids[ $service ]['admin'] ) > 0 ) {
						$this->bot_cat_line_notify_service->bot_cat_send_text_message( $uuids[ $service ]['admin'], $messages['admin'] );
					}

					if ( isset( $uuids[ $service ]['user'] ) && count( $uuids[ $service ]['user'] ) > 0 ) {
						$this->bot_cat_line_notify_service->bot_cat_send_text_message( $uuids[ $service ]['user'], $messages['user'] );
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

	/**
	 * Sends a new product alert to users who can receive it.
	 *
	 * @param string $new_status The new status of the post.
	 * @param string $old_status The old status of the post.
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_product_alert( string $new_status, string $old_status, WP_Post $post ): void {
		if (
			'product' !== $post->post_type ||
			'publish' !== $new_status ||
			'publish' === $old_status
		) {
			return;
		}

		$product = wc_get_product( $post->ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_product_type_uuids( 'new_product', $product );

		$messages = $this->bot_cat_message_service->bot_cat_generate_product_type_text( 'new_product', $product );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Sends a low stock alert to users who can receive it.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_low_stock_alert( WP_Post $post ): void {
		$product = wc_get_product( $post->ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_product_type_uuids( 'low_stock', $product );

		$messages = $this->bot_cat_message_service->bot_cat_generate_product_type_text( 'low_stock', $product );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Sends a no stock alert to users who can receive it.
	 *
	 * @param WP_Post $post The post object.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_no_stock_alert( WP_Post $post ): void {
		$product = wc_get_product( $post->ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_product_type_uuids( 'no_stock', $product );

		$messages = $this->bot_cat_message_service->bot_cat_generate_product_type_text( 'no_stock', $product );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * Sends a new order alert to users who can receive it.
	 *
	 * @param int $order_ID The ID of the order.
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_order_alert( int $order_ID ): void {
		$order = wc_get_order( $order_ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_order_type_uuids( 'new_order', $order );

		$messages = $this->bot_cat_message_service->bot_cat_generate_order_type_text( 'new_order', $order );

		$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids['telegram']['admin'], $messages['user'] );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}
}