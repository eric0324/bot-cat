<?php
/**
 * Plugin Name:       Bot Cat
 * Plugin URI:        https://ericwu.asia/plugins/bot-cat
 * Description:       Simply send chatbot notifications via plugins
 * Requires at least: 6.4.2
 * Requires PHP:      8.1
 * Author:            Eric Wu
 * Author URI:        https://ericwu.asia/
 * Version:           1.1.4
 * Text Domain:       bot-cat
 */

define('BOT_CAT_PLUGIN_DIR', plugin_dir_path(__FILE__));

const BOT_CAT_OFFICIAL_URL = 'https://bot-cat.com/';
const BOT_CAT_OPTION_PREFIX = 'bot_cat_';
const BOT_CAT_REST_NAMESPACE_PREFIX = 'bot-cat';
const SERVICES = ['line', 'line_notify', 'telegram'];

/**
 * Import dependent codes
 */
require_once BOT_CAT_PLUGIN_DIR . 'includes/View/Admin/Partial/BotCatNotificationOptions.php';

require_once BOT_CAT_PLUGIN_DIR . 'includes/View/BotCatProfileView.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/View/Admin/BotCatAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/View/Admin/BotCatLineAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/View/Admin/BotCatLineNotifyAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/View/Admin/BotCatTelegramAdminView.php';

require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatAuthService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatOAuthService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatLineNotifyService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatLineService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatTelegramService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatMessageService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatNotificationService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatRoleService.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Service/BotCatShortcodeService.php';

require_once BOT_CAT_PLUGIN_DIR . 'includes/Api/BotCatMessageApi.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Api/BotCatLineAuthApi.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Api/BotCatLineNotifyAuthApi.php';
require_once BOT_CAT_PLUGIN_DIR . 'includes/Api/BotCatTelegramAuthApi.php';


/**
 * Registration Options Group
 */
register_setting( BOT_CAT_OPTION_PREFIX . 'basic', BOT_CAT_OPTION_PREFIX . 'basic' );
register_setting( BOT_CAT_OPTION_PREFIX . 'line_notify', BOT_CAT_OPTION_PREFIX . 'line_notify' );
register_setting( BOT_CAT_OPTION_PREFIX . 'line', BOT_CAT_OPTION_PREFIX . 'line' );
register_setting( BOT_CAT_OPTION_PREFIX . 'telegram', BOT_CAT_OPTION_PREFIX . 'telegram' );


/**
 * Shortcode
 */
$shortcode_service = new BotCatShortcodeService();

add_action('init', [$shortcode_service, 'register_shortcodes']);

/**
 * Profile page
 */
$bot_cat_profile_view = new BotCatProfileView();

add_action('show_user_profile', [$bot_cat_profile_view, 'bot_cat_extra_user_profile_fields']);
add_action('edit_user_profile', [$bot_cat_profile_view, 'bot_cat_extra_user_profile_fields']);
if (class_exists('WooCommerce')) {
    add_action('woocommerce_edit_account_form', [$bot_cat_profile_view, 'bot_cat_extra_user_profile_fields']);
}


/**
 * Admin Pages
 */
$bot_cat_admin_view             = new BotCatAdminView();
$bot_cat_line_admin_view        = new BotCatLineAdminView();
$bot_cat_line_notify_admin_view = new BotCatLineNotifyAdminView();
$bot_cat_telegram_admin_view = new BotCatTelegramAdminView();

add_action( 'admin_menu', [ $bot_cat_admin_view, 'bot_cat_admin' ] );
add_action( 'admin_menu', [ $bot_cat_line_admin_view, 'bot_cat_line_admin' ] );
add_action( 'admin_menu', [ $bot_cat_line_notify_admin_view, 'bot_cat_line_notify_admin' ] );
add_action( 'admin_menu', [ $bot_cat_telegram_admin_view, 'bot_cat_telegram_admin' ] );


/**
 * APIs
 */
$bot_cat_line_auth_api        = new BotCatLineAuthApi();
$bot_cat_line_notify_auth_api = new BotCatLineNotifyAuthApi();
$bot_cat_telegram_auth_api    = new BotCatTelegramAuthApi();
$bot_cat_message_api          = new BotCatMessageApi();
add_action( 'rest_api_init', [ $bot_cat_line_auth_api, 'register_rest_route' ] );
add_action( 'rest_api_init', [ $bot_cat_line_notify_auth_api, 'register_rest_route' ] );
add_action( 'rest_api_init', [ $bot_cat_telegram_auth_api, 'register_rest_route' ] );
add_action( 'rest_api_init', [ $bot_cat_message_api , 'register_rest_route' ] );


/**
 * Notification
 */
$bot_cat_notification_service = new BotCatNotificationService();

add_action( 'comment_post', [ $bot_cat_notification_service, 'bot_cat_new_comment_alert' ], 10, 1 );
add_action( 'user_register', [ $bot_cat_notification_service, 'bot_cat_new_user_alert' ], 10, 1 );
add_action( 'wp_insert_post', [ $bot_cat_notification_service, 'bot_cat_post_publish_alert' ], 10, 3 );
add_action('wp_insert_post', [$bot_cat_notification_service, 'bot_cat_post_review_alert'], 10, 3);

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('transition_post_status', [$bot_cat_notification_service, 'bot_cat_new_product_alert'], 10, 3);
    add_action('woocommerce_low_stock', [$bot_cat_notification_service, 'bot_cat_low_stock_alert'], 10, 1);
    add_action('woocommerce_no_stock', [$bot_cat_notification_service, 'bot_cat_no_stock_alert'], 10, 1);
    add_action('woocommerce_new_order', [$bot_cat_notification_service, 'bot_cat_new_order_alert'], 1, 1);
}