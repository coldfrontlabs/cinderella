<?php

namespace Cinderella\Task;

class ScheduleRefresh extends Task
{
    public function run($options = [])
    {
        Loop::defer($this->cinderella->callableFromInstanceMethod('refreshScheduler'));
        return new TaskResult($this->id, $this->remoteId, "Scheduler refreshed sources");
    }
}
