<?php


namespace WordCamp\Queue\Schema;

/**
 * Class Table_Maker
 *
 * Utility class for creating/updating custom tables
 */
abstract class Table_Maker extends Schema {
	const NAME = '';

	/** @var \wpdb */
	protected $wpdb;

	public function __construct( \wpdb $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Register tables with WordPress, and create them if needed
	 *
	 * @return void
	 *
	 * @action init
	 */
	public function register_table() {
		// make WP aware of our table
		$this->wpdb->tables[]       = static::NAME;
		$this->wpdb->{static::NAME} = $this->get_full_table_name( static::NAME );

		// create the tables
		if ( $this->schema_update_required() ) {
			$this->update_table( static::NAME );
			$this->mark_schema_update_complete();
		}
	}

	/**
	 * @param string $table The name of the table
	 *
	 * @return string The CREATE TABLE statement, suitable for passing to dbDelta
	 */
	abstract protected function get_table_definition( $table );


	/**
	 * Update the schema for the given table
	 *
	 * @param string $table The name of the table to update
	 *
	 * @return void
	 */
	private function update_table( $table ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		$definition = $this->get_table_definition( $table );
		if ( $definition ) {
			$updated = dbDelta( $definition );
			foreach ( $updated as $updated_table => $update_description ) {
				if ( strpos( $update_description, 'Created table' ) === 0 ) {
					do_action( 'wordcamp/queue/table_maker/created_table', $updated_table, $table );
				}
			}
		}
	}

	/**
	 * @param string $table
	 *
	 * @return string The full name of the table, including the
	 *                table prefix for the current blog
	 */
	protected function get_full_table_name( $table ) {
		return $GLOBALS['wpdb']->prefix . $table;
	}
}
