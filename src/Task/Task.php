<?php

namespace Cinderella\Task;

use Cinderella\Cinderella;

class Task {
  protected $id;
  protected $options;
  protected $queue;
  protected $type;
  protected $cinderella;

  public function __construct($type, $options = [], Cinderella $cinderella, $arguments = FALSE) {
    $this->id = uniqid();
    $this->options = $options + $this->defaults();
    $this->queue = [];
    $this->type = $type;
    $this->cinderella = $cinderella;
    $this->processArguments($arguments);
  }

  public function processArguments($arguments) {
    return;
  }

  public function getId() {
    return $this->id . '-' . $this->type;
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

  public static function Factory($task, Cinderella $cinderella, $arguments = FALSE) {
    switch ($task['type']) {
      case TaskType::TaskRunner:
        return new TaskRunner(TaskType::TaskRunner, $task, $cinderella, $arguments);

      case TaskType::ScheduleRefresh:
        return new ScheduleRefresh(TaskType::ScheduleRefresh, $task, $cinderella, $arguments);

      case TaskType::HttpRequest:
        return new HttpRequest(TaskType::HttpRequest, $task, $cinderella, $arguments);

      case TaskType::Status:
        return new Status(TaskType::Status, $task, $cinderella, $arguments);

      case TaskType::None:
      default:
        return new Task(TaskType::None, $task, $cinderella, $arguments);
    }
  }
}
