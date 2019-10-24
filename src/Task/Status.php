<?php

namespace Cinderella\Task;

class Status extends Task
{
    public function run()
    {
        $status = $this->cinderella->getStatus();
        return new TaskResult(json_encode($status), null);
    }
}
