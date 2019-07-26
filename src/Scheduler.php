<?php

namespace Cinderella;

use Cinderella\Task;

class Scheduler {

  protected $schedule;

  public function __construct() {
    $this->schedule = [];
  }

  public function scheduleTask($time, Task $task) {
    $now = microtime(1);
    if ($time < $now) {
      throw new Exception('Time given is already past');
    }

    if (!isset($this->schedule[$time])) {
      $this->schedule[$time] = [];
    }

    $this->schedule[$time][] = $task;

    ksort($this->schedule);
  }

  public function tick() {
    $now = microtime(1);
    //foreach ($this->schedule)
  }

}
