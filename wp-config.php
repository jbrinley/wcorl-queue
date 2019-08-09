<?php
/**
 * Modern Tribe Skeleton configuration
 * Based on Mark Jaquith's Skeleton repository
 *
 * @link https://github.com/markjaquith/WordPress-Skeleton
 */

if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

function tribe_isSSL() {
	return ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' );
}

function tribe_getenv( $name, $default = null ) {
	$env = getenv( $name );
	if ( $env === false ) {
		return $default;
	}

	$env_str = strtolower( trim( $env ) );
	if ( $env_str === 'false' || $env_str === 'true' ) {
		return filter_var( $env_str, FILTER_VALIDATE_BOOLEAN );
	}

	if ( is_numeric( $env ) ) {
		return ( $env - 0 );
	}

	return $env;
}

if ( file_exists( __DIR__ . '/.env' ) ) {
	$dotenv = \Dotenv\Dotenv::create( __DIR__ );
	$dotenv->load();
}

// ==============================================================
// Assign default constant values
// ==============================================================

if ( ! isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) ) {
	$_SERVER['HTTP_X_FORWARDED_PROTO'] = '';
}

if ( $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS']       = 'on';
	$_SERVER['SERVER_PORT'] = 443;
}

$config_defaults = [

	// Paths
	'WP_CONTENT_DIR'          => __DIR__ . '/wp-content',
	'WP_CONTENT_URL'          => ( tribe_isSSL() ? 'https' : 'http' ) . '://' . $_SERVER['HTTP_HOST'] . '/wp-content',
	'ABSPATH'                 => __DIR__ . '/wp/',

	// DB settings
	'DB_CHARSET'              => 'utf8',
	'DB_COLLATE'              => '',
	'DB_NAME'                 => tribe_getenv( 'DB_NAME', '' ),
	'DB_USER'                 => tribe_getenv( 'DB_USER', '' ),
	'DB_PASSWORD'             => tribe_getenv( 'DB_PASSWORD', '' ),
	'DB_HOST'                 => tribe_getenv( 'DB_HOST', '' ),

	// Language
	'WPLANG'                  => tribe_getenv( 'WPLANG', '' ),

	// Security Hashes (grab from: https://api.wordpress.org/secret-key/1.1/salt)
	'AUTH_KEY'                => '%%AUTH_KEY%%',
	'SECURE_AUTH_KEY'         => '%%SECURE_AUTH_KEY%%',
	'LOGGED_IN_KEY'           => '%%LOGGED_IN_KEY%%',
	'NONCE_KEY'               => '%%NONCE_KEY%%',
	'AUTH_SALT'               => '%%AUTH_SALT%%',
	'SECURE_AUTH_SALT'        => '%%SECURE_AUTH_SALT%%',
	'LOGGED_IN_SALT'          => '%%LOGGED_IN_SALT%%',
	'NONCE_SALT'              => '%%NONCE_SALT%%',

	// Security Directives
	'DISALLOW_FILE_EDIT'      => true,
	'DISALLOW_FILE_MODS'      => true,
	'FORCE_SSL_LOGIN'         => false,
	'FORCE_SSL_ADMIN'         => false,

	// Performance
	'WP_CACHE'                => false,
	'DISABLE_WP_CRON'         => true,
	'WP_MEMORY_LIMIT'         => '96M',
	'WP_MAX_MEMORY_LIMIT'     => '256M',
	'EMPTY_TRASH_DAYS'        => 7,

	// Debug
	'WP_DEBUG'                       => tribe_getenv( 'WP_DEBUG', true ),
	'WP_DEBUG_LOG'                   => tribe_getenv( 'WP_DEBUG_LOG', true ),
	'WP_DEBUG_DISPLAY'               => tribe_getenv( 'WP_DEBUG_DISPLAY', true ),
	'SAVEQUERIES'                    => tribe_getenv( 'SAVEQUERIES', true ),
	'SCRIPT_DEBUG'                   => tribe_getenv( 'SCRIPT_DEBUG', false ),
	'CONCATENATE_SCRIPTS'            => tribe_getenv( 'CONCATENATE_SCRIPTS', false ),
	'COMPRESS_SCRIPTS'               => tribe_getenv( 'COMPRESS_SCRIPTS', false ),
	'COMPRESS_CSS'                   => tribe_getenv( 'COMPRESS_CSS', false ),
	'WP_DISABLE_FATAL_ERROR_HANDLER' => tribe_getenv( 'WP_DISABLE_FATAL_ERROR_HANDLER', true ),

	// Miscellaneous
	'WP_POST_REVISIONS'       => true,
	'WP_DEFAULT_THEME'        => tribe_getenv( 'WP_DEFAULT_THEME', 'twentysixteen' ),
];

// ==============================================================
// Use defaults array to define constants where applicable
// ==============================================================

foreach ( $config_defaults AS $config_default_key => $config_default_value ) {
	if ( ! defined( $config_default_key ) ) {
		define( $config_default_key, $config_default_value );
	}
}
// make sure our environment variables are also accessible as PHP constants
foreach ( $_ENV as $key => $value ) {
	if ( ! defined( $key ) ) {
		define( $key, tribe_getenv( $key ) );
	}
}

// ==============================================================
// Table prefix
// Change this if you have multiple installs in the same database
// ==============================================================

if ( empty( $table_prefix ) ) {
	$table_prefix = 'wp_';
}

// ==============================================================
// Bootstrap WordPress
// ==============================================================

require_once ABSPATH . 'wp-settings.php';
