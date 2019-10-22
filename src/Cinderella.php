<?php

namespace Cinderella;

use Amp\Artax\DefaultClient;
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

class Cinderella {
  use CallableMaker;
  private $config;
  private $promises = [];
  private $queue = [];
  private $scheduler;

  function __construct($config, $logger) {
    $this->config = $config + $this->defaultConfig();
    $this->config['endpoint'] = $config['endpoint'] + $this->defaultConfig()['endpoint'];
    $this->logger = $logger;

    if (isset($config['schedule'])) {
      $this->scheduler = new Scheduler($this->logger, $this);
      foreach ($config['schedule'] as $name => $schedule) {
        $this->scheduler->register($name, $schedule);
        $this->logger->debug("Registering schedule $name: {$schedule['url']}");
      }
    }

    Loop::run($this->callableFromInstanceMethod('server'));
  }

  private function server() {
    $servers = [];
    foreach ($this->config['listen'] as $l) {
      $servers[] = Socket\listen($l);
    }

    $router = new Router;
    foreach ($this->config['endpoint'] as $path => $endpoint) {
      $method = $endpoint['method'] ?? 'GET';
      $router->addRoute($method, $path, new CallableRequestHandler($this->callableFromInstanceMethod('handle')));
      $this->logger->debug("Adding route $path:$method");
    }

    $server = new Server($servers, $router, $this->logger);
    yield $server->start();

    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
      Loop::cancel($watcherId);
      yield $server->stop();
    });
  }

  public static function defaultConfig() {
    return [
      'listen' => [
        '0.0.0.0:10101',
      ],
      'max_concurrency' => 12,
      'max_queue' => 12,
      'endpoint' => [
        'status' => [
          'type' => TaskType::Status,
        ],
        'schedule-refresh' => [
          'type' => TaskType::ScheduleRefresh,
        ],
        'task' => [
          'type' => TaskType::TaskRunner,
          'method' => 'POST',
        ]
      ],
    ];
  }

  private function handle(Request $request) {
    $args = $request->getAttribute(Router::class);
    $path = $request->getUri()->getPath();
    if ($path[0] == '/') {
      $path = substr($path,1);
    }

    $config = $this->config['endpoint'][$path] ?? FALSE;

    if (!$config) {
      return new Response(Status::NOT_FOUND);
    }

    //TODO: Add resquest queuing here.

    return $this->runRoutine($path);
  }

  private function runRoutine($path) {
    if (!isset($this->config['endpoint'][$path])) {
      return new Response(Status::NOT_FOUND);
    }
    $config = $this->config['endpoint'][$path];
    $task = Task::Factory($config, $this);

    if ($message = $this->run($task, $path)) {
      return new Response(Status::OK, ['content-type' => 'text/plain'], $message);
    }

    return new Response(Status::NOT_IMPLEMENTED);
  }

  public function run(Task $task, $path) {
    $result = $task->run();

    if ($promise = $result->getPromise()) {

      //$promise->onResolve();
      //$this->callableFromInstanceMethod('server')
      //TODO: Set on onresolve to remove promise from list.
      $this->promises[$path][$task->getId()] = $promise;
    }
    try {
      Loop::defer($this->callableFromInstanceMethod('resolve'));
    } finally {

    }

    if ($message = $result->getMessage()) {
      $this->logger->notice($message);
      return $message;
    }
    return FALSE;
  }

  public function refreshScheduler() {
    return $this->scheduler->refresh();
  }

  public function scheduleTask($array) {
    $id = 'unnamed:' . $array['id'];
    $tasktime = $task['time'];
    $tasktime = time() + rand(10,45);
    $task = Task::Factory($task['task'], $this->cinderella);
    return $this->scheduler->scheduleTask($id, $time, $task);
  }

  public function getStatus() {
    return [
      'promises' => $this->promises,
    ];
  }

  public function resolve() {
    foreach ($this->promises as $group => $promises) {
      foreach ($promises as $id => $promise) {
        $this->pending[$group][$id] = $promise;
        unset($this->promises[$group][$id]);
        $logger = $this->logger;
        $results = Loop::Run(function () use ($promise, $id, $group, $logger) {
          $logger->notice("Yielding promise $id");
          yield $promise;
        });
      }
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
}