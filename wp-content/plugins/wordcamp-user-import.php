<?php
/*
Plugin Name:  WordCamp User Import
Description:  Demonstrates how to use a queuing system to handle a large data import
Author:       Jonathan Brinley
Version:      1.0.0
Author URI:   https://xplus3.net/
Requires PHP: 7.1.0
License:      GPLv2 or later
*/


use League\Csv\Reader;
use League\Csv\Statement;
use Pimple\Container;
use WordCamp\Queue\Container\Queue_Provider;
use WordCamp\Queue\Plugin;
use WordCamp\Queue\Queue;
use WordCamp\Queue\Task;

/**
 * Create the admin page to select a file to import
 */
add_action( 'admin_menu', function () {
	add_users_page( __( 'User Import' ), __( 'Import' ), 'edit_users', 'user-import', function () {
		$title  = __( 'User Import' );
		$action = admin_url( 'options.php' );
		ob_start();
		printf( "<form action='%s' method='post'>", esc_url( $action ) );
		settings_fields( 'user-import' );
		do_settings_sections( 'user-import' );
		submit_button();
		echo "</form>";
		$content = ob_get_clean();
		printf( '<div class="wrap"><h2>%s</h2>%s</div>', $title, $content );
	} );

	add_settings_section( 'default', '', '__return_empty_string', 'user-import' );

	add_settings_field( 'user-import-csv', __( 'User Data File' ), function () {
		$attachment_id = (int) get_option( 'user-import-csv', 0 );
		if ( $attachment_id ) {
			echo __( 'Import in progress...' );
		}
		printf( '<p><input type="number" name="user-import-csv" value="%s" /></p>', $attachment_id ?: '' );
		printf( '<p class="description">%s</p>', __( 'Enter the ID of the attached CSV' ) );
	}, 'user-import' );

	register_setting( 'user-import', 'user-import-csv', [
		'type'              => 'integer',
		'sanitize_callback' => 'absint',
	] );
}, 10, 0 );


add_action( 'wordcamp/queue/init', function ( Plugin $plugin, Container $container ) {

	/**
	 * Add a task to the queue when a new CSV is selected for import
	 */
	$init_import = function ( $old_value, $new_value ) use ( $container ) {
		if ( empty( $new_value ) || (int) $old_value === (int) $new_value ) {
			return;
		}

		/** @var Queue $queue */
		$queue = $container[ Queue_Provider::QUEUE ];

		$task = new Task( 'wordcamp/user-import/init', [
			'attachment_id' => (int) $new_value,
		] );

		$queue->dispatch( $task );
	};
	add_action( 'add_option_user-import-csv', $init_import, 10, 2 );
	add_action( 'update_option_user-import-csv', $init_import, 10, 2 );

	/**
	 * Handle the task for a CSV import
	 */
	add_action( 'wordcamp/user-import/init', function ( $args ) use ( $container ) {
		$attachment_id = $args['attachment_id'] ?? 0;
		if ( empty( $attachment_id ) ) {
			// do something to log the error
			return; // nothing else we can do. We don't want to retry with the same data.
		}

		/** @var Queue $queue */
		$queue = $container[ Queue_Provider::QUEUE ];

		$path = get_attached_file( $attachment_id );

		// This may throw an exception if there's an I/O problem, causing the task to re-try
		$csv = Reader::createFromPath( $path, 'r' );
		$csv->setHeaderOffset( 0 );

		$count = $csv->count();

		$batch_size = 100;

		$batches = ceil( $count / $batch_size );
		for ( $i = 0; $i < $batches; $i ++ ) {

			add_post_meta( $attachment_id, 'batch', $i, false );
			$task = new Task( 'wordcamp/user-import/batch', [
				'attachment_id' => $attachment_id,
				'batch'         => $i,
				'size'          => $batch_size,
			] );
			$queue->dispatch( $task );
		}
	}, 10, 1 );


	/**
	 * Handle a batch from the CSV
	 */
	add_action( 'wordcamp/user-import/batch', function ( $args ) use ( $container ) {
		$attachment_id = $args['attachment_id'] ?? 0;
		$batch         = $args['batch'] ?? 0;
		$size          = $args['size'] ?? 0;
		if ( empty( $attachment_id ) || empty( $batch ) || empty( $size ) ) {
			// do something to log the error
			return; // nothing else we can do. We don't want to retry with the same data.
		}

		$path = get_attached_file( $attachment_id );

		// This may throw an exception if there's an I/O problem, causing the task to re-try
		$csv = Reader::createFromPath( $path, 'r' );
		$csv->setHeaderOffset( 0 );

		$offset = $batch * $size;

		$statement = ( new Statement() )->offset( $offset )->limit( $size );

		$records = $statement->process( $csv )->getRecords();

		// remove tracking for this batch
		delete_post_meta( $attachment_id, 'batch', $batch );
		foreach ( $records as $user ) {
			// import the user, ignore the error if the user already exists
			wp_insert_user( [
				'user_login' => $user['username'],
				'user_email' => $user['email'],
				'user_pass'  => wp_generate_password(),
				'first_name' => $user['first'],
				'last_name'  => $user['last'],
			] );
		}
	}, 10, 1 );
}, 10, 2 );
