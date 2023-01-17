<?php

namespace Cinderella;

use Amp\Loop;
use Cinderella\Task\Task;

class Queue
{
    public const IDLE = 'idle';
    public const RUNNING = 'running';

    protected $queues;
    protected $status;
    protected $logger;
    protected $cinderella;
    protected $promises;

  /**
   * Queue module's contructor.
   */
    public function __construct($logger, $cinderella)
    {
        $this->queues = [];
        $this->status = [];
        $this->promises = [];
        $this->logger = $logger;
        $this->cinderella = $cinderella;
    }

    public function queueTask($queueid, Task $task, $resolve = null)
    {
        $this->queues[$queueid][] = [$task, $resolve];
        if (!isset($this->status[$queueid])) {
            $this->status[$queueid] = Queue::IDLE;
        }
        $name = $task->getLoggingName();
        $this->logger->info(
            "Queue: Queuing task $name in queue $queueid ({$this->status[$queueid]}: "
            . sizeof($this->queues[$queueid]) . " waiting tasks)"
        );
        Loop::defer(\Closure::fromCallable([$this, 'processQueue']));
    }

    public function processQueue()
    {
        $this->logger->info("Queue: Processing queue");
        foreach ($this->status as $queueid => $status) {
            if ($status == Queue::IDLE) {
                if (sizeof($this->queues[$queueid]) == 0) {
                    unset($this->queues[$queueid]);
                    unset($this->status[$queueid]);
                    continue;
                } else {
                    list($task, $resolve) = array_shift($this->queues[$queueid]);
                    $result = $task->run();
                    $this->logger->notice('Queue: Ran ' . $task->getLoggingName() . ': ' . $result->getMessage());
                    $promise = $result->getPromise();
                    $this->promises[$queueid][$task->getLoggingName()] = $promise;
                    $callback = \Closure::fromCallable([$this, 'resolveQueueTask']);
                    if ($promise) {
                        $this->status[$queueid] = Queue::RUNNING;
                        $promise->onResolve(function () use ($queueid, $callback, $resolve) {
                            $callback($queueid, $resolve);
                        });
                    }
                    continue;
                }
            }
        }
    }

    private function resolveQueueTask($queueid, $resolve)
    {
        $this->status[$queueid] = Queue::IDLE;
        unset($this->promises[$queueid]);
        if ($resolve) {
            $result = $resolve->run();
            $this->logger->notice("Queue: Running resolve tasks from $queueid "
            . $resolve->getLoggingName() . ': ' . $result->getMessage());
            $promise = $result->getPromise();
            $name = $resolve->getLoggingName();
            $resolvequeue = 'resolve-' . $queueid;
            $callback = \Closure::fromCallable([$this, 'resolveResolveTask']);
            if ($promise) {
                $this->promises[$resolvequeue][$name] = $promise;
                $promise->onResolve(function () use ($callback, $name, $resolvequeue) {
                    $callback($resolvequeue, $name);
                });
            }
        }
        Loop::defer(\Closure::fromCallable([$this, 'processQueue']));
    }

    private function resolveResolveTask($queueid, $name)
    {
        unset($this->promises[$queueid][$name]);
        if (sizeof($this->promises[$queueid]) == 0) {
            unset($this->promises[$queueid]);
        }
    }

    public function cancelTask($queueid, $remoteid)
    {
        foreach ($this->queues[$queueid] as $key => $task) {
            if ($task->getRemoteId() == $remoteid) {
                unset($this->queues[$queueid][$key]);
                return true;
            }
        }
        return false;
    }

    public function getStatus()
    {
        $queues = [];
        foreach ($this->queues as $queueid => $tasks) {
            $queues[$queueid] = array_map(function ($task) {
                return $task[0]->getLoggingName();
            }, $tasks);
        }
        $promises = [];
        foreach ($this->promises as $queueid => $ps) {
            $promises[$queueid] = array_keys($ps);
        }

        return [
            'queues' => $queues,
            'status' => $this->status,
            'promises' => $promises,
        ];
    }
}
