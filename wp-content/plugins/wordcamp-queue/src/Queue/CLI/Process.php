<?php


namespace WordCamp\Queue\CLI;

use WordCamp\Queue\Exception\Task_Not_Found_Exception;
use WordCamp\Queue\Exception\Unable_To_Claim_Task_Exception;
use WordCamp\Queue\Queue;

class Process extends Command {


	/**
	 * @var Queue
	 */
	protected $queue;

	/**
	 * @var int Number of seconds the process should run before gracefully exiting
	 */
	private $timelimit;

	public function __construct( Queue $queue, $timelimit = 300 ) {
		$this->queue     = $queue;
		$this->timelimit = $timelimit;

		parent::__construct();
	}

	public function command(): string {
		return 'process';
	}

	public function description(): string {
		return __( 'Process the queue' );
	}

	public function arguments(): array {
		return [
			[
				'type'        => 'generic',
				'optional'    => true,
				'description' => __( 'Arguments to filter the tasks that will be run' ),
			],
		];
	}

	public function run_command( $args, $assoc_args ): void {
		$end_time = time() + $this->timelimit;
		while ( time() < $end_time ) {
			try {
				$task = $this->queue->reserve( $assoc_args );
			} catch ( Unable_To_Claim_Task_Exception $e ) {
				\WP_CLI::debug( __( 'Unable to claim task' ) );
				sleep( 1 );
				continue;
			} catch ( Task_Not_Found_Exception $e ) {
				\WP_CLI::debug( __( 'No tasks found' ) );
				sleep( 1 );
				continue;
			}

			$action = $task->get_action();
			$args   = $task->get_args();

			try {
				\WP_CLI::debug( sprintf( __( '%d: Running action %s' ), $task->get_task_id(), $action ) );
				do_action( $action, $args );

				// mark the task complete
				$this->queue->ack( $task->get_task_id() );
			} catch ( \Exception $e ) {
				\WP_CLI::warn( sprintf( __( '%d: Error running action %s. Message: %s' ), $task->get_task_id(), $action, $e->getMessage() ) );
				// put it back in the queue to try again later
				$this->queue->nack( $task->get_task_id() );
			}
		}
	}
}