<?php
/**
 * Class BotCatShortcodeService
 *
 * This class provides a service for generating the rendered HTML for the bot-cat OAuth button list.
 */

namespace BotCat\Service;

defined( 'ABSPATH' ) || exit;


class BotCatShortcodeService {

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