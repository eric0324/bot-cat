<?php

class BotCatShortcodeService {

	public function register_shortcodes(): void {
		add_shortcode('bot-cat-oauth', [&$this, 'oauth_button_list']);
	}

	public function oauth_button_list(){
		$bot_cat_oauth_service = new BotCatOAuthService();
		return $bot_cat_oauth_service->oauth_view();
	}
}