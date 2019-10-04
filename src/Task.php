<?php
namespace Cinderella;


abstract class Task {
  protected $options;
  protected $queue;

  public function __construct($options = []) {
    $this->options = $options + $this->defaults();
    $this->queue = [];
  }

  public function queue() {
    $this->cleanup();
    if ($this->options['concurrency'] > 0 and sizeof($queue)) {}
  }

  protected function defaults() {
    return [
      'concurrency' => 0,
      'queue' => false,
    ];
  }

  abstract protected function cleanup();
  abstract protected function run();
}
