<?php

class BotCatNotificationService
{
    private $role_service;
    private $enable_service;
	private $bot_cat_message_service;

	public function __construct()
    {
        $this->role_service            = new BotCatRoleService();
		$this->bot_cat_message_service = new BotCatMessageService();
        $this->enable_service          = $this->role_service->get_enable_services();
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_post_publish_alert($post_ID, $post, $update): void
    {
        if ($post->post_type !== 'post') {
            return;
        }

        if ($post->post_status !== 'publish') {
            return;
        }

        $service_uuids = $this->role_service->get_service_uuids('publish_post');

        $user = get_userdata($post->post_author);
        $message = $this->bot_cat_message_service->generate_text();

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_post_review_alert($post_ID, $post, $update): void
    {
        if ($post->post_type !== 'post') {
            return;
        }

        if ($post->post_status !== 'pending') {
            return;
        }

        $service_uuids = $this->role_service->get_service_uuids('pending_post');

        $user = get_userdata($post->post_author);
        $message = $user->user_login . __(' pending ', 'bot-cat') . $post->post_title . "\nLink: " . get_permalink($post_ID);

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_new_comment_alert($comment_ID): void
    {
        $service_uuids = $this->role_service->get_service_uuids('new_comments');
        $comment = get_comment($comment_ID);
        $message = __('You have a new comment: ', 'bot-cat') . "\n" . $comment->comment_content;

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }

    }

    public function bot_cat_new_user_alert($user_ID)
    {

        $service_uuids = $this->role_service->get_service_uuids('new_users');

        $message = __('You have a new user register.', 'bot-cat');

        $user_info = get_userdata($user_ID);
        $message .= __('Username:', 'bot-cat') . $user_info->user_login;

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_new_product_alert($new_status, $old_status, $post): void
    {
        if (
            'product' !== $post->post_type ||
            'publish' !== $new_status ||
            'publish' === $old_status
        ) {
            return;
        }

        $service_uuids = $this->role_service->get_service_uuids('new_product');

        $message = __('New Product: ', 'bot-cat') . $post->post_title . "\nLink: " . get_permalink($post->ID);

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_low_stock_alert($product): void
    {
        $service_uuids = $this->role_service->get_service_uuids('low_stock_product');

        $message = __('[Low Stock] Product: ', 'bot-cat') . $product->get_name() . "\nLink: " . get_permalink($product->get_id());

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_no_stock_alert($product): void
    {
        $service_uuids = $this->role_service->get_service_uuids('out_stock_product');

        $message = __('[No Stock] Product: ', 'bot-cat') . $product->get_name() . "\nLink: " . get_permalink($product->get_id());

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $message);
        }
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_new_order_alert($order_ID): void
    {
        $order = wc_get_order($order_ID);

        $service_uuids = $this->role_service->get_can_manage_woocommerce_service_uuids('new_order');

        $customer_uuids = $this->role_service->get_customer_service_uuids('new_order', $order);

        $admin_message = __('New Order: ', 'bot-cat') . "\n" . __('Link: ', 'bot-cat') . $order->get_edit_order_url();
        $customer_message = __('New Order: ', 'bot-cat') . "\n" . __('Detail: ', 'bot-cat') . $order->get_view_order_url();

        if (in_array('line', $this->enable_service, false)) {
            $line_notification = new BotCatLineService();
            $line_notification->send_text_message($service_uuids['line'], $admin_message);
            $line_notification->send_text_message($customer_uuids['line'], $customer_message);
        }

        if (in_array('line_notify', $this->enable_service, false)) {
            $line_notification = new BotCatLineNotifyService();
            $line_notification->notify($service_uuids['line_notify'], $admin_message);
            $line_notification->notify($service_uuids['line_notify'], $customer_message);
        }

        if (in_array('telegram', $this->enable_service, false)) {
            $telegram_notification = new BotCatTelegramService();
            $telegram_notification->send_text_message($service_uuids['telegram'], $admin_message);
            $telegram_notification->send_text_message($customer_uuids['telegram'], $customer_message);
        }
    }
}