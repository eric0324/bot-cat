<?php

class BotCatLineNotifyAuth
{
    private $basic;

    public function __construct()
    {
        $this->basic = new BotCatBasic();
    }

    public function register_rest_route(): void
    {

        register_rest_route(BOT_CAT_REST_NAMESPACE_PREFIX, '/line_notify/uuid', [
            'methods' => 'POST',
            'callback' => [&$this, 'store_uuid'],
            'permission_callback' => '__return_true'
        ]);
    }

    /**
     * @param $request
     *
     * @return void
     * @throws JsonException
     */
    public function store_uuid($request): void
    {

        $can_access = $this->basic->check_key($request);

        if (!$can_access) {
            wp_send_json(['Message' => 'Unauthorized'], 401);
        }

        $user_info = $this->basic->get_user_by_token($request);

        if ($user_info[0] && isset($user_info[0]->ID)) {
            update_user_meta($user_info[0]->ID, BOT_CAT_OPTION_PREFIX . 'line_notify_uuid', $request['uuid']);
        }

        wp_send_json(['Message' => 'Success'], 200);
    }
}