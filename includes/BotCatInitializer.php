<?php

namespace BotCat;

use BotCat\Api\BotCatLineAuthApi;
use BotCat\Api\BotCatLineNotifyAuthApi;
use BotCat\Api\BotCatMessageApi;
use BotCat\Api\BotCatTelegramAuthApi;
use BotCat\Service\BotCatNotificationService;
use BotCat\Service\BotCatShortcodeService;
use BotCat\View\Admin\BotCatAdminView;
use BotCat\View\Admin\BotCatLineAdminView;
use BotCat\View\Admin\BotCatLineNotifyAdminView;
use BotCat\View\Admin\BotCatSlackAdminView;
use BotCat\View\Admin\BotCatTelegramAdminView;
use BotCat\View\BotCatProfileView;

defined( 'ABSPATH' ) || exit;

/**
 * Class BotCatInitializer
 *
 * This class initializes the Bot Cat plugin by registering various actions and hooks.
 */
class BotCatInitializer {

	/**
	 * Initializes the BotCat plugin.
	 *
	 * This method adds various action hooks to initialize different aspects of the BotCat plugin.
	 * - The 'plugins_loaded' action is used to load the text domain for translations.
	 * - The 'init' action is used to register plugin settings, shortcodes, profile view,
	 *   notification service, admin view, and API routes.
	 *
	 * @return void
	 */
	public static function bot_cat_init(): void {
		add_action( 'plugins_loaded', [ __CLASS__, 'bot_cat_load_text_domain' ] );
		add_action( 'init', [ __CLASS__, 'bot_cat_register_settings' ] );
		add_action( 'init', [ __CLASS__, 'bot_cat_register_shot_codes' ] );
		add_action( 'init', [ __CLASS__, 'bot_cat_profile_view' ] );
		add_action( 'init', [ __CLASS__, 'bot_cat_notification_service' ] );
		add_action( 'init', [ __CLASS__, 'bot_cat_admin_view' ] );
		add_action( 'rest_api_init', [ __CLASS__, 'bot_cat_api' ] );
	}

	/**
	 * Loads the text domain for the BotCat plugin.
	 *
	 * This method loads the translation files for the BotCat plugin so that the
	 * strings can be translated into different languages. It uses the WordPress
	 * function load_plugin_textdomain() to load the translation files for the
	 * 'bot-cat' text domain. The translation files should be located in the
	 * 'languages' directory of the BotCat plugin.
	 *
	 * @return void
	 */
	public static function bot_cat_load_text_domain(): void {
		load_plugin_textdomain( 'bot-cat', false, BOT_CAT_PLUGIN_DIR . 'languages' );
	}

	/**
	 * Registers the settings for the BotCat plugin.
	 *
	 * This method registers the settings for the BotCat plugin using the WordPress
	 * function `register_setting()`. It registers the following settings:
	 *
	 * 1. Basic Settings: BOT_CAT_OPTION_PREFIX + 'basic'
	 * 2. Line Notify Settings: BOT_CAT_OPTION_PREFIX + 'line_notify'
	 * 3. Line Settings: BOT_CAT_OPTION_PREFIX + 'line'
	 * 4. Telegram Settings: BOT_CAT_OPTION_PREFIX + 'telegram'
	 * 5. Slack Settings: BOT_CAT_OPTION_PREFIX + 'slack'
	 *
	 * @return void
	 */
	public static function bot_cat_register_settings(): void {
		register_setting( BOT_CAT_OPTION_PREFIX . 'basic', BOT_CAT_OPTION_PREFIX . 'basic' );
		register_setting( BOT_CAT_OPTION_PREFIX . 'line_notify', BOT_CAT_OPTION_PREFIX . 'line_notify' );
		register_setting( BOT_CAT_OPTION_PREFIX . 'line', BOT_CAT_OPTION_PREFIX . 'line' );
		register_setting( BOT_CAT_OPTION_PREFIX . 'telegram', BOT_CAT_OPTION_PREFIX . 'telegram' );
		register_setting( BOT_CAT_OPTION_PREFIX . 'slack', BOT_CAT_OPTION_PREFIX . 'slack' );
	}

	/**
	 * Registers the shortcode for the BotCat OAuth button list.
	 *
	 * This method adds a shortcode for displaying the BotCat OAuth button list.
	 * The "bot-cat-oauth" shortcode can be used in WordPress content to render the OAuth button list.
	 * The shortcode calls the "bot_cat_oauth_button_list" method in the BotCatShortcodeService class.
	 *
	 * @return void
	 */
	public static function bot_cat_register_shot_codes(): void {
		add_shortcode( 'bot-cat-oauth', [ BotCatShortcodeService::class, 'bot_cat_oauth_button_list' ] );
	}

