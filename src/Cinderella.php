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
use Cinderella\Task\QueuedTask;
use Cinderella\Task\Task;
use Cinderella\Task\TaskType;
use Monolog\Logger;
use Psr\Log\LogLevel;

/**
 * Main class for running the Cinderella daemon.
 */
class Cinderella
{
    use CallableMaker;

    private $config;
    private $pending = [];
    private $promises = [];
    private $queue;
    private $scheduler;

    /**
     * Constructor that initializes the member variables.
     */
    public function __construct($config, $logger)
    {
        $this->config = $config + $this->defaultConfig();
        $this->config['endpoint'] = $config['endpoint'] + $this->defaultConfig()['endpoint'];
        $this->logger = $logger;
        $this->queue = new Queue($this->logger, $this);
    }

    /**
     * Start server in the AMP loop.
     */
    public function start()
    {
        Loop::run($this->callableFromInstanceMethod('server'));
    }

    /**
     * Run the server.
     */
    private function server()
    {
        // Setup the listen address for the webserver.
        $servers = [];
        foreach ($this->config['listen'] as $l) {
            $servers[] = Socket\listen($l);
        }

        // Setup the request router.
        $router = new Router();
        foreach ($this->config['endpoint'] as $path => $endpoint) {
            $method = $endpoint['method'] ?? 'GET';
            $router->addRoute($method, $path, new CallableRequestHandler($this->callableFromInstanceMethod('handle')));
            $this->logger->debug("Adding route $path:$method");
        }

        // Start the amp webserver.
        $server = new Server($servers, $router, $this->logger);

        if ($result = $server->start()) {
            yield $result;
        } else {
            $this->logger->error("Couldn't start server");
        }

        // Start the scheduler.
        Loop::defer($this->callableFromInstanceMethod('scheduler'));
    }

    /**
     * Setup the schedule runner.
     */
    private function scheduler()
    {
        $this->logger->debug('Starting scheduler');
        $this->scheduler = new Scheduler($this->logger, $this);

        if (isset($this->config['schedule'])) {
            foreach ($this->config['schedule'] as $name => $schedule) {
                $this->scheduler->register($name, $schedule);
                $this->logger->info("Registering schedule $name: {$schedule['url']}");
            }
        }
    }

    /**
     * Default configuration for Cinderella.
     */
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

    /**
     * Handle incomming requests to the AMPHP webserver.
     */
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
        return $this->runRoutine($path, $request);
    }

    /**
     * Setup the task for the specified path and handle HTTP return codes.
     */
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

    /**
     * Execute the path task.
     */
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

    /**
     * Trigger the scheduler to refresh the schedules.
     */
    public function refreshScheduler()
    {
        return $this->scheduler->asyncRefresh();
    }

    /**
     * Schedule task in the scheduler.
     */
    public function scheduleTask($array)
    {
        $id = 'unnamed:' . $array['id'];
        $tasktime = $task['time'];
        $task = Task::factory($task['task'], $this);
        return $this->scheduler->scheduleTask($id, $time, $task);
    }

    /**
     * Queue tasks in the queueing system.
     */
    public function queueTask($queue, $task, $resolve)
    {
        $queued_task = Task::factory($task, $this);
        $resolve_task = null;
        if ($resolve) {
            $resolve_task = Task::factory($resolve, $this);
        }
        return $this->queue->queueTask($queue, $queued_task, $resolve_task);
    }

    /**
     * Get the status of the cinderella server.
     */
    public function getStatus()
    {
        return [
            'promises' => array_map('array_keys', $this->promises),
            'pending' => array_map('array_keys', $this->pending),
            'schedule' => $this->scheduler->getStatus(),
            'queue' => $this->queue->getStatus(),
            'loop' => Loop::getInfo(),
        ];
    }

    /**
     * Track resolved promises.
     */
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
    }

    /**
     * Return access to Cinderella's logger.
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Mark tasks as complete.
     */
    public function complete($task, $group)
    {
        unset($this->pending[$group][$task]);
    }
}
