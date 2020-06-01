<?php

namespace Cinderella;

use Amp\CallableMaker;
use Amp\Http\Client\HttpException;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\Server;
use Amp\Http\Status;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Loop;
use Amp\Process\Process;
use Amp\Socket;
use Cinderella\Task\Task;
use Cinderella\Task\TaskType;
use Monolog\Logger;
use Psr\Log\LogLevel;

class Cinderella
{
    use CallableMaker;

    private $config;
    private $pending = [];
    private $promises = [];
    private $queue;
    private $scheduler;

    public function __construct($config, $logger)
    {
        $this->config = $config + $this->defaultConfig();
        $this->config['endpoint'] = $config['endpoint'] + $this->defaultConfig()['endpoint'];
        $this->logger = $logger;
        $this->queue = new Queue($this->logger, $this);
        Loop::run($this->callableFromInstanceMethod('server'));
    }

    private function server()
    {
        $servers = [];
        foreach ($this->config['listen'] as $l) {
            $servers[] = Socket\listen($l);
        }

        $router = new Router();
        foreach ($this->config['endpoint'] as $path => $endpoint) {
            $method = $endpoint['method'] ?? 'GET';
            $router->addRoute($method, $path, new CallableRequestHandler($this->callableFromInstanceMethod('handle')));
            $this->logger->debug("Adding route $path:$method");
        }

        $server = new Server($servers, $router, $this->logger);

        if ($result = $server->start()) {
            yield $result;
        } else {
            $this->logger->error("Couldn't start server");
        }
        if (defined('SIGINT')) {
            Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
                Loop::cancel($watcherId);
                yield $server->stop();
            });
        }

        Loop::run($this->callableFromInstanceMethod('scheduler'));
    }

    private function scheduler() {
        $this->scheduler = new Scheduler($this->logger, $this);

        if (isset($this->config['schedule'])) {
            foreach ($this->config['schedule'] as $name => $schedule) {
                $this->scheduler->register($name, $schedule);
                $this->logger->info("Registering schedule $name: {$schedule['url']}");
            }
        }
    }

    public static function defaultConfig()
    {
        return [
            'listen' => [
                '0.0.0.0:10101',
            ],
            'endpoint' => [
                'status' => [
                    'type' => TaskType::STATUS,
                ],
                'schedule-refresh' => [
                    'type' => TaskType::SCHEDULE_REFRESH,
                ],
                'task' => [
                    'type' => TaskType::TASK_RUNNER,
                    'method' => 'POST',
                ],
            ],
        ];
    }

    private function handle(Request $request)
    {
        $args = $request->getAttribute(Router::class);
        $path = $request->getUri()->getPath();
        if ($path[0] == '/') {
            $path = substr($path, 1);
        }

        $config = $this->config['endpoint'][$path] ?? false;

        if (!$config) {
            return new Response(Status::NOT_FOUND);
        }

      //TODO: Add request queuing here.

        return $this->runRoutine($path, $request);
    }

    private function runRoutine($path, $request)
    {
        if (!isset($this->config['endpoint'][$path])) {
            return new Response(Status::NOT_FOUND);
        }
        $config = $this->config['endpoint'][$path];

        $inputStream = $request->getBody();
        $buffer = "";
        while (($chunk = yield $inputStream->read()) !== null) {
            $buffer .= $chunk;
        }
        $body = json_decode($buffer, true);
        if (!$body) {
            $body = [];
        }

        $task = Task::factory($body + $config, $this);

        if ($message = $this->run($task, $path)) {
            return new Response(Status::OK, ['content-type' => 'text/plain'], $message);
        }

        return new Response(Status::NOT_IMPLEMENTED);
    }

    public function run(Task $task, $path)
    {
        $result = $task->run();
        if ($promise = $result->getPromise()) {
            $this->promises[$path][$task->getId()] = $promise;
        }
        try {
            Loop::defer($this->callableFromInstanceMethod('resolve'));
        } finally {
        }

        $this->logger->notice('Ran ' . $task->getId());
        return json_encode($result->toArray(), JSON_PRETTY_PRINT);
    }

    public function refreshScheduler()
    {
        return $this->scheduler->asyncRefresh();
    }

    public function scheduleTask($array)
    {
        $id = 'unnamed:' . $array['id'];
        $tasktime = $task['time'];
        $task = Task::factory($task['task'], $this->cinderella);
        return $this->scheduler->scheduleTask($id, $time, $task);
    }

    public function queueTask($queue, $task) {
        $queuedtask = Task::factory($task, $this->cinderella);
        return $this->queue->queueTask($queue, $queuedtask);
    }

    public function getStatus()
    {
        return [
            'promises' => array_map('array_keys', $this->promises),
            'pending' => array_map('array_keys', $this->pending),
            'schedule' => $this->scheduler->getStatus(),
            'queue' => $this->queue->getStatus(),
        ];
    }

    public function resolve()
    {
        $logger = $this->logger;
        $cinderella = &$this;
        foreach ($this->promises as $group => $promises) {
            if (empty($promises)) {
                break;
            }

            foreach ($promises as $id => $promise) {
                $pending['group'] = $group;
                $pending['ids'][] = $id;
                $this->pending[$group][$id] = $promise;
                unset($this->promises[$group][$id]);
            }
            $result = \Amp\Promise\all($promises);
            $result->onResolve(
                function ($error = null, $result = null) use ($pending, $cinderella, $logger) {
                    foreach ($pending['ids'] as $id) {
                        $cinderella->complete($id, $pending['group']);
                    }
                    $message = 'Tasks ' . implode(', ', $pending['ids']) . ' from ' . $pending['group'] . ' completed';
                    if ($error) {
                        $logger->error($message . ': ' . $error->getMessage());
                    } else {
                        $logger->info($message);
                    }
                }
            );
        }

        foreach ($this->promises as $group => $promises) {
            if (empty($this->promises)) {
                unset($this->promises[$group]);
            }
        }

        if (!empty($this->promises)) {
            Loop::defer($this->callableFromInstanceMethod('resolve'));
        }
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function complete($task, $group)
    {
        unset($this->pending[$group][$task]);
    }
}
