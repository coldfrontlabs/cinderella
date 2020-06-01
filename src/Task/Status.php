<?php

namespace Cinderella\Task;

class Status extends Task
{
    public function run()
    {
        $status = $this->cinderella->getStatus();
        return new TaskResult($this->id, $this->remoteId, $status);
    }
}
