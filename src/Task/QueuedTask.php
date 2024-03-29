<?php

namespace Cinderella\Task;

class QueuedTask extends Task
{

    public function run(): TaskResult
    {
        $this->cinderella->queueTask($this->options['queue'], $this->options['task'], $this->options['resolve']);
        return new TaskResult(
            $this->getId(),
            $this->getRemoteId(),
            'Queued task'
        );
    }

    public function defaults()
    {
        return [
            'id' => null,
            'queue' => 'default',
            'task' => null,
            'resolve' => null,
        ];
    }
}
