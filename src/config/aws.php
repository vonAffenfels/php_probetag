<?php
/**
 * AWS specific overwrites
 */
use Roots\WPConfig\Config;

/*
 * Use iam profiles
 */
if(getenv('AWS_ACCESS_KEY') && getenv('AWS_SECRET_KEY')) {
    $s3_settings = array(
        'provider' => 'aws',
        'access-key-id' => getenv('AWS_ACCESS_KEY'),
        'secret-access-key' => getenv('AWS_SECRET_KEY'),
        'use-server-roles' => false
    );
} else {
    define('AWS_USE_EC2_IAM_ROLE', true);
    $s3_settings = array(
        'provider' => 'aws',
        'use-server-roles' => true
    );
}

/*
 * S3 HTTPS handling
 */
$s3_force_https = true;
if(getenv('USE_MINIO')) {
    $s3_force_https = false;
}

/*
 * Force HTTP Host
 */
if(getenv('WP_FORCE_HOST')) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['HTTP_HOST'] = getenv('WP_FORCE_HOST');
    $_SERVER['SERVER_NAME']  = getenv('WP_FORCE_HOST');
}

/*
 * Support for wp cli and others
 */
if(!isset($_SERVER['HTTP_HOST'])) {
    if(getenv('WP_DEFAULT_HOST')) {
        $_SERVER['HTTP_HOST'] = getenv('WP_DEFAULT_HOST');
        $_SERVER['SERVER_NAME']  = getenv('WP_DEFAULT_HOST');
    } elseif (defined( 'WP_CLI' )  && WP_CLI) {
        $_SERVER['HTTP_HOST'] = 'wp-cli.org';
        $_SERVER['SERVER_NAME'] = 'wp-cli.org';
    }
}

/*
 * Handle multi domain into single instance of wordpress installation
 */
$proto = 'http';
if ((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) || (isset($_SERVER['HTTPS']) && $_SERVER['SERVER_PORT'] == 443)) {
    $proto = 'https';
    $_SERVER['HTTPS'] = true;
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') {
    $proto = 'https';
    $_SERVER['HTTPS'] = true;
} else {
    $_SERVER['HTTPS'] = false;
}

Config::define('WP_HOME', $proto . '://' . $_SERVER['HTTP_HOST']);
Config::define('WP_SITEURL', $proto . '://' . $_SERVER['HTTP_HOST'] . '/wp');

/*
 * WP Offload setting
 */
Config::define( 'AS3CF_SETTINGS', serialize( array_merge($s3_settings, array(
    // S3 bucket to upload files
    'bucket' => getenv('WP_OFFLOAD_BUCKET'),
    // S3 bucket region (e.g. 'us-west-1' - leave blank for default region)
    'region' => getenv('WP_OFFLOAD_REGION'),
    // Automatically copy files to S3 on upload
    'copy-to-s3' => true,
    // Rewrite file URLs to S3
    'serve-from-s3' => true,
    // Delivery Provider ('storage', 'aws', 'do', 'gcp', 'cloudflare', 'keycdn', 'stackpath', 'other')
    'delivery-provider' => 'aws',
    // Use a custom domain (CNAME), not supported when using 'storage' Delivery Provider
    'enable-delivery-domain' => true,
    // Custom domain (CNAME), not supported when using 'storage' Delivery Provider
    'delivery-domain' => getenv('WP_OFFLOAD_CLOUDFRONT'),
    // Enable object prefix, useful if you use your bucket for other files
    'enable-object-prefix' => true,
    // Object prefix to use if 'enable-object-prefix' is 'true'
    'object-prefix' => 'wp-content/uploads/',
    // Organize S3 files into YYYY/MM directories
    'use-yearmonth-folders' => true,
    // Serve files over HTTPS
    'force-https' => $s3_force_https,
    // Remove the local file version once offloaded to S3
    'remove-local-file' => true,
    // Append a timestamped folder to path of files offloaded to S3
    'object-versioning' => false,
) ) ) );

/**
 * Disable WP CRON
 */
Config::define('DISABLE_WP_CRON', true);
