<?php

namespace Cinderella;

use Amp\CallableMaker;
use Amp\Loop;
use Cinderella\Task\Task;
use Cinderella\Task\TaskType;

class Scheduler {
  use CallableMaker;

  protected $schedule;
  protected $scheduledTasksIds;
  protected $remoteSchedules;
  protected $logger;
  protected $cinderella;

  /**
   * Scheduler module's contructor.
   */
  public function __construct($logger, $cinderella) {
    $this->schedule = [];
    $this->remoteSchedules = [];
    $this->scheduledTasksIds = [];
    $this->logger = $logger;
    $this->cinderella = $cinderella;
  }

  /**
   * Register a remote schedule.
   */
  public function register($name, $schedule) {
    $this->remoteSchedules[$name] = $schedule;
    $this->remoteSchedules[$name]['last_updated'] = 0;
    $this->refresh($name);
  }

  /**
   * Load remote schedule.
   */
  public function refresh($name = null) {
    if (!isset($name)) {
      foreach (array_keys($this->remoteSchedules) as $name) {
        $this->refresh($name);
      }
      return;
    }
    $this->logger->debug("Scheduler: refreshing $name");
    $tasks = file_get_contents($this->remoteSchedules[$name]['url']);
    if (!$tasks) {
      return;
    }
    $tasks = json_decode($tasks, TRUE);
    $this->remoteSchedules[$name]['last_updated'] = microtime(1);

    foreach ($tasks as $task) {
      $id = $name . ':' . $task['id'];
      //$tasktime = $task['time'];
      $tasktime = time() + rand(10,45);
      $task = Task::Factory($task['task'], $this->cinderella);

      $this->scheduleTask($id, $tasktime, $task);
    }

  }

  /**
   * Schedule a single task.
   */
  public function scheduleTask($id, $time, Task $task) {
    $this->logger->debug("Scheduler: scheduling $id at $time");

    // Don't load duplicate tasks.
    if (isset($this->scheduledTaskIds[$id])) {
      $this->logger->warning("Scheduler: rejected scheduling tasks $id at $time as it is already scheduled (or recently run)");
      return;
    }

    $this->scheduledTaskIds[$id] = $id;

    if (!isset($this->schedule[$time])) {
      $this->schedule[$time] = [];
    }

    $this->schedule[$time][] = $task;

    ksort($this->schedule);
    $this->tick(); // This can't go here, it schedules too much.
  }

  /**
   * Check whether any tasks should be run and set a recheck time.
   */
  public function tick($watcherId = NULL) {
    static $nextCheckIn = FALSE;
    static $nextCheckInId = NULL;

    if ($watcherId == $nextCheckInId) {
      $nextCheckInId = NULL;
    }

    $now = microtime(1);
    $this->logger->debug("Scheduler: checking schedule at $now");

    foreach (array_keys($this->schedule) as $time) {
      if ($now >= $time) {
        $this->logger->debug("Scheduler: Running tasks scheduled for $time at $now");
        $this->runTasks($time);
        $nextCheckIn = FALSE;
        $nextCheckInId = NULL;
      }
    }

    ksort($this->schedule);
    $times = array_keys($this->schedule);
    if (empty($times)) {
      $nextCheckIn = FALSE;
      $nextCheckInId = NULL;
      $this->logger->debug("Scheduler: queue empty, no check-ins scheduled");
      return;
    }
    $nextRunIn = (float)($times[0] - time()) / 2;
    if ($nextRunIn < 0.5) {
      $nextRunIn /= 100;
    } elseif ($nextRunIn < 2) {
      $nextRunIn /= 10;
    } elseif ($nextRunIn < 5) {
      $nextRunIn /= 4;
    }

    if (!$nextCheckInId or !$nextCheckIn or $nextCheckIn < $nextRunIn - $now) {
      $this->logger->debug("Scheduler: Next scheduled event is at $times[0] - checking back in $nextRunIn seconds");
      $nextCheckIn = (int)$now + $nextRunIn;
      if ($nextCheckInId) {
        Loop::cancel($nextCheckInId);
      }
      $nextCheckInId = Loop::delay($nextRunIn * 1000, $this->callableFromInstanceMethod('tick'));
    } else {
      $this->logger->debug("Scheduler: Wanted to schedule a check in in $nextRunIn seconds, but the next it scheduled for " . (string)($nextCheckIn - $now));
    }
  }

  /**
   * Run the specific tasks at a specified time.
   */
  public function runTasks($time) {
    foreach ($this->schedule[$time] as $key => $task) {
      $this->cinderella->run($task,':scheduler:');
      unset($this->schedule[$time][$key]);
    }
    if (empty($this->schedule[$time])) {
      unset($this->schedule[$time]);
    }
  }

}
