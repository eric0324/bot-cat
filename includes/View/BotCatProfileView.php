<?php

/**
 * Class BotCatProfileView
 *
 * A class for displaying extra fields for the user profile with Bot Cat integration.
 */
class BotCatProfileView
{
	private BotCatOAuthService $bot_cat_oauth_service;

	public function __construct()
	{
		$this->bot_cat_oauth_service = new BotCatOAuthService();
	}

	/**
	 * Function to display extra fields for the user profile.
	 *
	 * This function retrieves the HTML code for the OAuth view and
	 * displays it on the user profile page.
	 *
	 * @return void
	 */
	public function bot_cat_extra_user_profile_fields(): void
    {
		?> <?php
		    $html = $this->bot_cat_oauth_service->bot_cat_oauth_view();
		    echo($html);
	    ?> <?php
    }
}