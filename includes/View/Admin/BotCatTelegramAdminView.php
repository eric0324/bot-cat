<?php

class BotCatTelegramAdminView
{
    public function bot_cat_telegram_admin(): void
    {
        add_submenu_page(
            'bot-cat',
            'Telegram',
            'Telegram',
            'manage_options',
            'bot-cat-telegram-admin',
            [&$this, 'bot_cat_view']);
    }

    /**
     * @return void
     */
    public function bot_cat_view(): void
    {
        $options = get_option(BOT_CAT_OPTION_PREFIX . 'telegram');
        $serviceOptions = new BotCatNotificationOptions('telegram');

        ?>
        <div class="wrap">
        <?php
            if (!isset($options['chat_id'], $options['api_token']) || !$options['chat_id'] || !$options['api_token']) {
	            $url = 'https://bot-cat.com/console/telegram';
	            $link = sprintf( wp_kses( __( 'Please go to <a href="%s">Bot Cat Console</a> to input settings .', 'bot-cat' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
	            echo '<div class="update-nag notice notice-warning inline">' . $link . '</div>';
            }
        ?>
        <h2><?php _e('Telegram Settings', 'bot-cat') ?></h2>
        <form method="post" action="options.php">
        <?php settings_fields(BOT_CAT_OPTION_PREFIX . 'telegram'); ?>
        <?php do_settings_sections(BOT_CAT_OPTION_PREFIX . 'telegram'); ?>
        <input
                type="hidden"
                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>telegram[api_token]"
                value="<?php if (isset($options['api_token']))
                    echo esc_attr($options['api_token']) ?>"
        >
        <input
                type="hidden"
                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>telegram[chat_id]"
                value="<?php if (isset($options['chat_id']))
                    echo esc_attr($options['chat_id']) ?>"
        >
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Notification enabled', 'bot-cat') ?></th>
                <td>
                    <input
                            type="checkbox"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>telegram[is_enable]"
                            value="1"
                        <?php if (isset($options['is_enable']))
                            echo esc_attr(checked(1, $options['is_enable'], false)) ?>
                    >
                    <label><?php _e('Enable', 'bot-cat') ?></label>

                </td>
            </tr>
        </table>

        <hr>
        <?php
        $serviceOptions->bot_cat_init();
    }
}