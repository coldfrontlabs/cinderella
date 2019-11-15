<?php

namespace Cinderella\Task;

class Status extends Task
{
    public function run($options = [])
    {
        $status = $this->cinderella->getStatus();
        return new TaskResult($this->id, $status);
    }
}
