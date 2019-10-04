<?php

use Amp\Artax\DefaultClient;
use Cinderella\TaskResult;

namespace Cinderella\Task;

class Http {
  protected function run() {
    $client = new DefaultClient();
    $promise = $client->request($this->options['parameters']['url']);
    $message = $path . ' - Sending HTTP GET to ' . $this->options['parameters']['url'];
    return new TaskResult($message, $promise);
  }
}
