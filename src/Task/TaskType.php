<?php

namespace Cinderella\Task;

class TaskType
{
    public const NONE = 'none';
    public const HTTP_REQUEST = 'http_request';
    public const BASH = 'bash';
    public const TASK_RUNNER = 'task_runner';
    public const STATUS = 'status';
    public const SCHEDULE_REFRESH = 'schedule_refresh';
}
