<?php
/*
Plugin Name:  WordCamp Queue
Description:  Build a queue to handle background processing of long-running tasks
Author:       Jonathan Brinley
Version:      1.0.0
Author URI:   https://xplus3.net/
Requires PHP: 7.1.0
License:      GPLv2 or later
*/

// Start the plugin
add_action( 'plugins_loaded', 'wordcamp_queue_init', 1, 0 );

/**
 * @return \WordCamp\Queue\Plugin
 */
function wordcamp_queue_init() {
	$container = new \Pimple\Container( [ 'plugin_file' => __FILE__ ] );
	$plugin    = \WordCamp\Queue\Plugin::instance( $container );
	$plugin->init();

	/**
	 * Fires after the plugin has initialized
	 *
	 * @param \WordCamp\Queue\Plugin $plugin    The global instance of the plugin controller
	 * @param \Pimple\Container   $container The plugin's dependency injection container
	 */
	do_action( 'wordcamp/queue/init', $plugin, $container );

	return $plugin;
}

