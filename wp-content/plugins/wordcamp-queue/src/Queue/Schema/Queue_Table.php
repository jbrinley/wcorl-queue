<?php


namespace WordCamp\Queue\Schema;

class Queue_Table extends Table_Maker {
	const NAME = 'task_queue';

	protected $schema_version = 1;

	protected function get_table_definition( $table ) {
		$table_name       = $this->wpdb->$table;
		$charset_collate  = $this->wpdb->get_charset_collate();
		return "CREATE TABLE {$table_name} (
		        task_id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
		        action VARCHAR(255) NOT NULL,
		        args TEXT NOT NULL,
		        priority int(10) NOT NULL DEFAULT 0,
		        run_after int(10) NOT NULL DEFAULT 0,
		        attempts int(10) NOT NULL DEFAULT 0,
		        max_attempts int(10) NOT NULL DEFAULT 100,
		        date_created int(10) NOT NULL DEFAULT 0,
		        date_claimed int(10) NOT NULL DEFAULT 0,
		        date_completed int(10) NOT NULL DEFAULT 0,
		        PRIMARY KEY  (task_id),
		        KEY task_dates (date_claimed, date_completed, run_after),
		        KEY attempts (attempts, priority),
		        KEY priority (priority, attempts),
		        KEY action (action)
		        ) $charset_collate";
	}


}
