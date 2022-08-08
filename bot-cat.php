<?php
/**
 * Plugin Name:       Bot Cat
 * Plugin URI:        https://ericwu.asia/plugins/bot-cat
 * Description:       Simply send chatbot notifications via plugins
 * Requires at least: 5.2
 * Requires PHP:      5.6
 * Author:            Eric Wu
 * Author URI:        https://ericwu.asia/
 * Version:           1.0.6
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
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/Admin/Partial/BotCatNotificationOptions.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/BotCatProfileView.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/Admin/BotCatAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/Admin/BotCatLineAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/Admin/BotCatLineNotifyAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/View/Admin/BotCatTelegramAdminView.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Service/BotCatLineNotifyService.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Service/BotCatLineService.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Service/BotCatTelegramService.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Service/BotCatNotificationService.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Service/BotCatRoleService.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Auth/BotCatBasic.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Auth/BotCatLineAuth.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Auth/BotCatLineNotifyAuth.php';
require_once BOT_CAT_PLUGIN_DIR . '/includes/Auth/BotCatTelegramAuth.php';


/**
 * Registration Options Group
 */
register_setting(BOT_CAT_OPTION_PREFIX . 'basic', BOT_CAT_OPTION_PREFIX . 'basic');
register_setting(BOT_CAT_OPTION_PREFIX . 'line_notify', BOT_CAT_OPTION_PREFIX . 'line_notify');
register_setting(BOT_CAT_OPTION_PREFIX . 'line', BOT_CAT_OPTION_PREFIX . 'line');
register_setting(BOT_CAT_OPTION_PREFIX . 'telegram', BOT_CAT_OPTION_PREFIX . 'telegram');


/**
 * Profile page
 */
$profile = new BotCatProfileView();

add_action('show_user_profile', [$profile, 'bot_cat_extra_user_profile_fields']);
add_action('edit_user_profile', [$profile, 'bot_cat_extra_user_profile_fields']);
if (class_exists('WooCommerce')) {
    add_action('woocommerce_edit_account_form', [$profile, 'bot_cat_extra_user_profile_fields']);
}


/**
 * Admin Pages
 */
$admin_page = new BotCatAdminView();
$line_admin_page = new BotCatLineAdminView();
$line_notify_admin_page = new BotCatLineNotifyAdminView();
$telegram_admin_page = new BotCatTelegramAdminView();

add_action('admin_menu', [$admin_page, 'bot_cat_admin']);
add_action('admin_menu', [$line_admin_page, 'bot_cat_line_admin']);
add_action('admin_menu', [$line_notify_admin_page, 'bot_cat_line_notify_admin']);
add_action('admin_menu', [$telegram_admin_page, 'bot_cat_telegram_admin']);


/**
 * Auth API
 */
$line_auth = new BotCatLineAuth();
$line_notify_auth = new BotCatLineNotifyAuth();
$telegram_auth = new BotCatTelegramAuth();
add_action('rest_api_init', [$line_auth, 'register_rest_route']);
add_action('rest_api_init', [$line_notify_auth, 'register_rest_route']);
add_action('rest_api_init', [$telegram_auth, 'register_rest_route']);


/**
 * Notification
 */
$notification_service = new BotCatNotificationService();

add_action('comment_post', [$notification_service, 'bot_cat_new_comment_alert'], 10, 1);
add_action('user_register', [$notification_service, 'bot_cat_new_user_alert'], 10, 1);
add_action('wp_insert_post', [$notification_service, 'bot_cat_post_publish_alert'], 10, 3);
add_action('wp_insert_post', [$notification_service, 'bot_cat_post_review_alert'], 10, 3);

if (!function_exists('is_plugin_active')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

if (is_plugin_active('woocommerce/woocommerce.php')) {
    add_action('transition_post_status', [$notification_service, 'bot_cat_new_product_alert'], 10, 3);
    add_action('woocommerce_low_stock', [$notification_service, 'bot_cat_low_stock_alert'], 10, 1);
    add_action('woocommerce_no_stock', [$notification_service, 'bot_cat_no_stock_alert'], 10, 1);
    add_action('woocommerce_new_order', [$notification_service, 'bot_cat_new_order_alert'], 1, 1);
}