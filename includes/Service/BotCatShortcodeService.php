<?php

namespace BotCat\Service;

class BotCatShortcodeService {

	/**
	 * Registers the shortcodes for the bot-cat OAuth button list.
	 *
	 * This method adds the 'bot-cat-oauth' shortcode to the WordPress shortcode system,
	 * using the 'bot_cat_oauth_button_list' method of this class.
	 *
	 * @return void
	 */
	public function register_shortcodes(): void {
		add_shortcode( 'bot-cat-oauth', [ &$this, 'bot_cat_oauth_button_list' ] );
	}

	/**
	 * Returns the rendered HTML for the bot-cat OAuth button list.
	 *
	 * This method instantiates the BotCatOAuthService class and calls its bot_cat_oauth_view() method,
	 * which generates the HTML to display the bot-cat OAuth button list.
	 *
	 * @return string The rendered HTML for the bot-cat OAuth button list.
	 */
	public function bot_cat_oauth_button_list(): string {
		return ( new BotCatOAuthService() )->bot_cat_oauth_view();
	}
}