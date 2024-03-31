<?php

/**
 * Class BotCatSlackAdminView
 *
 * Provides the Slack admin view in the Bot Cat plugin settings.
 */
class BotCatSlackAdminView
{
	/**
	 * Displays the Slack admin page in the Bot Cat plugin settings.
	 *
	 * @return void
	 */
    public function bot_cat_slack_admin(): void
    {
        add_submenu_page(
            'bot-cat',
            'Slack',
            'Slack',
            'manage_options',
            'bot-cat-slack-admin',
            [&$this, 'bot_cat_view']);
    }

	/**
	 * Displays the Slack settings form and handles submission of the form.
	 *
	 * @return void
	 */
    public function bot_cat_view(): void
    {
        $options = get_option(BOT_CAT_OPTION_PREFIX . 'slack');
        $serviceOptions = new BotCatTargetOptions('slack');

        ?>
        <div class="wrap">
        <?php
            if (!isset($options['webhook_url']) || !$options['webhook_url']) {
	            $url = 'https://bot-cat.com/console/slack';
	            $link = sprintf( wp_kses( __( 'Please go to <a href="%s">Bot Cat Console</a> to input settings .', 'bot-cat' ), array(  'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
	            echo '<div class="update-nag notice notice-warning inline">' . $link . '</div>';
            }
        ?>
        <h2><?php _e('Slack Settings', 'bot-cat') ?></h2>
        <form method="post" action="options.php">
        <?php settings_fields(BOT_CAT_OPTION_PREFIX . 'slack'); ?>
        <?php do_settings_sections(BOT_CAT_OPTION_PREFIX . 'slack'); ?>
        <input
                type="hidden"
                name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>slack[webhook_url]"
                value="<?php if (isset($options['webhook_url']))
                    echo esc_attr($options['webhook_url']) ?>"
        >
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Notification enabled', 'bot-cat') ?></th>
                <td>
                    <input
                            type="checkbox"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>slack[is_enable]"
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
	    $serviceOptions->bot_cat_webhook_can_receive_message();
    }
}