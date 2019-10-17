<?php

namespace Cinderella\Task;

class TaskResult {
  protected $message;
  protected $promise;

  function __construct($message, $promise) {
    $this->message = $message;
    $this->promise = $promise;
  }

  function getMessage() {
    return $this->message ?? "";
  }

  function getPromise() {
    return $this->promise ?? FALSE;
  }
}
