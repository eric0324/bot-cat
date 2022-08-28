<?php

class BotCatShortcodeService {

	public function register_shortcodes(): void {
		add_shortcode('bot-cat-oauth', [&$this, 'bot_cat_oauth_button_list' ]);
	}

	public function bot_cat_oauth_button_list(){
		return ( new BotCatOAuthService() )->bot_cat_oauth_view();
	}
}