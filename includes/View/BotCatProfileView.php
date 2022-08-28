<?php

class BotCatProfileView
{
	/**
	 * @var BotCatOAuthService
	 */
	private $bot_cat_oauth_service;

	public function __construct()
	{
		$this->bot_cat_oauth_service = new BotCatOAuthService();
	}

	public function bot_cat_extra_user_profile_fields(): void
    {
		?> <?php
		    $html = $this->bot_cat_oauth_service->bot_cat_oauth_view();
		    echo($html);
	    ?> <?php
    }
}