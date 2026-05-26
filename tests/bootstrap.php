<?php
/**
 * PHPUnit bootstrap for unit tests.
 *
 * Loads Composer's autoloader and Brain Monkey so individual tests can
 * stub WordPress functions without a full WP install.
 *
 * @package BotCat\Tests
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/wordpress/');
}

if (!defined('WPINC')) {
    define('WPINC', 'wp-includes');
}

if (!defined('BOT_CAT_FILE')) {
    define('BOT_CAT_FILE', dirname(__DIR__) . '/bot-cat.php');
}

if (!defined('BOT_CAT_DIR')) {
    define('BOT_CAT_DIR', dirname(__DIR__) . '/');
}

if (!defined('BOT_CAT_VERSION')) {
    define('BOT_CAT_VERSION', '1.0.0');
}

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

if (!defined('OBJECT')) {
    define('OBJECT', 'OBJECT');
}
