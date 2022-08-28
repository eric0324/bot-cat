<?php

class BotCatLineService
{

    private $multicast_message_url;

    public function __construct()
    {
        $this->multicast_message_url = 'https://api.line.me/v2/bot/message/multicast';
    }

    /**
     * @throws JsonException
     */
    public function bot_cat_send_text_message($to, $message)
    {
        $options = get_option(BOT_CAT_OPTION_PREFIX . 'line');

        $response = wp_remote_post($this->multicast_message_url,
            [
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                    'Authorization' => 'Bearer ' . $options['channel_access_token']
                ],
                'data_format' => 'body',
                'body' => json_encode([
                    'to' => $to,
                    'messages' => [
                        0 => [
                            'type' => 'text',
                            'text' => $message
                        ]
                    ]
                ], JSON_THROW_ON_ERROR)
            ],
        );

        $json = $response['body'] ?? [];

        return json_decode($json, false, 512, JSON_THROW_ON_ERROR);
    }
}