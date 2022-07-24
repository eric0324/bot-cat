<?php

class BotCatAdminView
{
    public function __construct()
    {

    }

    public function bot_cat_admin(): void
    {
        add_menu_page(
            'Bot Cat',
            'Bot Cat',
            'manage_options',
            'bot-cat',
            [&$this, 'view'],
            'dashicons-testimonial',
            '120'
        );
    }

    /**
     * @return void
     */
    public function view(): void
    {
        $options = get_option(BOT_CAT_OPTION_PREFIX . 'basic');

        ?>
        <div class="wrap">
        <h2><?php _e('Basic Settings', 'bot-cat') ?></h2>
        <form method="post" action="options.php">
        <?php settings_fields(BOT_CAT_OPTION_PREFIX . 'basic'); ?>
        <?php do_settings_sections(BOT_CAT_OPTION_PREFIX . 'basic'); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Bot Cat Redirect Token', 'bot-cat') ?></th>
                <td>
                    <input
                            type="password"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>basic[redirect_token]"
                            value="<?php if (isset($options['redirect_token']))
                                echo esc_attr($options['redirect_token']) ?>"
                    >
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Bot Cat Key', 'bot-cat') ?></th>
                <td>
                    <input
                            type="password"
                            name="<?php echo esc_attr(BOT_CAT_OPTION_PREFIX) ?>basic[key]"
                            value="<?php if (isset($options['key']))
                                echo esc_attr($options['key']) ?>"
                    >
                </td>
            </tr>
        </table>
        <?php


        submit_button();
    }
}