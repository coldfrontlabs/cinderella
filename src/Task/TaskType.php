<?php
namespace Cinderella\Task;

class TaskType {
    const None = 'none';
    const HttpRequest = 'http_request';
    const Bash = 'bash';
    const TaskRunner = 'task_runner';
    const Status = 'status';
    const ScheduleRefresh = 'schedule_refresh';
}