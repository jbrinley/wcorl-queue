<?php

namespace WordCamp\Queue;

class Task {

	private $action;
	private $args;
	private $meta;
	private $priority;
	private $after;
	private $task_id;

	public function __construct( string $action, array $args = [], array $meta = [], int $priority = 10, int $after = 0, string $task_id = null ) {
		$this->action   = $action;
		$this->args     = $args;
		$this->meta     = $meta;
		$this->priority = $priority;
		$this->after    = $after;
		$this->task_id  = $task_id;
	}

	public function get_priority(): int {
		return $this->priority;
	}

	public function get_after(): int {
		return $this->after;
	}

	public function get_task_id(): string {
		return $this->task_id;
	}

	public function get_action(): string {
		return $this->action;
	}

	public function get_args(): array {
		return $this->args;
	}

	public function get_meta() {
		return $this->meta;
	}

}
