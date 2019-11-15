<?php

namespace Cinderella\Task;

use Amp\Artax\DefaultClient;
use Amp\Artax\Request;
use Cinderella\Cinderella;

class TaskRunner extends Task
{

    public function processArguments($arguments)
    {
        if (!isset($this->options['tasks'])) {
            $this->options['tasks'] = $arguments['tasks'];
        }
        if (!isset($this->options['resolve'])) {
            $this->options['resolve'] = $arguments['resolve'];
        }
    }

    public function run($options = [])
    {
        $start = microtime(true);
        $id = $this->getId();
        $promises = [];
        $messages = [];
        $logger = $this->cinderella->getLogger();
        $data = [];
        foreach ($this->options['tasks'] as $taskdata) {
            $task = Task::Factory($taskdata, $this->cinderella);
            $result = $task->run();
            if ($promise = $result->getPromise()) {
                $promises[] = $promise;
            }
            if ($message = $result->getMessage()) {
                $messages[] = $message;
            }
            $data[$result->getId()] = $result->getData();
        }

        $resolveTask = Task::Factory($this->options['resolve'], $this->cinderella);

        $promise = \Amp\Promise\all($promises);
        $promise->onResolve(
            function ($error = null, $result = null) use ($resolveTask, $id, $start, $logger) {
                $time = microtime(true) - $start;
                $options = [
                    'body' => [
                        'resolve' => [
                            'results' => $result,
                            'time' => $time,
                        ],
                    ],
                ];
                $resolveTask->run($options);
                if ($error) {
                    $logger->error("Task $id ($time seconds): an error occured:" . $error->getMessage());
                } else {
                    $logger->info("Task $id ($time seconds): Successfully ran all tasks");
                }
            }
        );

        $message = "Task $id: Scheduling set of tasks";
        return new TaskResult($id, $this->remoteId, $message, $promise, $data);
    }
}
