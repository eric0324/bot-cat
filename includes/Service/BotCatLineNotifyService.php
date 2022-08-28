<?php

class BotCatLineNotifyService
{

    private $notify_url;

    public function __construct()
    {
        $this->notify_url = "https://notify-api.line.me/api/";
    }

    /**
     * @param $to
     * @param $text
     *
     * @return void
     */
    public function bot_cat_send_text_message($to, $text): void
    {

        foreach ($to as $uuid) {
            $request_params = [
                "headers" => "Authorization: Bearer " . $uuid,
                "body" => [
                    "message" => $text
                ]
            ];

            wp_remote_post($this->notify_url . 'notify', $request_params);
        }
    }
}