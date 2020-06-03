<?php

namespace Cinderella\Task;

class Status extends Task
{
    public function run(): TaskResult
    {
        $status = $this->cinderella->getStatus();
        return new TaskResult($this->id, $this->remoteId, $status);
    }
}
