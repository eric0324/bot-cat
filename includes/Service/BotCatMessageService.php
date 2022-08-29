<?php

class BotCatMessageService {

	private $bot_cat_message;

	public function __construct() {
		$this->bot_cat_message = get_option( BOT_CAT_OPTION_PREFIX . 'messages' );
	}

	/**
	 * Generate post type text
	 *
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

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Post type message', 'bot-cat' ),
			__( 'Post type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Generate comment type text
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

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Comment type message', 'bot-cat' ),
			__( 'Comment type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Generate user type text
	 *
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

		return $this->bot_cat_str_replace_message(
			__( '[Admin] User type message', 'bot-cat' ),
			__( 'User type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Generate product type text
	 *
	 * @param $action_name
	 * @param $product
	 *
	 * @return array
	 */
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

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Product type message', 'bot-cat' ),
			__( 'Product type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * Generate order type text
	 *
	 * @param $action_name
	 * @param $order
	 *
	 * @return array
	 */
	public function bot_cat_generate_order_type_text( $action_name, $order ): array {
		$keyword_text = [
			'[total]'               => $order->get_total(),
			'[payment_method]'      => $order->get_payment_method(),
			'[order_time]'          => $order->get_date_created(),
			'[customer_note]'       => $order->get_customer_note(),
			'[billing_first_name]'  => $order->get_billing_first_name(),
			'[billing_last_name]'   => $order->get_billing_last_name(),
			'[billing_company]'     => $order->get_billing_company(),
			'[billing_address_1]'   => $order->get_billing_address_1(),
			'[billing_address_2]'   => $order->get_billing_address_2(),
			'[billing_city]'        => $order->get_billing_city(),
			'[billing_state]'       => $order->get_billing_state(),
			'[billing_postcode]'    => $order->get_billing_postcode(),
			'[billing_country]'     => $order->get_billing_country(),
			'[billing_email]'       => $order->get_billing_email(),
			'[billing_phone]'       => $order->get_billing_phone(),
			'[shipping_first_name]' => $order->get_shipping_first_name(),
			'[shipping_last_name]'  => $order->get_shipping_last_name(),
			'[shipping_company]'    => $order->get_shipping_company(),
			'[shipping_address_1]'  => $order->get_shipping_address_1(),
			'[shipping_address_2]'  => $order->get_shipping_address_2(),
			'[shipping_city]'       => $order->get_shipping_city(),
			'[shipping_state]'      => $order->get_shipping_state(),
			'[shipping_postcode]'   => $order->get_shipping_postcode(),
			'[shipping_country]'    => $order->get_shipping_country(),
			'[shipping_phone]'      => $order->get_shipping_phone()
		];

		$item_string = '';
		foreach ( $order->get_items() as $item_id => $item ) {
			$item_string .= ($item->get_name() . ' ');
		}
		$keyword_text['[order_product]'] = $item_string;

		return $this->bot_cat_str_replace_message(
			__( '[Admin] Order type message', 'bot-cat' ),
			__( 'Order type message', 'bot-cat' ),
			$keyword_text,
			$this->bot_cat_message['admin'][ $action_name ],
			$this->bot_cat_message['user'][ $action_name ]
		);
	}

	/**
	 * @param $admin_message
	 * @param $user_message
	 * @param $keyword_list
	 * @param $admin_message_template
	 * @param $user_message_template
	 *
	 * @return array
	 */
	private function bot_cat_str_replace_message(
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