<?php

namespace Cinderella\Task;

use Amp\Artax\DefaultClient;
use Amp\Artax\Request;
use Cinderella\Cinderella;

class TaskRunner extends Task {

  public function processArguments($arguments) {
    if (!isset($this->options['tasks'])) {
      $this->options['tasks'] = $arguments['tasks'];
    }
    if (!isset($this->options['resolve'])) {
        $this->options['resolve'] = $arguments['resolve'];
    }
  }

  public function run() {
    $start = microtime(TRUE);
    $id = $this->getId();
    $promises = [];
    $messages = [];
    $logger = $this->cinderella->getLogger();

    foreach ($this->options['tasks'] as $taskdata) {
      $task = Task::Factory($taskdata, $this->cinderella);
      $result = $task->run();
      if ($promise = $result->getPromise()) {
        $promises[] = $promise;
      }
      if ($message = $result->getMessage()) {
        $messages[] = $message;
      }
    }

    $resolveTask = Task::Factory($this->options['resolve'], $this->cinderella);

    $promise = \Amp\Promise\all($promises);
    $promise->onResolve(
      function($error = null, $result = null) use ($resolveTask, $id, $start, $logger) {
        $time = microtime(TRUE) - $start;
        if ($error) {
          $logger->error("Task $id ($time seconds): an error occured:" . $error->getMessage());
        } else {
          $resolveTask->run();
          $logger->info("Task $id ($time seconds): Successful - " . $result);
        }
      }
    );

    $message = "Task $id: Scheduling set of tasks";
    return new TaskResult($message, $promise);
  }
}