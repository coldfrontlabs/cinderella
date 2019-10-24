<?php

namespace Cinderella\Task;

use Amp\Process\Process;

class Bash extends Task
{
    public function run()
    {
        $process = new Process($this->options['parameters']['cmd']);
        $promise = $process->start();
        $message = $path . ' - Executing ' . $this->options['parameters']['cmd'];
        return new TaskResult($message, $promise);
    }
}
