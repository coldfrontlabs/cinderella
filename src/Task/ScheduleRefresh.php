<?php

namespace Cinderella\Task;

use Amp\Loop;

class ScheduleRefresh extends Task
{
    public function run() : TaskResult
    {
        $this->cinderella->refreshScheduler();
        return new TaskResult($this->id, $this->remoteId, "Scheduler refreshed sources");
    }
}
