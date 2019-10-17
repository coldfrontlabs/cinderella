<?php

namespace Cinderella\Task;

class Status extends Task {
  public function run() {
    $status = $this->cinderella->getStatus();
    return new TaskResult("Status: " . print_r($status, TRUE), NULL);
  }
}