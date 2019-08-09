<?php

namespace WordCamp\Queue\Backend;

use WordCamp\Queue\Exception\Task_Not_Found_Exception;
use WordCamp\Queue\Exception\Unable_To_Claim_Task_Exception;
use WordCamp\Queue\Exception\Unable_To_Enqueue_Task_Exception;
use WordCamp\Queue\Task;
use WordCamp\Queue\Queue;

class MySQL implements Queue {

	/**
	 * @var \wpdb An instance of the WordPress database
	 */
	private $wpdb;

	/**
	 * @var string The name of the database table to store the queue
	 */
	private $queue_table;

	/**
	 * @var string THe name of the database table to store task meta
	 */
	private $meta_table;

	/**
	 * @var int seconds before a dequeued item is nack'ed or deleted
	 */
	private $ttl;

	public function __construct( \wpdb $wpdb, string $queue_table, string $meta_table, int $ttl = 300 ) {
		$this->wpdb        = $wpdb;
		$this->queue_table = $this->wpdb->$queue_table;
		$this->meta_table  = $this->wpdb->$meta_table;
		$this->ttl         = $ttl;
	}

	public function dispatch( Task $task ): int {
		$data = [
			'action'       => $task->get_action(),
			'args'         => json_encode( $task->get_args() ),
			'priority'     => $task->get_priority(),
			'run_after'    => $task->get_after(),
			'date_created' => time(),
		];

		$inserted = $this->wpdb->insert( $this->queue_table, $data, [ '%s', '%s', '%d', '%d', '%d' ] );

		if ( ! $inserted ) {
			throw new Unable_To_Enqueue_Task_Exception( 'Error enqueuing message' );
		}

		$task_id = $this->wpdb->insert_id;

		foreach ( $task->get_meta() as $key => $value ) {
			$this->wpdb->insert( $this->meta_table, [
				'task_id'    => $task_id,
				'meta_key'   => $key,
				'meta_value' => $value,
			], [ '%d', '%s', '%s' ] );
		}

		return $task_id;
	}

	public function reserve( array $args = [] ): Task {
		$join = '';

		$where = 'WHERE q.date_claimed = 0
			AND q.date_completed = 0
			AND q.run_after <= UNIX_TIMESTAMP()
			AND q.attempts < q.max_attempts';

		$order = 'ORDER BY q.attempts ASC, q.priority ASC';

		$limit = 'LIMIT 0,1';

		if ( $args ) {
			$i = 0;
			foreach ( $args as $key => $value ) {
				$i ++;
				$alias = sprintf( 'm%d', $i );
				$join  .= $this->wpdb->prepare( " INNER JOIN $this->meta_table $alias ON q.task_id = $alias.task_id AND $alias.meta_key=%s", $key );
				$where .= $this->wpdb->prepare( " AND $alias.meta_value=%s", $value );
			}
		}

		$task = $this->wpdb->get_row( "SELECT q.* FROM $this->queue_table q $join $where $order $limit", ARRAY_A );

		if ( empty( $task ) ) {
			throw new Task_Not_Found_Exception( 'Empty queue' );
		}

		$task['args'] = json_decode( $task['args'], 1 );

		$this->wpdb->update(
			$this->queue_table,
			[
				'date_claimed' => time(),
				'attempts'     => $task['attempts'] + 1,
			],
			[
				'task_id'        => $task['task_id'],
				'date_claimed'   => 0, // ensures that another process didn't claim the task
				'date_completed' => 0,
			]
		);

		if ( 0 === $this->wpdb->rows_affected ) {
			throw new Unable_To_Claim_Task_Exception( 'Lost queue lock' );
		}

		$meta         = $this->wpdb->get_results( $this->wpdb->prepare( "SELECT meta_key, meta_value FROM $this->meta_table WHERE task_id=%d", $task['task_id'] ), ARRAY_A );
		$task['meta'] = wp_list_pluck( $meta, 'meta_value', 'meta_key' );

		return new Task( $task['action'], $task['args'], $task['meta'], $task['priority'], $task['run_after'], $task['task_id'] );
	}

	public function ack( string $task_id ): void {
		$this->wpdb->update(
			$this->queue_table,
			[ 'date_completed' => time() ],
			[ 'task_id' => $task_id ]
		);
	}

	public function nack( string $task_id ): void {
		$this->wpdb->update(
			$this->queue_table,
			[ 'date_claimed' => 0 ], // release our claim on the task
			[ 'id' => $task_id ]
		);
	}

	public function cleanup(): void {
		$records_to_delete = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT task_id FROM {$this->queue_table} WHERE date_completed != 0 AND date_completed < %d", time() - $this->ttl ) );
		if ( $records_to_delete ) {
			$task_id_list = implode( ',', array_map( 'intval', $records_to_delete ) );
			$this->wpdb->query( "DELETE FROM {$this->meta_table} WHERE task_id IN ($task_id_list)" );
			$this->wpdb->query( "DELETE FROM {$this->queue_table} WHERE task_id IN ($task_id_list)" );
		}

		$stale_records = $this->wpdb->get_col( $this->wpdb->prepare( "SELECT task_id FROM {$this->queue_table} WHERE date_claimed != 0 AND date_claimed < %d AND date_completed = 0", time() - $this->ttl ) );
		if ( $stale_records ) {
			$task_id_list = implode( ',', array_map( 'intval', $stale_records ) );
			$this->wpdb->query( "UPDATE {$this->queue_table} SET date_claimed=0 WHERE task_id IN ($task_id_list)" );
		}
	}
}
