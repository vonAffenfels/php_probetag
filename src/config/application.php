<?php

/**
 * Your base production configuration goes in this file. Environment-specific
 * overrides go in their respective config/environments/{{WP_ENV}}.php file.
 *
 * A good default policy is to deviate from the production config as little as
 * possible. Try to define as much of your configuration in this file as you
 * can.
 */


use function Env\env;
use Roots\WPConfig\Config;

/** @var string Directory containing all of the site's files */
$root_dir = dirname(__DIR__);

/** @var string Document Root */
$webroot_dir = $root_dir . '/web';

/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = Dotenv\Dotenv::createUnsafeImmutable($root_dir);
if (file_exists($root_dir . '/.env')) {
    $dotenv->load();
    $dotenv->required(['WP_HOME', 'WP_SITEURL']);
    if (!env('DATABASE_URL')) {
        $dotenv->required(['DB_NAME', 'DB_USER', 'DB_PASSWORD']);
    }
}

/**
 * Set up our global environment constant and load its config first
 * Default: production
 */
$wpEnv = env('WP_ENV') ?? 'production';
define('WP_ENV', $wpEnv === 'local' ? 'development' : $wpEnv);
$wpEnvToAppEnv = [
    'local' => 'apedev',
    'development' => 'dev',
    'staging' => 'stage',
    'production' => 'prod',
];
define('APPLICATION_ENV', $wpEnvToAppEnv[$wpEnv] ?? 'prod');
define('WP_SENTRY_ENV', env('WP_ENV'));

/**
 * URLs
 */
Config::define('WP_HOME', env('WP_HOME'));
Config::define('WP_SITEURL', env('WP_SITEURL'));

/**
 * xLib Config
 */
Config::define('SCHEDULER_SEARCH_INDEX', env('SCHEDULER_SEARCH_INDEX'));
Config::define('SCHEDULER_SEARCH_TYPE', env('SCHEDULER_SEARCH_TYPE'));
Config::define('HUB_PAGE_OVERVIEW_CATEGORY', env('HUB_PAGE_OVERVIEW_CATEGORY'));
Config::define('FEEDBACK_EMAIL', env('FEEDBACK_EMAIL'));
Config::define('SEARCH_RESULT_ITEMS_PER_PAGE', env('SEARCH_RESULT_ITEMS_PER_PAGE'));

/**
 * Custom Content Directory
 */
Config::define('CONTENT_DIR', '/app');
Config::define('WP_CONTENT_DIR', $webroot_dir . Config::get('CONTENT_DIR'));
Config::define('WP_CONTENT_URL', Config::get('WP_HOME') . Config::get('CONTENT_DIR'));
//Config::define('WP_DEFAULT_THEME', Config::get('CONTENT_DIR') . '/themes');

/**
 * DB settings
 */
Config::define('DB_NAME', env('DB_NAME'));
Config::define('DB_USER', env('DB_USER'));
Config::define('DB_PASSWORD', env('DB_PASSWORD'));
Config::define('DB_HOST', env('DB_HOST') ?: 'localhost');
Config::define('DB_CHARSET', 'utf8mb4');
Config::define('DB_COLLATE', '');
$table_prefix = env('DB_PREFIX') ?: 'wp_';

if (env('DATABASE_URL')) {
    $dsn = (object) parse_url(env('DATABASE_URL'));

    Config::define('DB_NAME', substr($dsn->path, 1));
    Config::define('DB_USER', $dsn->user);
    Config::define('DB_PASSWORD', isset($dsn->pass) ? $dsn->pass : null);
    Config::define('DB_HOST', isset($dsn->port) ? "{$dsn->host}:{$dsn->port}" : $dsn->host);
}

/**
 * Authentication Unique Keys and Salts
 */
Config::define('AUTH_KEY', env('AUTH_KEY'));
Config::define('SECURE_AUTH_KEY', env('SECURE_AUTH_KEY'));
Config::define('LOGGED_IN_KEY', env('LOGGED_IN_KEY'));
Config::define('NONCE_KEY', env('NONCE_KEY'));
Config::define('AUTH_SALT', env('AUTH_SALT'));
Config::define('SECURE_AUTH_SALT', env('SECURE_AUTH_SALT'));
Config::define('LOGGED_IN_SALT', env('LOGGED_IN_SALT'));
Config::define('NONCE_SALT', env('NONCE_SALT'));

/**
 * Custom Settings
 */
Config::define('AUTOMATIC_UPDATER_DISABLED', true);
Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);
// Disable the plugin and theme file editor in the admin
Config::define('DISALLOW_FILE_EDIT', true);
// Disable plugin and theme updates and installation from the admin
Config::define('DISALLOW_FILE_MODS', true);
// limit number of post revisions
Config::define('WP_POST_REVISIONS', 3);

/**
 * VVZ Settings
 */
Config::define('VVZ_API_BASE_URL', env('VVZ_API_BASE_URL'));
Config::define('VVZ_API_ACCESS_TOKEN', env('VVZ_API_ACCESS_TOKEN'));
Config::define('PROFILE_KEY', env('PROFILE_KEY'));

/**
 * Debugging Settings
 */
$isLocal = APPLICATION_ENV === 'apedev';
$isTest = in_array(APPLICATION_ENV, ['dev', 'stage']);
Config::define('SAVEQUERIES', $isLocal);
Config::define('WP_DEBUG', $isLocal || $isTest);
Config::define('WP_DEBUG_DISPLAY', $isLocal);
Config::define('WP_DISABLE_FATAL_ERROR_HANDLER', $isLocal);
Config::define('SCRIPT_DEBUG', $isLocal);

ini_set('display_errors', strval($isLocal || $isTest));
if ($isLocal) {
    error_reporting( E_ALL );
} elseif ($isTest) {
    error_reporting( E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_STRICT & ~E_DEPRECATED & ~E_USER_DEPRECATED );
} else {
    error_reporting( E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_STRICT );
}

/**
 * Allow WordPress to detect HTTPS when used behind a reverse proxy or a load balancer
 * See https://codex.wordpress.org/Function_Reference/is_ssl#Notes
 */
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

/**
 * Allow WordPress to get the Client IP when used behind a reverse proxy or a load balancer
 */
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
    $_SERVER['REMOTE_ADDR'] = $parts[0];
}

/**
 * Load aws specific settings
 */
$aws_config = __DIR__ . '/aws.php';

if (file_exists($aws_config)) {
    require_once $aws_config;
}

/**
 * Load salt specific settings
 */
// $salt_config = __DIR__ . '/salt.php';

// if (file_exists($salt_config)) {
//     require_once $salt_config;
// }

/**
 * Load environment specific settings
 */
$env_config = __DIR__ . '/environments/' . WP_ENV . '.php';

if (file_exists($env_config)) {
    require_once $env_config;
}

Config::apply();

/**
 * Bootstrap WordPress
 */
if (!defined('ABSPATH')) {
    define('ABSPATH', $webroot_dir . '/wp/');
}
// Some functions regarding plugins
include_once(ABSPATH . 'wp-admin/includes/plugin.php');

