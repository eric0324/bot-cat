<?php

class BotCatProfileView
{
    public function __construct()
    {

    }

    public function bc_extra_user_profile_fields(): void
    {
        ?>
        <h2><?php _e('Connect and Get Notifications', 'bot-cat') ?></h2>
        <table class="form-table">
            <?php

            $user_info = wp_get_current_user();
            $user_id = $user_info->data->ID;

            $user_token = get_user_meta($user_id, BOT_CAT_OPTION_PREFIX . '_token');

            if (!$user_token) {
                $strings = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $user_token = $user_id . str_shuffle($strings);
                update_user_meta($user_id, BOT_CAT_OPTION_PREFIX . 'token', $user_token);
            } else {
                $user_token = $user_token[0];
            }

            $basic_options = get_option(BOT_CAT_OPTION_PREFIX . 'basic');
            $redirect_token = $basic_options['redirect_token'];

            // LINE
            $options = get_option(BOT_CAT_OPTION_PREFIX . 'line');
            if (isset($options['is_enable'])) {
                $url = BOT_CAT_OFFICIAL_URL . 'auth/' . $redirect_token . '/line?user=' . $user_token;
                $line_uuid = get_user_meta($user_id, BOT_CAT_OPTION_PREFIX . 'line_uuid');
                ?>
                <tr>
                    <th>LINE</th>
                    <td>
                        <?php if (isset($line_uuid) && $line_uuid): ?>
                            <?php _e('Connected', 'bot-cat') ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url($url) ?>"><?php _e('Connect', 'bot-cat') ?></a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php
            }

            // LINE Notify
            $options = get_option(BOT_CAT_OPTION_PREFIX . 'line_notify');
            if (isset($options['is_enable'])) {
                $url = BOT_CAT_OFFICIAL_URL . 'auth/' . $redirect_token . '/line_notify?user=' . $user_token;
                $line_notify_uuid = get_user_meta($user_id, BOT_CAT_OPTION_PREFIX . 'line_notify_uuid');
                ?>
                <tr>
                    <th>LINE Notify</th>
                    <td>
                        <?php if (isset($line_notify_uuid) && $line_notify_uuid): ?>
                            <?php _e('Connected', 'bot-cat') ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url($url) ?>"><?php _e('Connect', 'bot-cat') ?></a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php
            }

            // Telegram
            $options = get_option(BOT_CAT_OPTION_PREFIX . 'telegram');
            if (isset($options['is_enable'])) {
                $url = BOT_CAT_OFFICIAL_URL . 'redirect/' . $redirect_token . '/telegram?user=' . $user_token;
                $telegram_uuid = get_user_meta($user_id, BOT_CAT_OPTION_PREFIX . 'telegram_uuid');
                ?>
                <tr>
                    <th>Telegram</th>
                    <td>
                        <?php if (isset($telegram_uuid) && $telegram_uuid): ?>
                            <?php _e('Connected', 'bot-cat') ?>
                        <?php else: ?>
                            <script async src="https://telegram.org/js/telegram-widget.js?19"
                                    data-telegram-login="<?php echo esc_attr($options['chat_id']) ?>"
                                    data-size="medium" data-auth-url="<?php echo esc_url($url) ?>"
                                    data-request-access="write"></script>
                        <?php endif ?>
                    </td>
                </tr>
                <?php
            }

            ?>
        </table>
        <?php
    }
}