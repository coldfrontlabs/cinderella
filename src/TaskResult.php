<?php

namespace Cinderella;

class TaskResult {
  public $message;
  public $promise;

  function __construct($message, $promise) {
    $this->message = $message;
    $this->promise = $promise;
  }
}
