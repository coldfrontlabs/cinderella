<?php

namespace Cinderella\Task;

class TryOnShoe extends Task
{
    public function run()
    {
        return new TaskResult($this->id, $this->remoteId, ["result" => "Perhaps if it would help . . . but you see I have the other slipper"]);
    }
}
