<?php


namespace WordCamp\Queue\Schema;

class Meta_Table extends Table_Maker {
	const NAME = 'taskmeta';

	protected $schema_version = 1;

	protected function get_table_definition( $table ) {
		$table_name       = $this->wpdb->$table;
		$charset_collate  = $this->wpdb->get_charset_collate();
		return "CREATE TABLE {$table_name} (
		        meta_id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,
		        task_id BIGINT(20) unsigned NOT NULL,
		        meta_key VARCHAR(255) DEFAULT NULL,
		        meta_value VARCHAR(255) DEFAULT NULL,
		        PRIMARY KEY  (meta_id),
		        KEY task_id (task_id),
		        KEY meta (meta_key, meta_value)
		        ) $charset_collate";
	}


}
