<?php

namespace Cinderella;

use Amp\Loop;
use Cinderella\Task\Task;

class Scheduler
{
    protected $schedule;
    protected $scheduledTaskIds;
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
        $this->scheduledTaskIds = [];
        $this->logger = $logger;
        $this->cinderella = $cinderella;
        Loop::repeat(900 * 1000, \Closure::fromCallable([$this, 'scheduledRefresh']));
    }

    /**
     * Get the scheduler current status.
     */
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
        $this->remoteSchedules[$name]['refresh'] = $schedule['refresh'] ?? 900;
        $this->remoteSchedules[$name]['last_updated'] = 0;
        $this->refresh($name);
    }

    public function asyncRefresh()
    {
        Loop::defer(\Closure::fromCallable([$this, 'refresh']));
    }

  /**
   * Load remote schedule.
   */
    public function refresh($name = null)
    {
        if (!isset($name) or !isset($this->remoteSchedules[$name])) {
            foreach (array_keys($this->remoteSchedules) as $name) {
                $this->refresh($name);
            }
            return;
        }
        $this->logger->info("Scheduler: refreshing $name");
        $schedule = file_get_contents($this->remoteSchedules[$name]['url']);
        if (!$schedule) {
            $this->logger->error("Failed to load schedule: Couldn't reach the schedule URL");
            $this->remoteSchedules[$name]['refresh'] = 10;
            Loop::delay(10000, \Closure::fromCallable([$this, 'scheduledRefresh']));
            return;
        }
        $schedule = json_decode($schedule, true);

        if (!$schedule) {
            $this->logger->error("Failed to load schedule: Invalid JSON");
            $this->remoteSchedules[$name]['refresh'] = 10;
            Loop::delay(10000, \Closure::fromCallable([$this, 'scheduledRefresh']));
            return;
        }

        $tasks = $schedule['schedule'];

        $this->remoteSchedules[$name]['refresh'] = $schedule['refresh'] ?? 900;
        $this->remoteSchedules[$name]['last_updated'] = time();

        foreach ($tasks as $taskdata) {
            $task = Task::factory($taskdata['task'], $this->cinderella);
            $this->scheduleTask($taskdata['id'], $taskdata['time'], $task);
        }
    }

    /**
     * Refresh the scheduled, if needed.
     */
    public function scheduledRefresh()
    {
        static $lastRefresh = 0;
        $this->logger->debug("Scheduler: checking if schedules need refreshing.");
        if (empty($this->remoteSchedules)) {
            $this->logger->debug("Scheduler: no remote schedules, thus none need refreshing.");
            return;
        }

        $now = time();
        $nextRefreshIn = 900;
        foreach ($this->remoteSchedules as $name => $remote) {
            if (time() - $remote['last_updated'] > $remote['refresh']) {
                $this->logger->debug("Scheduler: $name needs refreshing");
                $this->refresh($name);
            } else {
                $this->logger->debug("Scheduler: $name does not needs refreshing");
            }
        }
    }

  /**
   * Schedule a single task.
   */
    public function scheduleTask($id, $time, Task $task)
    {
        $diff = $time - time();
        $date = date('Y-m-d H:i:s', $time);
        $this->logger->info("Scheduler: scheduling $id at $date ($time) - in $diff seconds");

      // Reschedule duplicate tasks.
        if (isset($this->scheduledTaskIds[$id])) {
            $this->logger->info(
                "Scheduler: Rescheduling task $id at $date ($time)"
            );
            $oldtime = $this->scheduledTaskIds[$id];
            unset($this->schedule[$oldtime][$id]);
        }

        $this->scheduledTaskIds[$id] = $time;

        if (!isset($this->schedule[$time])) {
            $this->schedule[$time] = [];
        }

        $this->schedule[$time][$id] = $task;

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
                $date = date('Y-m-d H:i:s', $time);
                $this->logger->info("Scheduler: Running tasks scheduled for $date ($time) at $now ($diff seconds late)");
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
            $date = date('Y-m-d H:i:s', $times[0]);
            $this->logger->debug(
                "Scheduler: Next scheduled event is at $date ($times[0]) - checking back in $nextRunIn seconds"
            );
            $nextCheckIn = (int)$now + $nextRunIn;
            if ($nextCheckInId) {
                Loop::cancel($nextCheckInId);
                $this->logger->debug("Scheduler: Cancelling check-in at $nextRunIn");
            }
            $nextCheckInId = Loop::delay($nextRunIn * 1000, \Closure::fromCallable([$this, 'tick']));
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
