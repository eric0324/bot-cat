<?php

/**
 * Class BotCatAdminView
 *
 * This class represents the admin view for the BotCat plugin.
 */

namespace BotCat\View\Admin;


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
		// Basic settings for open source version
		?>
        <div></div>
        <h2><?php _e( 'Basic Settings', 'bot-cat' ) ?></h2>
        <form method="post" action="options.php">
		<?php settings_fields( BOT_CAT_OPTION_PREFIX . 'basic' ); ?>
		<?php do_settings_sections( BOT_CAT_OPTION_PREFIX . 'basic' ); ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Plugin Status', 'bot-cat' ) ?></th>
                <td>
                    <p><?php _e( 'BotCat plugin is active and ready to send notifications.', 'bot-cat' ) ?></p>
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