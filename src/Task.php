<?php
namespace Cinderella;


class Task {
  public $type;
  public $options;

  public function __construct($type, $options = []) {
    $this->type = $type;
    $this->options = $options;
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
