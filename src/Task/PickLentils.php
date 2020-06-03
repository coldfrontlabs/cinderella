<?php

namespace Cinderella\Task;

use Amp\CallableMaker;
use Amp\Deferred;
use Amp\Loop;

/**
 * Sleep task for testing mostly.
 */
class PickLentils extends Task
{
    use CallableMaker;
    private $deferred;
    private $count;

    public function defaults()
    {
        return [
            'id' => null,
            'lentils' => 5,
        ];
    }

    public function run(): TaskResult
    {
        $message = "Picking {$this->options['lentils']} from the fireplace";
        $this->cinderella->getLogger()->info($message);
        $this->deferred = new Deferred();
        $this->count = 1;
        Loop::delay(1000, $this->callableFromInstanceMethod('pickLentils'));
        return new TaskResult(
            $this->getId(),
            $this->getRemoteId(),
            $message,
            $this->deferred->promise()
        );
    }

    private function pickLentils()
    {
        if ($this->count < $this->options['lentils']) {
            $this->cinderella->getLogger()->info($this->getLoggingName()
                . ": Picked {$this->count} of {$this->options['lentils']} lentils out of the fireplace");
            $this->count++;
            Loop::delay(1000, $this->callableFromInstanceMethod('pickLentils'));
            return;
        }
        $this->cinderella->getLogger()->info($this->getLoggingName()
            . ": Finished picking {$this->options['lentils']} lentils out of the fireplace");
        $this->deferred->resolve("Done");
    }
}
