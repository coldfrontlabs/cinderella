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

  function __construct($config) {
    $this->config = $config + $this->defaultConfig();
    $this->logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $this->logHandler->setFormatter(new ConsoleFormatter);
    $this->logger = new Logger('server');
    $this->logger->pushHandler($this->logHandler);

    Loop::run($this->callableFromInstanceMethod('server'));
  }

  private function server() {
    $servers = [
      Socket\listen("0.0.0.0:1337"),
      Socket\listen("[::]:1337"),
    ];

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
      'endpoint' => [
        'hello-world' => [],
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

    switch ($config['type']) {
      case 'http_request':
        return new Response(Status::OK, ['content-type' => 'text/plain'], $this->http_request($config));

      case 'bash':
        return new Response(Status::OK, ['content-type' => 'text/plain'], $this->bash($config));
    }
    return new Response(Status::NOT_IMPLEMENTED);
  }

  private function http_request($config) {
    $client = new DefaultClient;
    $response = $client->request($config['parameters']['url']);
    $message = 'Sending HTTP GET to ' . $config['parameters']['url'];
    $this->logger->notice($message);
    return $message;
  }

  private function bash($config) {
    $process = new Process($config['parameters']['cmd']);
    $process->start();
    $message = 'Executing ' . $config['parameters']['cmd'];
    $this->logger->notice($message);
    return $message;
  }
}
