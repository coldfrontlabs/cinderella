<?php

namespace Cinderella\Task;

class TaskResult
{
    protected $id;
    protected $remoteId;
    protected $message;
    protected $promise;
    protected $data;

    public function __construct($id, $remoteId, $message, $promise = null, $data = [])
    {
        $this->id = $id;
        $this->remoteId = $remoteId;
        $this->message = $message;
        $this->promise = $promise;
        $this->data = $data;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getRemoteId()
    {
        return $this->remoteId;
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
            'remoteid' => $this->getRemoteId(),
            'message' => $this->getMessage(),
            'data' => $this->getData(),
        ];
    }
}