	/**
	 * Registers the profile view for the BotCat plugin.
	 *
	 * This method adds action hooks to display extra user profile fields on the user profile page.
	 * The action hooks are triggered when the user profile page is being rendered for a user.
	 * The 'show_user_profile' action is used for the user's own profile page,
	 * and the 'edit_user_profile' action is used for editing other user profiles.
	 *
	 * If the WooCommerce plugin is enabled, an additional action hook 'woocommerce_edit_account_form'
	 * is added to display the extra user profile fields on the WooCommerce edit account form.
	 *
	 * @return void
	 */
	public static function bot_cat_profile_view(): void {

		add_action( 'show_user_profile', [ BotCatProfileView::class, 'bot_cat_extra_user_profile_fields' ] );
		add_action( 'edit_user_profile', [ BotCatProfileView::class, 'bot_cat_extra_user_profile_fields' ] );
		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_edit_account_form', [
				BotCatProfileView::class,
				'bot_cat_extra_user_profile_fields'
			] );
		}
	}

	/**
	 * Registers the admin view for the BotCat plugin.
	 *
	 * This method adds action hooks to the "admin_menu" action, which will be triggered
	 * when the WordPress admin menu is being rendered. The action hooks will call the
	 * corresponding admin methods in the BotCatAdminView, BotCatLineAdminView,
	 * BotCatLineNotifyAdminView, BotCatTelegramAdminView, and BotCatSlackAdminView classes.
	 *
	 * @return void
	 */
	public static function bot_cat_admin_view(): void {
		add_action( 'admin_menu', [ BotCatAdminView::class, 'bot_cat_admin' ] );
		add_action( 'admin_menu', [ BotCatLineAdminView::class, 'bot_cat_line_admin' ] );
		add_action( 'admin_menu', [ BotCatLineNotifyAdminView::class, 'bot_cat_line_notify_admin' ] );
		add_action( 'admin_menu', [ BotCatTelegramAdminView::class, 'bot_cat_telegram_admin' ] );
		add_action( 'admin_menu', [ BotCatSlackAdminView::class, 'bot_cat_slack_admin' ] );
	}

	/**
	 * The bot_cat_api method is responsible for registering REST routes for authentication and messaging APIs.
	 *
	 * @return void
	 */
	public static function bot_cat_api(): void {
		add_action( 'rest_api_init', [ BotCatLineAuthApi::class, 'register_rest_route' ] );
		add_action( 'rest_api_init', [ BotCatLineNotifyAuthApi::class, 'register_rest_route' ] );
		add_action( 'rest_api_init', [ BotCatTelegramAuthApi::class, 'register_rest_route' ] );
		add_action( 'rest_api_init', [ BotCatMessageApi::class, 'register_rest_route' ] );
	}

	/**
	 * The bot_cat_notification_service method is responsible for setting up various actions and hooks
	 * for sending notifications related to comments, users, posts, and WooCommerce events.
	 *
	 * @return void
	 */
	public static function bot_cat_notification_service(): void {

		add_action( 'comment_post', [ BotCatNotificationService::class, 'bot_cat_new_comment_alert' ], 10, 1 );
		add_action( 'user_register', [ BotCatNotificationService::class, 'bot_cat_new_user_alert' ], 10, 1 );
		add_action( 'wp_insert_post', [ BotCatNotificationService::class, 'bot_cat_post_publish_alert' ], 10, 3 );
		add_action( 'wp_insert_post', [ BotCatNotificationService::class, 'bot_cat_post_review_alert' ], 10, 3 );

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		}

		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			add_action( 'transition_post_status', [
				BotCatNotificationService::class,
				'bot_cat_new_product_alert'
			], 10, 3 );
			add_action( 'woocommerce_low_stock', [
				BotCatNotificationService::class,
				'bot_cat_low_stock_alert'
			], 10, 1 );
			add_action( 'woocommerce_no_stock', [ BotCatNotificationService::class, 'bot_cat_no_stock_alert' ], 10, 1 );
			add_action( 'woocommerce_new_order', [
				BotCatNotificationService::class,
				'bot_cat_new_order_alert'
			], 1, 1 );
		}
	}
}

