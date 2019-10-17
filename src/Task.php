<?php

namespace Cinderella;

class Task {
  protected $options;
  protected $queue;
  protected $type;

  public function __construct($options = []) {
    $this->options = $options + $this->defaults();
    $this->queue = [];
    $this->type = TaskType::None;
  }

  public function queue() {
    $this->cleanup();
    if ($this->options['concurrency'] > 0 and sizeof($queue)) {}
  }

  protected function defaults() {
    return [
      'concurrency' => 0,
      'queue' => false,
    ];
  }

  protected function cleanup() {
    // Do nothing in base task.
  }

  public function run() {
    $message = "Running {$this->type}\n";
    print $message;
    return [$message, NULL];
  }

  public static function Factory($task) {
    switch ($task['type']) {
      case TaskType::None:
      default:
        return new Task(TaskType::None, $task);
    }
  }
}
