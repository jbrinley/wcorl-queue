<?php
/*
Plugin Name:  WordCamp Queue Test
Description:  A simple test file to enqueue tasks by making HTTP requests
Author:       Jonathan Brinley
Version:      1.0.0
Author URI:   https://xplus3.net/
Requires PHP: 7.1.0
License:      GPLv2 or later
*/

use WordCamp\Queue\Container\Queue_Provider;
use WordCamp\Queue\Queue;
use WordCamp\Queue\Task;

/**
 * Add a task to the queue by making a request
 * with the `enqueue` query param. Any additional
 * query params will be passed as args to the
 * queue task.
 */
add_action( 'template_redirect', function () {
	$action = filter_input( INPUT_GET, 'enqueue' );
	if ( $action ) {
		$args = $_GET;
		unset( $args['enqueue'] );

		/** @var Queue $queue */
		$queue = \WordCamp\Queue\Plugin::instance()->container()[ Queue_Provider::QUEUE ];

		$task = new Task( $action, $args, [ 'source' => 'template_redirect' ], 12, time() + 10 );

		$queue->dispatch( $task );
	}
} );

/**
 * Add a callback to handle queue tasks with the action "wordcamp/test"
 */
add_action( 'wordcamp/test', function( array $args ) {
	\WP_CLI::log( print_r( $args, true ) );
}, 10, 1 );
