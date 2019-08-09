<?php


namespace WordCamp\Queue\Container;

use Pimple\Container;
use WordCamp\Queue\Schema\Meta_Table;
use WordCamp\Queue\Schema\Queue_Table;

class Schema_Provider extends Provider {
	const QUEUE_TABLE = 'schema.table.task_queue';
	const META_TABLE  = 'schema.table.taskmeta';

	public function register( Container $container ) {
		$container[ self::QUEUE_TABLE ] = function ( Container $container ) {
			return new Queue_Table( $GLOBALS['wpdb'] );
		};

		$container[ self::META_TABLE ] = function ( Container $container ) {
			return new Meta_Table( $GLOBALS['wpdb'] );
		};

		add_action( 'plugins_loaded', function () use ( $container ) {
			$container[ self::QUEUE_TABLE ]->register_table();
			$container[ self::META_TABLE ]->register_table();
		}, 10, 0 );
	}

}
