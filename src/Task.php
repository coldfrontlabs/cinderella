<?php
namespace Cinderella;


class Task {
  public $type;
  public $options;

  public function __construct($type, $options = []) {
    $this->type = $type;
    $this->options = $options;
  }

  public function run() {
    print "Running {$this->type}\n";
  }

}
