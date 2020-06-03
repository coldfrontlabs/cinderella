<?php

namespace Cinderella\Task;

use Cinderella\Cinderella;

class Task
{
    protected $id;
    protected $remoteId;
    protected $options;
    protected $queue;
    protected $type;
    protected $cinderella;

    public function __construct($type, $options, Cinderella $cinderella)
    {
        $this->id = uniqid();
        $this->options = $options + $this->defaults();
        $this->remoteId = $options['id'] ?? null;
        $this->type = $type;
        $this->cinderella = $cinderella;
    }

    public function getId()
    {
        return $this->id . '-' . $this->type;
    }

    public function getRemoteId()
    {
        return $this->remoteId;
    }

    public function getLoggingName()
    {
        if ($this->remoteId) {
            return $this->getId() . '+' . $this->getRemoteId();
        } else {
            return $this->getId();
        }
    }

    protected function defaults()
    {
        return [
            'id',
        ];
    }

    protected function cleanup()
    {
      // Do nothing in base task.
    }

    public function run(): TaskResult
    {
        $message = "Running {$this->type}\n";
        return new TaskResult($message, null);
    }

    public function mergeOptions($options)
    {
        $this->options = array_merge_recursive($this->options, $options);
    }

    public static function factory($task, Cinderella $cinderella)
    {
        switch ($task['type']) {
            case TaskType::TASK_RUNNER:
                return new TaskRunner(TaskType::TASK_RUNNER, $task, $cinderella);

            case TaskType::SCHEDULE_REFRESH:
                return new ScheduleRefresh(TaskType::SCHEDULE_REFRESH, $task, $cinderella);

            case TaskType::HTTP_REQUEST:
                return new HttpRequest(TaskType::HTTP_REQUEST, $task, $cinderella);

            case TaskType::QUEUED_TASK:
                return new QueuedTask(TaskType::QUEUED_TASK, $task, $cinderella);

            case TaskType::STATUS:
                return new Status(TaskType::STATUS, $task, $cinderella);

            case TaskType::PICK_LENTILS:
                return new PickLentils(TaskType::PICK_LENTILS, $task, $cinderella);

            case TaskType::TRY_ON_SLIPPER:
                return new TryOnSlipper(TaskType::TRY_ON_SLIPPER, $task, $cinderella);

            case TaskType::NONE:
            default:
                return new Task(TaskType::NONE, $task, $cinderella);
        }
    }
}
