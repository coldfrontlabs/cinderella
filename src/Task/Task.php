<?php

namespace Cinderella\Task;

use Cinderella\Cinderella;

class Task {
  protected $options;
  protected $queue;
  protected $type;
  protected $cinderella;

  public function __construct($type, $options = [], Cinderella $cinderella) {
    $this->options = $options + $this->defaults();
    $this->queue = [];
    $this->type = $type;
    $this->cinderella = $cinderella;
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
    return new TaskResult($message, NULL);
  }

  public static function Factory($task, Cinderella $cinderella) {
    switch ($task['type']) {
      case TaskType::ScheduleRefresh:
        return new ScheduleRefresh(TaskType::ScheduleRefresh, $task, $cinderella);

      case TaskType::HttpRequest:
        return new HttpRequest(TaskType::ScheduleRefresh, $task, $cinderella);

      case TaskType::Status:
        return new Status(TaskType::Status, $task, $cinderella);

      case TaskType::None:
      default:
        return new Task(TaskType::None, $task, $cinderella);
    }
  }
}
