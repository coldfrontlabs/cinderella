<?php

namespace Cinderella\Task;

class TaskResult
{
    protected $id;
    protected $message;
    protected $promise;
    protected $data;

    public function __construct($id, $message, $promise = null, $data = [])
    {
        $this->id = $id;
        $this->message = $message;
        $this->promise = $promise;
        $this->data = $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMessage()
    {
        return $this->message ?? "";
    }

    public function getPromise()
    {
        return $this->promise ?? false;
    }

    public function getData()
    {
        return $this->data;
    }

    public function toArray() {
        return [
            'id' => $this->getId(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
        ];
    }
}
