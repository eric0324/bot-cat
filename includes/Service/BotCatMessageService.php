<?php

class BotCatMessageService {

	private $bot_cat_user_message;
	private $bot_cat_admin_message;

	public function __construct() {
		$this->bot_cat_user_message = get_option( BOT_CAT_OPTION_PREFIX . 'message_user' );
		$this->bot_cat_admin_message = get_option( BOT_CAT_OPTION_PREFIX . 'message_admin' );
	}


	public function generate_post_publish_text( $post ): array {
		$admin_message = 'Admin 新文章';
		$user_message = 'user 新文章';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_post_review_text( $post ): array {
		$admin_message = 'Admin 新審核文章';
		$user_message = 'user 新審核文章';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_new_comment_text( $comment ): array {
		$admin_message = 'Admin 新回覆';
		$user_message = 'user 新回覆';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_new_user_text( $user ): array {
		$admin_message = 'Admin 新使用者';
		$user_message = 'user 新使用者';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_new_product_text( $product ): array {
		$admin_message = 'Admin 新商品';
		$user_message = 'user 新商品';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_low_stock_text( $product ): array {
		$admin_message = 'Admin 低庫存';
		$user_message = 'user 低庫存';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_no_stock_text( $product ): array {
		$admin_message = 'Admin 無庫存';
		$user_message = 'user 無庫存';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}

	public function generate_new_order_text( $order ): array {
		$admin_message = 'Admin 新訂單';
		$user_message = 'user 新訂單';

		return [
			'admin'  => $admin_message,
			'user' => $user_message
		];
	}
}