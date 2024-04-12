<?php

namespace BotCat\View\Admin;

use BotCat\View\Admin\Partial\BotCatTargetOptions;


/**
 * Class BotCatLineAdminView
 *
 * Represents the LINE admin view for the Bot Cat plugin.
 */

defined( 'ABSPATH' ) || exit;

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
			$url  = 'https://bot-cat.com/console/line';
			$link = sprintf( wp_kses( __( 'Please go to <a href="%s">Bot Cat Console</a> to input settings .', 'bot-cat' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
			echo '<div class="update-nag notice notice-warning inline">' . $link . '</div>';
		}
		?>
        <h2><?php _e( 'LINE Settings', 'bot-cat' ) ?></h2>
        <form method="post" action="options.php">
		<?php settings_fields( BOT_CAT_OPTION_PREFIX . 'line' ); ?>
		<?php do_settings_sections( BOT_CAT_OPTION_PREFIX . 'line' ); ?>
        <input
                type="hidden"
                name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>line[channel_access_token]"
                value="<?php if ( isset( $options['channel_access_token'] ) )
					echo esc_attr( $options['channel_access_token'] ) ?>"
        >
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Notification enabled', 'bot-cat' ) ?></th>
                <td>
                    <input
                            type="checkbox"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>line[is_enable]"
                            value="1"
						<?php if ( isset( $options['is_enable'] ) )
							echo esc_attr( checked( 1, $options['is_enable'], false ) ) ?>
                    >
                    <label><?php _e( 'Enable', 'bot-cat' ) ?></label>

                </td>
            </tr>
        </table>

        <hr>
		<?php

		$serviceOptions->bot_cat_role_can_receive_message();
	}
}