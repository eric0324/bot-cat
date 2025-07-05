<?php

/**
 * Class BotCatSlackAdminView
 *
 * Provides the Slack admin view in the Bot Cat plugin settings.
 */

namespace BotCat\View\Admin;

use BotCat\View\Admin\Partial\BotCatTargetOptions;


class BotCatSlackAdminView {
	/**
	 * Displays the Slack admin page in the Bot Cat plugin settings.
	 *
	 * @return void
	 */
	public static function bot_cat_slack_admin(): void {
		$instance = new self();
		add_submenu_page(
			'bot-cat',
			'Slack',
			'Slack',
			'manage_options',
			'bot-cat-slack-admin',
			[ $instance, 'bot_cat_view' ] );
	}

	/**
	 * Displays the Slack settings form and handles submission of the form.
	 *
	 * @return void
	 */
	public function bot_cat_view(): void {
		$options        = get_option( BOT_CAT_OPTION_PREFIX . 'slack' );
		$serviceOptions = new BotCatTargetOptions( 'slack' );

		?>
        <div class="wrap">
		<?php
		if ( ! isset( $options['webhook_url'] ) || empty( $options['webhook_url'] ) ) {
			echo '<div class="update-nag notice notice-warning inline">' . __( 'Please configure your Slack webhook URL below to enable Slack notifications.', 'bot-cat' ) . '</div>';
		}
		?>
        <h2><?php _e( 'Slack Settings', 'bot-cat' ) ?></h2>
        <form method="post" action="options.php">
		<?php settings_fields( BOT_CAT_OPTION_PREFIX . 'slack' ); ?>
		<?php do_settings_sections( BOT_CAT_OPTION_PREFIX . 'slack' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="webhook_url"><?php _e( 'Slack Webhook URL', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="webhook_url"
                            type="url"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>slack[webhook_url]"
                            value="<?php if ( isset( $options['webhook_url'] ) )
								echo esc_attr( $options['webhook_url'] ) ?>"
                            style="width: 100%; max-width: 500px;"
                            placeholder="https://hooks.slack.com/services/..."
                    >
                    <p class="description"><?php _e( 'Create an Incoming Webhook in your Slack workspace and paste the URL here.', 'bot-cat' ) ?></p>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="channel"><?php _e( 'Default Channel', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="channel"
                            type="text"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>slack[channel]"
                            value="<?php if ( isset( $options['channel'] ) )
								echo esc_attr( $options['channel'] ) ?>"
                            style="width: 200px;"
                            placeholder="#general"
                    >
                    <p class="description"><?php _e( 'Default channel to send notifications (optional, leave empty to use webhook default)', 'bot-cat' ) ?></p>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="username"><?php _e( 'Bot Username', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="username"
                            type="text"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>slack[username]"
                            value="<?php if ( isset( $options['username'] ) )
								echo esc_attr( $options['username'] ) ?>"
                            style="width: 200px;"
                            placeholder="BotCat"
                    >
                    <p class="description"><?php _e( 'Display name for the bot (optional)', 'bot-cat' ) ?></p>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Notification enabled', 'bot-cat' ) ?></th>
                <td>
                    <input
                            id="is_enable"
                            type="checkbox"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>slack[is_enable]"
                            value="1"
						<?php if ( isset( $options['is_enable'] ) )
							echo esc_attr( checked( 1, $options['is_enable'], false ) ) ?>
                    >
                    <label for="is_enable"><?php _e( 'Enable', 'bot-cat' ) ?></label>
                </td>
            </tr>
        </table>

        <hr>

        <h3><?php _e( 'Instructions', 'bot-cat' ) ?></h3>
        <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 20px;">
            <h4><?php _e( 'Setting up Slack Webhook', 'bot-cat' ) ?></h4>
            <ol>
                <li><?php _e( 'Go to your Slack workspace settings', 'bot-cat' ) ?></li>
                <li><?php _e( 'Navigate to Apps > Incoming Webhooks', 'bot-cat' ) ?></li>
                <li><?php _e( 'Click "Add to Slack" and choose a channel', 'bot-cat' ) ?></li>
                <li><?php _e( 'Copy the webhook URL and paste it above', 'bot-cat' ) ?></li>
                <li><?php _e( 'Configure optional settings like channel and username', 'bot-cat' ) ?></li>
                <li><?php _e( 'Save settings and enable notifications', 'bot-cat' ) ?></li>
            </ol>
        </div>

        <hr>
		<?php
		$serviceOptions->bot_cat_role_can_receive_message();
	}
}