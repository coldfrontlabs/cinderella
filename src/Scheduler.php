<?php

namespace Cinderella;

use Amp\CallableMaker;
use Amp\Loop;
use Cinderella\Task\Task;
use Cinderella\Task\TaskType;

class Scheduler
{
    use CallableMaker;

    protected $schedule;
    protected $scheduledTasksIds;
    protected $remoteSchedules;
    protected $logger;
    protected $cinderella;

  /**
   * Scheduler module's contructor.
   */
    public function __construct($logger, $cinderella)
    {
        $this->schedule = [];
        $this->remoteSchedules = [];
        $this->scheduledTasksIds = [];
        $this->logger = $logger;
        $this->cinderella = $cinderella;
    }

    public function getStatus()
    {
        $status = [];
        foreach ($this->schedule as $time => $tasks) {
            foreach ($tasks as $task) {
                $status[$time][] = $task->getId();
            }
        }
        return $status;
    }

  /**
   * Register a remote schedule.
   */
    public function register($name, $schedule)
    {
        $this->remoteSchedules[$name] = $schedule;
        $this->remoteSchedules[$name]['last_updated'] = 0;
        $this->refresh($name);
    }

  /**
   * Load remote schedule.
   */
    public function refresh($name = null)
    {
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
        $tasks = json_decode($tasks, true);
        $this->remoteSchedules[$name]['last_updated'] = microtime(1);

        foreach ($tasks as $taskdata) {
            $taskdata['time'] = time() + rand(1, 10);
            $taskdata['id'] = uniqid();
            $task = Task::factory($taskdata['task'], $this->cinderella);
            $this->scheduleTask($taskdata['id'], $taskdata['time'], $task);
        }
    }

  /**
   * Schedule a single task.
   */
    public function scheduleTask($id, $time, Task $task)
    {
        $diff = $time - time();
        $this->logger->info("Scheduler: scheduling $id at $time (in $diff seconds");

      // Don't load duplicate tasks.
        if (isset($this->scheduledTaskIds[$id])) {
            $this->logger->warning(
                "Scheduler: rejected scheduling tasks $id at $time - it is already scheduled or recently run"
            );
            return;
        }

        $this->scheduledTaskIds[$id] = $id;

        if (!isset($this->schedule[$time])) {
            $this->schedule[$time] = [];
        }

        $this->schedule[$time][] = $task;

        ksort($this->schedule);
        $this->tick();
    }

  /**
   * Check whether any tasks should be run and set a recheck time.
   */
    public function tick($watcherId = null)
    {
        static $nextCheckIn = null;
        static $nextCheckInId = null;

        if ($watcherId == $nextCheckInId) {
            $nextCheckInId = null;
        }

        $now = microtime(1);
        $this->logger->debug("Scheduler: checking schedule at $now");

        foreach (array_keys($this->schedule) as $time) {
            if ($now >= $time) {
                $diff = $now - $time;
                $this->logger->info("Scheduler: Running tasks scheduled for $time at $now ($diff seconds late)");
                $this->runTasks($time);
                $nextCheckIn = null;
                $nextCheckInId = null;
            }
        }

        ksort($this->schedule);
        $times = array_keys($this->schedule);
        if (empty($times)) {
            $nextCheckIn = null;
            $nextCheckInId = null;
            $this->logger->info("Scheduler: queue empty, no check-ins scheduled");
            return;
        }
        $nextRunIn = (float)($times[0] - time()) / 2;

        if ($nextRunIn < 0.5) {
            $nextRunIn /= 500;
        } elseif ($nextRunIn < 2) {
            $nextRunIn /= 10;
        } elseif ($nextRunIn < 5) {
            $nextRunIn /= 4;
        }

        if (!isset($nextCheckInId) or !isset($nextCheckIn) or $nextCheckIn > $nextRunIn + $now) {
            $this->logger->debug(
                "Scheduler: Next scheduled event is at $times[0] - checking back in $nextRunIn seconds"
            );
            $nextCheckIn = (int)$now + $nextRunIn;
            if ($nextCheckInId) {
                Loop::cancel($nextCheckInId);
                $this->logger->debug("Scheduler: Cancelling check-in at $nextRunIn");
            }
            $nextCheckInId = Loop::delay($nextRunIn * 1000, $this->callableFromInstanceMethod('tick'));
        } else {
            $this->logger->debug(
                "Scheduler: Wanted to schedule a check in in $nextRunIn seconds, but the next it scheduled for "
                . (string)($nextCheckIn - $now)
            );
        }
    }

  /**
   * Run the specific tasks at a specified time.
   */
    public function runTasks($time)
    {
        foreach ($this->schedule[$time] as $key => $task) {
            $this->cinderella->run($task, ':scheduler:');
            unset($this->schedule[$time][$key]);
        }
        if (empty($this->schedule[$time])) {
            unset($this->schedule[$time]);
        }
    }
}
