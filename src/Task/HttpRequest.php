<?php

namespace Cinderella\Task;

use Amp\Artax\DefaultClient;
use Amp\Artax\Request;

class HttpRequest extends Task {
  public function run() {
    $client = new DefaultClient();
    $request = new Request($this->options['url'], $this->options['method']);
    if (isset($this->options['body'])) {
      $request->withBody($this->options['body']);
    }
    if (isset($this->options['headers'])) {
      foreach ($this->options['headers'] as $header => $value) {
        $request->withHeader($header, $value);
      }
    }

    $promise = $client->request($this->options['url']);
    $message = 'Task: Sending HTTP Request to ' . $this->options['url'];
    return new TaskResult($message, $promise);
  }
}
