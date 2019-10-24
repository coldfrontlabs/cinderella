<?php

namespace Cinderella\Task;

class TaskResult
{
    protected $message;
    protected $promise;

    public function __construct($message, $promise)
    {
        $this->message = $message;
        $this->promise = $promise;
    }

    public function getMessage()
    {
        return $this->message ?? "";
    }

    public function getPromise()
    {
        return $this->promise ?? false;
    }
}
