<?php

namespace Cinderella\Task;

class Schedule extends Task
{

    public function defaults()
    {
        return [
            'id' => null,
            'time' => null,
            'tasks' => [],
        ];
    }

    public function processArguments($arguments)
    {
        if (!isset($this->options['tasks'])) {
            $this->options['tasks'] = $arguments['tasks'];
        }
        if (!isset($this->options['time']) and isset($arguments['time'])) {
            $this->options['time'] = $arguments['time'];
        }
    }

    public function run() : TaskResult
    {
        $count = 0;
        $task_ids = [];
        $time = $this->options['time'];
        $date = date('Y-m-d H:i:s', $time);
        foreach ($this->options['tasks'] as $task) {
            $task_ids[] = $task->getId();
            $this->cinderella->scheduleTask($task->getId(), $time, $task);
            $count++;
        }
        $ids = implode(', ', $task_ids);
        return new TaskResult($this->id, $this->remoteId, "Scheduled $count tasks ($ids) to run at $date ($time)");
    }
}
