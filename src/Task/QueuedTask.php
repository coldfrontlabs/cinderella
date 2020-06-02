<?php

namespace Cinderella\Task;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Cinderella\Cinderella;

class QueuedTask extends Task
{

    public function run()
    {
        $this->cinderella->queueTask($this->options['queue'], $this->options['task'], $this->options['resolve']);
        return new TaskResult(
            $this->getId(),
            $this->getRemoteId(),
            'Queued task'
        );
    }

    public function defaults() {
        return [
            'id' => NULL,
            'queue' => 'default',
            'task' => NULL,
            'resolve' => NULL,
        ];
    }
}
