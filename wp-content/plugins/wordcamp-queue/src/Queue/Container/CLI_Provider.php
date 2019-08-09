<?php


namespace WordCamp\Queue\Container;

use Pimple\Container;
use WordCamp\Queue\CLI\Cleanup;
use WordCamp\Queue\CLI\Process;

class CLI_Provider extends Provider {
	const PROCESS = 'process';
	const CLEANUP = 'cleanup';

	public function register( Container $container ) {
		$container[ self::PROCESS ] = function ( Container $container ) {
			return new Process( $container[ Queue_Provider::QUEUE ] );
		};
		$container[ self::CLEANUP ] = function ( Container $container ) {
			return new Cleanup( $container[ Queue_Provider::QUEUE ] );
		};

		add_action( 'init', function () use ( $container ) {
			if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
				return;
			}
			$container[ self::PROCESS ]->register();
			$container[ self::CLEANUP ]->register();
		} );
	}
}