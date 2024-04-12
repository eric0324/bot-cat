<?php

/**
 * Class BotCatAdminView
 *
 * This class represents the admin view for the BotCat plugin.
 */

namespace BotCat\View\Admin;


defined( 'ABSPATH' ) || exit;

class BotCatAdminView {
	/**
	 * Displays the BotCat admin settings page.
	 *
	 * @return void
	 *
	 */
	public static function bot_cat_view(): void {
		$options = get_option( BOT_CAT_OPTION_PREFIX . 'basic' );

		?>
        <div class="wrap">
		<?php
		if ( ( ! isset( $options['redirect_token'], $options['key'] ) ) ||
		     ( $options['redirect_token'] === '' ) ||
		     ( $options['key'] === '' )
		) {
			$url  = 'https://bot-cat.com/documents';
			$link = sprintf( wp_kses( __( 'Are you having problems? Please read our <a href="%s">documents</a> .', 'bot-cat' ), array( 'a' => array( 'href' => array() ) ) ), esc_url( $url ) );
			echo '<div class="update-nag notice notice-warning inline">' . $link . '</div>';
		}
		?>
        <div></div>
        <h2><?php _e( 'Basic Settings', 'bot-cat' ) ?></h2>
        <form method="post" action="options.php">
		<?php settings_fields( BOT_CAT_OPTION_PREFIX . 'basic' ); ?>
		<?php do_settings_sections( BOT_CAT_OPTION_PREFIX . 'basic' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="redirect-token"><?php _e( 'Bot Cat Redirect Token', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="redirect-token"
                            type="password"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>basic[redirect_token]"
                            value="<?php if ( isset( $options['redirect_token'] ) )
								echo esc_attr( $options['redirect_token'] ) ?>"
                    >
                </td>
            </tr>
        </table>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="key"><?php _e( 'Bot Cat Key', 'bot-cat' ) ?></label>
                </th>
                <td>
                    <input
                            id="key"
                            type="password"
                            name="<?php echo esc_attr( BOT_CAT_OPTION_PREFIX ) ?>basic[key]"
                            value="<?php if ( isset( $options['key'] ) )
								echo esc_attr( $options['key'] ) ?>"
                    >
                </td>
            </tr>
        </table>
		<?php


		submit_button();
	}

	/**
	 * Registers the BotCat admin menu page.
	 *
	 * @return void
	 */
	public static function bot_cat_admin(): void {
		$instance = new self();
		add_menu_page(
			'Home',
			'BotCat',
			'manage_options',
			'bot-cat',
			[ $instance, 'bot_cat_view' ],
			'dashicons-testimonial',
			'120'
		);
	}
}