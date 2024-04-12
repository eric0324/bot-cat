<?php
/**
 * Plugin Name:       Bot Cat
 * Plugin URI:        https://ericwu.asia/plugins/bot-cat
 * Description:       Simply send chatbot notifications via plugins
 * Requires at least: 6.4.2
 * Requires PHP:      8.2
 * Author:            Eric Wu
 * Author URI:        https://ericwu.asia/
 * Version:           1.1.6
 * Text Domain:       bot-cat
 */

namespace BotCat;

defined( 'ABSPATH' ) || exit;


require_once __DIR__ . '/includes/BotCatConstants.php';
require_once __DIR__ . '/vendor/autoload.php';

BotCatInitializer::init();