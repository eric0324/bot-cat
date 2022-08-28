<?php

class BotCatTelegramService
{

    private $bot_api_url;
    private $send_message_path;

    public function __construct()
    {
        $this->bot_api_url = 'https://api.telegram.org/bot';
        $this->send_message_path = '/sendMessage';
    }

    public function bot_cat_send_text_message($to, $message): void
    {

        $options = get_option(BOT_CAT_OPTION_PREFIX . 'telegram');

        foreach ($to as $uuid) {
            $url = $this->bot_api_url . $options['api_token'] . $this->send_message_path . "?chat_id=" . $uuid . "&text=" . urlencode($message);

            wp_remote_post($url,
                [
                    'method' => 'GET',
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ]
                ]
            );
        }
    }
}