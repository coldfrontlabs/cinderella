<?php

namespace Cinderella\Task;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Cinderella\Cinderella;

class QueuedTask extends Task
{

    public function run()
    {
        $this->cinderella->queueTask($options['queue'], $options['task']);
    }

    public function defaults() {
        return [
            'queue' => 'default',
            'task' => NULL,
            'resolve' => NULL,
        ];
    }
}
