<?php


namespace WordCamp\Queue\Container;

use Pimple\Container;
use WordCamp\Queue\Backend\MySQL;
use WordCamp\Queue\Schema\Meta_Table;
use WordCamp\Queue\Schema\Queue_Table;

class Queue_Provider extends Provider {
	const QUEUE = 'queue';

	public function register( Container $container ) {
		$container[ self::QUEUE ] = function( Container $container ) {
			/** @var \wpdb $wpdb */
			global $wpdb;
			return new MySQL( $wpdb, Queue_Table::NAME, Meta_Table::NAME );
		};
	}
}
