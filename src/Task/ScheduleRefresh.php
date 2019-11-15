<?php

namespace Cinderella\Task;

class ScheduleRefresh extends Task
{
    public function run($options = [])
    {
        $this->cinderella->refreshScheduler();
        return new TaskResult($this->id, "Scheduler refreshed sources");
    }
}
