<?php

/**
 * Class BotCatLineAdminView
 *
 * Represents the LINE admin view for the Bot Cat plugin.
 */

namespace BotCat\View\Admin;

use BotCat\View\Admin\Partial\BotCatTargetOptions;


class BotCatLineAdminView {

	/**
	 * Registers the LINE admin page under the Bot Cat plugin submenu.
	 *
	 * @return void
	 */
	public static function bot_cat_line_admin(): void {
		$instance = new self();
		add_submenu_page(
			'bot-cat',
			'LINE',
			'LINE',
			'manage_options',
			'bot-cat-line-admin',
			[ $instance, 'bot_cat_view' ] );
	}

	/**
	 * Display the view for LINE settings in the Bot Cat plugin.
	 *
	 * @return void
	 */
	public function bot_cat_view(): void {
		$options        = get_option( BOT_CAT_OPTION_PREFIX . 'line' );
		$serviceOptions = new BotCatTargetOptions( 'line' );

		?>
        <div class="wrap">
		<?php
		if ( ! isset( $options['channel_access_token'] ) || ! $options['channel_access_token'] ) {
			echo '<div class="update-nag notice notice-warning inline">' . __( 'Please configure your LINE Bot Channel Access Token below to enable LINE notifications.', 'bot-cat' ) . '</div>';
		}
		?>
        <h2><?php _e( 'LINE Settings', 'bot-cat' ) ?></h2>
        <form method="post" action="options.php">
		<?php settings_fields( BOT_CAT_OPTION_PREFIX . 'line' ); ?>
		<?php do_settings_sections( BOT_CAT_OPTION_PREFIX . 'line' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="channel_access_token"><?php _e( 'Channel Access Token', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="channel_access_token"
                            type="password"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>line[channel_access_token]"
                            value="<?php if ( isset( $options['channel_access_token'] ) )
								echo esc_attr( $options['channel_access_token'] ) ?>"
                            style="width: 100%; max-width: 500px;"
                            placeholder="<?php _e( 'Enter your LINE Bot Channel Access Token', 'bot-cat' ) ?>"
                    >
                    <p class="description"><?php _e( 'Get this token from LINE Developers Console > Messaging API > Channel access token', 'bot-cat' ) ?></p>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="channel_secret"><?php _e( 'Channel Secret', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="channel_secret"
                            type="password"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>line[channel_secret]"
                            value="<?php if ( isset( $options['channel_secret'] ) )
								echo esc_attr( $options['channel_secret'] ) ?>"
                            style="width: 100%; max-width: 500px;"
                            placeholder="<?php _e( 'Enter your LINE Bot Channel Secret', 'bot-cat' ) ?>"
                    >
                    <p class="description"><?php _e( 'Get this from LINE Developers Console > Basic settings > Channel secret (optional for security)', 'bot-cat' ) ?></p>
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Webhook URL', 'bot-cat' ) ?></th>
                <td>
                    <code><?php echo esc_url( rest_url( 'bot-cat/line/webhook' ) ); ?></code>
                    <p class="description"><?php _e( 'Use this URL as your LINE Bot webhook URL in LINE Developers Console > Messaging API > Webhook settings', 'bot-cat' ) ?></p>
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
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>line[is_enable]"
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
            <h4><?php _e( 'Setting up LINE Bot', 'bot-cat' ) ?></h4>
            <ol>
                <li><?php _e( 'Create a LINE Bot in LINE Developers Console', 'bot-cat' ) ?></li>
                <li><?php _e( 'Copy the Channel Access Token and Channel Secret from your LINE Bot settings', 'bot-cat' ) ?></li>
                <li><?php _e( 'Paste them in the fields above and save', 'bot-cat' ) ?></li>
                <li><?php _e( 'Set the webhook URL in LINE Developers Console to the URL shown above', 'bot-cat' ) ?></li>
                <li><?php _e( 'Enable webhook in LINE Developers Console', 'bot-cat' ) ?></li>
                <li><?php _e( 'Users can now add your bot as a friend and send a message to connect their accounts', 'bot-cat' ) ?></li>
            </ol>
        </div>

        <hr>
		<?php

		$serviceOptions->bot_cat_role_can_receive_message();
	}
}