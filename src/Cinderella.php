<?php

namespace Cinderella;

use Amp\Artax\DefaultClient;
use Amp\ByteStream\ResourceOutputStream;
use Amp\CallableMaker;
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
use Monolog\Logger;

class Cinderella {
  use CallableMaker;
  private $config;
  private $promises = [];
  private $queue = [];

  function __construct($config) {
    $this->config = $config + $this->defaultConfig();
    $this->config['endpoint'] = $config['endpoint'] + $this->defaultConfig()['endpoint'];
    $this->logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $this->logHandler->setFormatter(new ConsoleFormatter);
    $this->logger = new Logger('server');
    $this->logger->pushHandler($this->logHandler);

    Loop::run($this->callableFromInstanceMethod('server'));
  }

  private function server() {
    $servers = [];
    foreach ($this->config['listen'] as $l) {
      $servers[] = Socket\listen($l);
    }

    $router = new Router;
    foreach ($this->config['endpoint'] as $path => $endpoint) {
      $router->addRoute($endpoint['option'] ?? 'GET', $path, new CallableRequestHandler($this->callableFromInstanceMethod('handle')));
    }

    $server = new Server($servers, $router, $this->logger);
    yield $server->start();

    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
      Loop::cancel($watcherId);
      yield $server->stop();
    });
  }

  private function defaultConfig() {
    return [
      'listen' => [
        '0.0.0.0:10101',
      ],
      'max_concurrency' => 12,
      'max_queue' => 12,
      'endpoint' => [
        'status' => [
          'type' => 'status',
        ],
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

  private function http_request($path) {
    $config = $this->config['endpoint'][$path];
    $client = new DefaultClient;
    $promise = $client->request($config['parameters']['url']);
    $message = $path . ' - Sending HTTP GET to ' . $config['parameters']['url'];
    return [$message, $promise];
  }

  private function bash($path) {
    $config = $this->config['endpoint'][$path];
    $process = new Process($config['parameters']['cmd']);
    $promise = $process->start();
    $message = $path . ' - Executing ' . $config['parameters']['cmd'];
    $this->logger->notice($message);
    return [$message, $promise];
  }

  private function runRoutine($path) {
    $config = $this->config['endpoint'][$path];
    $response = NULL;
    $message = FALSE;
    $promise = NULL;

    switch ($config['type']) {
      case 'http_request':
        list($message, $promise) = $this->http_request($path);
        break;

      case 'bash':
        list($message, $promise) = $this->bash($path);
        break;

      case 'status':
        $message = print_r($this->promises);
        break;
    }

    if ($message) {
      $this->logger->notice($message);
      return new Response(Status::OK, ['content-type' => 'text/plain'], $message);
    }

    if ($promise) {
      $id = uniqid();
      //$promise->onResolve();
      //$this->callableFromInstanceMethod('server')
      //TODO: Set on onresolve to remove promise from list.
      $this->promises[$path][$id] = $promise;
    }

    return new Response(Status::NOT_IMPLEMENTED);
  }
}
