<?php

class BotCatShortcodeService {

	public function register_shortcodes(): void {
		add_shortcode('bot-cat-oauth', [&$this, 'oauth_button_list']);
	}

	public function oauth_button_list(){
		return ( new BotCatOAuthService() )->oauth_view();
	}
}