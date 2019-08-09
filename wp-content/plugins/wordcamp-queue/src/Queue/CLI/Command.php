<?php

namespace WordCamp\Queue\CLI;
use WP_CLI;

abstract class Command extends \WP_CLI_Command {

	public function register() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'queue ' . $this->command(), [ $this, 'run_command' ], [
			'shortdesc' => $this->description(),
			'synopsis'  => $this->arguments(),
		] );
	}

	/**
	 * @return string The name of the sub-command
	 */
	abstract protected function command(): string;

	/**
	 * @return string The command description
	 */
	abstract protected function description(): string;

	/**
	 * @return array Arguments to register the command
	 */
	abstract protected function arguments(): array;

	/**
	 * @param array $args
	 * @param array $assoc_args
	 *
	 * @return void
	 */
	abstract public function run_command( $args, $assoc_args ): void;

}