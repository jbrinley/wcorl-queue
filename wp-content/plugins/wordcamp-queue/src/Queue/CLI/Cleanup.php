<?php

namespace WordCamp\Queue\CLI;

use WordCamp\Queue\Queue;

class Cleanup extends Command {


	/** @var Queue */
	private $queue;

	public function __construct( Queue $queue ) {
		$this->queue = $queue;
		parent::__construct();
	}

	public function command(): string {
		return 'cleanup';
	}

	public function arguments(): array {
		return [];
	}

	public function description(): string {
		return __( 'Runs the cleanup command on the queue.' );
	}

	public function run_command( $args, $assoc_args ): void {
		$this->queue->cleanup();
	}

}