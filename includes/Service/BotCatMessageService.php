<?php

class BotCatMessageService {

	private $bot_cat_message;

	public function __construct() {
		$this->bot_cat_message = get_option( BOT_CAT_OPTION_PREFIX . 'messages' );
	}

	/**
	 * @param $action_name
	 * @param $post
	 *
	 * @return array
	 */
	public function bot_cat_generate_post_type_text( $action_name, $post ): array {

		$userdata = get_userdata( $post->post_author );

		$keyword_text = [
			'[title]'   => $post->post_title,
			'[content]' => $post->post_content,
			'[date]'    => $post->post_date,
			'[id]'      => $post->ID,
			'[link]'    => get_permalink( $post->ID ),
			'[author]'  => $userdata->display_name
		];

		return $this->bot_cat_str_ireplace_meesage(
			__( '[Admin] Post published.', 'bot-cat' ),
			__( 'Post published.', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['users'][ $action_name ]
		);
	}

	/**
	 * Generate new comment text
	 *
	 * @param $action_name
	 * @param $comment
	 *
	 * @return array
	 */
	public function bot_cat_generate_comment_type_text( $action_name, $comment ): array {

		$userdata = get_userdata( $comment->user_id );

		$keyword_text = [
			'[author_email]' => $comment->comment_author_email,
			'[content]'      => $comment->comment_content,
			'[date]'         => $comment->comment_date,
			'[id]'           => $comment->comment_ID,
			'[author_ip]'    => $comment->comment_author_ip,
			'[author_name]'  => $userdata->display_name
		];

		return $this->bot_cat_str_ireplace_meesage(
			__( '[Admin] New comment.', 'bot-cat' ),
			__( 'New comment.', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['users'][ $action_name ]
		);
	}

	/**
	 * @param $action_name
	 * @param $user
	 *
	 * @return array
	 */
	public function bot_cat_generate_user_type_text( $action_name, $user ): array {
		$keyword_text = [
			'[username]'        => $user->user_nicename,
			'[name]'            => $user->display_name,
			'[id]'              => $user->ID,
			'[registered_date]' => $user->user_registered,
			'[email]'           => $user->user_email
		];

		return $this->bot_cat_str_ireplace_meesage(
			__( '[Admin] New user.', 'bot-cat' ),
			__( 'New user.', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['users'][ $action_name ]
		);
	}

	public function bot_cat_generate_product_type_text( $action_name, $product ): array {
		$keyword_text = [
			'[name]'              => $product->get_name(),
			'[date_created]'      => $product->post_date,
			'[featured]'          => $product->get_featured(),
			'[sku]'               => $product->get_sku(),
			'[price]'             => $product->get_price(),
			'[regular_price]'     => $product->get_regular_price(),
			'[sale_price]'        => $product->get_sale_price(),
			'[date_on_sale_from]' => $product->get_date_on_sale_from(),
			'[date_on_sale_to]'   => $product->get_date_on_sale_to(),
			'[total_sales]'       => $product->get_total_sales(),
			'[stock_quantity]'    => $product->get_stock_quantity(),
			'[stock_status]'      => $product->get_stock_status(),
			'[backorders]'        => $product->get_backorders(),
			'[sold_individually]' => $product->get_sold_individually(),
			'[weight]'            => $product->get_weight(),
			'[length]'            => $product->get_length(),
			'[width]'             => $product->get_width(),
			'[height]'            => $product->get_height(),
			'[virtual]'           => $product->get_virtual(),
			'[downloadable]'      => $product->get_downloadable(),
			'[link]'              => get_permalink( $product->get_id() )
		];

		return $this->bot_cat_str_ireplace_meesage(
			__( '[Admin] New product.', 'bot-cat' ),
			__( 'New product.', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['users'][ $action_name ]
		);
	}

	public function bot_cat_generate_order_type_text( $action_name, $order ): array {
		$keyword_text = [
			'[total]'               => $order,
			'[order_product]'       => $order,
			'[payment_method]'      => $order,
			'[order_time]'          => $order,
			'[customer_note]'       => $order,
			'[billing_first_name]'  => $order,
			'[billing_last_name]'   => $order,
			'[billing_company]'     => $order,
			'[billing_address_1]'   => $order,
			'[billing_address_2]'   => $order,
			'[billing_city]'        => $order,
			'[billing_state]'       => $order,
			'[billing_postcode]'    => $order,
			'[billing_country]'     => $order,
			'[billing_email]'       => $order,
			'[billing_phone]'       => $order,
			'[shipping_first_name]' => $order,
			'[shipping_last_name]'  => $order,
			'[shipping_company]'    => $order,
			'[shipping_address_1]'  => $order,
			'[shipping_address_2]'  => $order,
			'[shipping_city]'       => $order,
			'[shipping_state]'      => $order,
			'[shipping_postcode]'   => $order,
			'[shipping_country]'    => $order,
			'[shipping_phone]'      => $order
		];

		return $this->bot_cat_str_ireplace_meesage(
			__( '[Admin] New order.', 'bot-cat' ),
			__( 'New order.', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['users'][ $action_name ]
		);
	}

	private function bot_cat_str_ireplace_meesage(
		$admin_message,
		$user_message,
		$keyword_list,
		$admin_message_template,
		$user_message_template
	): array {
		if ( $admin_message_template ) {
			$admin_message = str_ireplace( array_keys( $keyword_list ), $keyword_list, $admin_message_template );
		}

		if ( $user_message_template ) {
			$user_message = str_ireplace( array_keys( $keyword_list ), $keyword_list, $user_message_template );
		}

		return [
			'admin' => $admin_message,
			'user'  => $user_message
		];
	}
}