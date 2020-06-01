<?php

namespace Cinderella;

use Amp\CallableMaker;
use Amp\Loop;
use Cinderella\Task\Task;
use Cinderella\Task\TaskType;

class Queue {
    use CallableMaker;

    public const IDLE = 'idle';
    public const RUNNING = 'running';

    protected $queues;
    protected $status;
    protected $logger;
    protected $cinderella;

  /**
   * Queue module's contructor.
   */
    public function __construct($logger, $cinderella)
    {
        $this->queues = [];
        $this->status = [];
        $this->logger = $logger;
        $this->cinderella = $cinderella;
    }

    public function queueTask($queueid, QueuedTask $task) {
        $this->queues[$queueid] = array_push($this->queues[$queueid], $task);
        if (!isset($this->status[$queueid])) {
            $this->status[$queueid] = Queue::IDLE;
        }
        $name = $task->getLoggingName();
        $this->logger->info(
            "Queue: Queuing task $name in queue $queueid ({$this->status[$queueid]}: " . sizeof($this->queues[$queueid]) . " waiting tasks)"
        );
        Loop::run($this->callableFromInstanceMethod('processQueue'));
    }

    public function processQueue() {

    }

    public function cancelTask($queueid, $remoteid) {
        foreach ($this->queues[$queueid] as $key => $task) {
            if ($task->getRemoteId() == $remoteid) {
                unset($this->queues[$queueid][$key]);
                return TRUE;
            }
        }
        return FALSE;
    }


    public function getStatus() {
        $ret = [];
        foreach ($this->queues as $queueid => $tasks) {
            $ret[$queueid] = array_map(function ($task) { return $task->getLoggingName(); }, $tasks);
        }
        return $ret;
    }
}