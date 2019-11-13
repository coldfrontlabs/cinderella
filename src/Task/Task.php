<?php

namespace Cinderella\Task;

use Cinderella\Cinderella;

class Task
{
    protected $id;
    protected $options;
    protected $queue;
    protected $type;
    protected $cinderella;

    public function __construct($type, $options, Cinderella $cinderella, $arguments = false)
    {
        $this->id = uniqid();
        $this->options = $options + $this->defaults();
        $this->queue = [];
        $this->type = $type;
        $this->cinderella = $cinderella;
        $this->processArguments($arguments);
    }

    public function processArguments($arguments)
    {
        return;
    }

    public function getId()
    {
        return $this->id . '-' . $this->type;
    }

    public function queue()
    {
        $this->cleanup();
        if ($this->options['concurrency'] > 0 and sizeof($queue)) {
        }
    }

    protected function defaults()
    {
        return [
        'concurrency' => 0,
        'queue' => false,
        ];
    }

    protected function cleanup()
    {
      // Do nothing in base task.
    }

    public function run($options = [])
    {
        $message = "Running {$this->type}\n";
        return new TaskResult($message, null);
    }

    public static function factory($task, Cinderella $cinderella, $arguments = false)
    {
        switch ($task['type']) {
            case TaskType::TASK_RUNNER:
                return new TaskRunner(TaskType::TASK_RUNNER, $task, $cinderella, $arguments);

            case TaskType::SCHEDULE_REFRESH:
                return new ScheduleRefresh(TaskType::SCHEDULE_REFRESH, $task, $cinderella, $arguments);

            case TaskType::HTTP_REQUEST:
                return new HttpRequest(TaskType::HTTP_REQUEST, $task, $cinderella, $arguments);

            case TaskType::STATUS:
                return new Status(TaskType::STATUS, $task, $cinderella, $arguments);

            case TaskType::NONE:
            default:
                return new Task(TaskType::NONE, $task, $cinderella, $arguments);
        }
    }
}
