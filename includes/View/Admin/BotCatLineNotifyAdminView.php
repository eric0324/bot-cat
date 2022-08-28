<?php

class BotCatLineNotifyAdminView
{

    public function bot_cat_line_notify_admin(): void
    {
        add_submenu_page(
            'bot-cat',
            'LINE Notify',
            'LINE Notify',
            'manage_options',
            'bot-cat-line-notify-admin',
            [&$this, 'bot_cat_view']);
    }

    /**
     * @return void
     */
    public function bot_cat_view(): void
    {
        $options = get_option(BOT_CAT_OPTION_PREFIX . 'line_notify');
        $serviceOptions = new BotCatNotificationOptions('line_notify');

        ?>
        <div class="wrap">
        <h2><?php _e('LINE Notify Settings', 'bot-cat') ?></h2>
        <form method="post" action="options.php">
        <?php settings_fields(BOT_CAT_OPTION_PREFIX . 'line_notify'); ?>
        <?php do_settings_sections(BOT_CAT_OPTION_PREFIX . 'line_notify'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Notification enabled', 'bot-cat') ?></th>
                <td>
                    <input
                            type="checkbox"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>line_notify[is_enable]"
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