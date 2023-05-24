<?php

/**
 * Plugin Name: WordPress Sentry
 * Plugin URI: https://github.com/stayallive/wp-sentry
 * Description: A (unofficial) WordPress plugin to report PHP and JavaScript errors to Sentry.
 * Version: must-use-proxy
 * Author: Alex Bouma
 * Author URI: https://alex.bouma.dev
 * License: MIT
 */
define( 'WP_SENTRY_PHP_DSN', 'https://069a4e92cf33428389aac29e8d5be5bd@sentry.vonaffenfels.de/26' );

$wp_sentry = __DIR__ . '/../plugins/wp-sentry/wp-sentry.php';

if ( ! file_exists( $wp_sentry ) ) {
    return;
}

require $wp_sentry;

define( 'WP_SENTRY_MU_LOADED', true );
