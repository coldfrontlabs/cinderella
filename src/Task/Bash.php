<?php

use Amp\Process\Process;
use Cinderella\TaskResult;

namespace Cinderella\Task;

class Bash {
  protected function run() {
    $process = new Process($this->options['parameters']['cmd']);
    $promise = $process->start();
    $message = $path . ' - Executing ' . $this->options['parameters']['cmd'];
    return new TaskResult($message, $promise);
  }
}
