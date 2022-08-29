<?php

class BotCatNotificationService {
	private $bot_cat_role_service;
	private $enable_service;
	private $bot_cat_message_service;
	private $bot_cat_line_service;
	private $bot_cat_telegram_service;
	private $bot_cat_line_notify_service;

	public function __construct() {
		$this->bot_cat_role_service = new BotCatRoleService();
		$this->enable_service       = $this->bot_cat_role_service->get_enable_services();

		$this->bot_cat_message_service     = new BotCatMessageService();
		$this->bot_cat_line_service        = new BotCatLineService();
		$this->bot_cat_line_notify_service = new BotCatLineNotifyService();
		$this->bot_cat_telegram_service    = new BotCatTelegramService();
	}

	/**
	 * @param $post_ID
	 * @param $post
	 * @param $update
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_post_publish_alert( $post_ID, $post, $update ): void {
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
	 * @param $post_ID
	 * @param $post
	 * @param $update
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_post_review_alert( $post_ID, $post, $update ): void {
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
	 * @param $comment_ID
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_comment_alert( $comment_ID ): void {
		$comment = get_comment( $comment_ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_comment_type_uuids( 'new_comment', $comment );

		$messages = $this->bot_cat_message_service->bot_cat_generate_comment_type_text( 'new_comment', $comment );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * @param $user_ID
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_user_alert( $user_ID ): void {
		$user = get_userdata($user_ID);

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_user_type_uuids( 'new_user', $user );

		$messages = $this->bot_cat_message_service->bot_cat_generate_user_type_text( 'new_user', $user );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * @param $new_status
	 * @param $old_status
	 * @param $post
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_product_alert( $new_status, $old_status, $post ): void {
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
	 * @param $post
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_low_stock_alert( $post ): void {
		$product = wc_get_product( $post->ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_product_type_uuids( 'low_stock', $product );

		$messages = $this->bot_cat_message_service->bot_cat_generate_product_type_text( 'low_stock', $product );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * @param $post
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_no_stock_alert( $post ): void {
		$product = wc_get_product( $post->ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_product_type_uuids( 'no_stock', $product );

		$messages = $this->bot_cat_message_service->bot_cat_generate_product_type_text( 'no_stock', $product );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * @param $order_ID
	 *
	 * @return void
	 * @throws JsonException
	 */
	public function bot_cat_new_order_alert( $order_ID ): void {
		$order = wc_get_order( $order_ID );

		$uuids = $this->bot_cat_role_service->bot_cat_get_can_receive_wc_order_type_uuids( 'new_order', $order );

		$messages = $this->bot_cat_message_service->bot_cat_generate_order_type_text( 'new_order', $order );

		$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids['telegram']['admin'], $messages['user'] );

		$this->bot_cat_send_text_message( $uuids, $messages );
	}

	/**
	 * @param $uuids
	 * @param $messages
	 *
	 * @return void
	 * @throws JsonException
	 */
	private function bot_cat_send_text_message( $uuids, $messages ): void {
		foreach ($this->enable_service as $service) {
			if ( in_array( $service, $this->enable_service, false ) ) {
				if ($service === 'line') {
					if (isset($uuids[$service]['admin']) && count($uuids[$service]['admin']) > 0) {
						$this->bot_cat_line_service->bot_cat_send_text_message( $uuids[$service]['admin'], $messages['admin'] );
					}

					if (isset($uuids[$service]['user']) && count($uuids[$service]['user']) > 0) {
						$this->bot_cat_line_service->bot_cat_send_text_message( $uuids[$service]['user'], $messages['user'] );
					}
				}

				if ($service === 'line_notify') {
					if (isset($uuids[$service]['admin']) && count($uuids[$service]['admin']) > 0) {
						$this->bot_cat_line_notify_service->bot_cat_send_text_message( $uuids[$service]['admin'], $messages['admin'] );
					}

					if (isset($uuids[$service]['user']) && count($uuids[$service]['user']) > 0) {
						$this->bot_cat_line_notify_service->bot_cat_send_text_message( $uuids[$service]['user'], $messages['user'] );
					}
				}
				
				if ($service === 'telegram') {
					if (isset($uuids[$service]['admin']) && count($uuids[$service]['admin']) > 0) {
						$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids[$service]['admin'], $messages['admin'] );
					}

					if (isset($uuids[$service]['user']) && count($uuids[$service]['user']) > 0) {
						$this->bot_cat_telegram_service->bot_cat_send_text_message( $uuids[$service]['user'], $messages['user'] );
					}
				}
			}
		}
	}
}