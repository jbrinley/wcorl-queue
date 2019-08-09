<?php

namespace WordCamp\Queue;

use WordCamp\Queue\Exception\Task_Not_Found_Exception;
use WordCamp\Queue\Exception\Unable_To_Claim_Task_Exception;
use WordCamp\Queue\Exception\Unable_To_Enqueue_Task_Exception;

interface Queue {

	/**
	 * Add a message to the queue
	 *
	 * @param Task $task
	 *
	 * @return int The ID of the enqueued task
	 * @throws Unable_To_Enqueue_Task_Exception
	 */
	public function dispatch( Task $task ): int;

	/**
	 * @param array $args Criteria for the retrieved task
	 *
	 * @return Task
	 * @throws Task_Not_Found_Exception if there is no processable task in the queue
	 * @throws Unable_To_Claim_Task_Exception if a reservation was attempted but failed
	 */
	public function reserve( array $args = [] ): Task;

	/**
	 * @param string $task_id
	 *
	 * @return void
	 *
	 * Acknowledgement processing of the Message. This results in the task being removed from the queue.
	 */
	public function ack( string $task_id ): void;

	/**
	 * @param string $task_id
	 *
	 * @return void
	 *
	 * Negative Acknowledgement processing of the Message. This results in the task being returned to the queue.
	 */
	public function nack( string $task_id ): void;

	/**
	 * Release frozen tasks, remove old records
	 *
	 * @return void
	 */
	public function cleanup(): void;
}
