<?php

namespace Cinderella;

class TaskManager
{
    protected $tasks;
    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    protected function create($name)
    {
        $classname = 'Task\\' . $this->config[$name]['type'];
        if (!class_exists($classname)) {
            throw new Exception("Invalid task type: $name");
        }
        $this->tasks[$name] = new $classname($this->config[$name]);
    }

    public function queue($name)
    {
        if (!isset($this->tasks[$name])) {
            $this->create($name);
        }
        $this->tasks[$name]->queue();
    }
}
