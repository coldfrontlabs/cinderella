<?php

namespace Cinderella\Task;

class ScheduleRefresh extends Task
{
    public function run()
    {
        $this->cinderella->refreshScheduler();
        return new TaskResult("Scheduler refreshed sources", null);
    }
}
