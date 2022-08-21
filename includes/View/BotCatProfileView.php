<?php

class BotCatProfileView
{
    public function bot_cat_extra_user_profile_fields(): void
    {
        $bot_cat_oauth_service = new BotCatOAuthService();

		?> <?php
	    $html = $bot_cat_oauth_service->oauth_view();
	    echo($html);
	    ?> <?php
    }
}