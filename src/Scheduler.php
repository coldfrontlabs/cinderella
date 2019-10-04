<?php

namespace Cinderella;

use Amp\CallableMaker;
use Cinderella\Task;

class Scheduler {
  use CallableMaker;

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
    $this->tick();
  }

  public function tick() {
    $now = microtime(1);

    foreach ($this->schedule as $time => $tasks) {
      if ($now >= $nextTime) {
        $this->runTasks($nextTime);
      }
    }

    ksort($this->schedule);
    $times = array_keys($this->schedule);
    $nextRunIn = (float)($times[0] - time()) / 2;
    Loop::delay($nextRunIn * 1000, $this->callableFromInstanceMethod('tick'));
  }

  public function runTasks($time) {
    foreach ($this->schedule[$time] as $key => $task) {
      $task->run();
      unset($this->schedule[$time][$key]);
    }
    if (empty($this->schedule[$time])) {
      unset($this->schedule[$time]);
    }
  }

}
