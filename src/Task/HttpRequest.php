<?php

namespace Cinderella\Task;

use Amp\Artax\DefaultClient;
use Amp\Artax\Request;
use Cinderella\Cinderella;

class HttpRequest extends Task
{

    public function run($options = [])
    {
        static $client = null;
        if (!$client) {
            $client = new DefaultClient();
        }
        $start = microtime(true);

        $this->options = array_merge_recursive($this->options, $options);
        $url = $this->options['url'];
        $method = $this->options['method'];
        $body = $this->options['body'] ?? null;
        $headers = $this->options['headers'] ?? [];
        $id = $this->getId();
        $logger = $this->cinderella->getLogger();

        $promise = \Amp\call(function () use ($client, $url, $method, $body, $headers, $id) {
            $request = new Request($url, $method);
            if (isset($body)) {
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                $request->withBody($body);
            }

            foreach ($headers as $header => $value) {
                $request->withHeader($header, $value);
            }

            $response = yield $client->request($request);
            $contents = yield $response->getBody();
            return [$response, $contents];
        });

        $promise->onResolve(
            function ($error = null, $result = null) use ($id, $start, $url, $logger) {
                $time = microtime(true) - $start;
                if ($error) {
                    $logger->error("Task $id ($time seconds): an error occured:" . $error->getMessage());
                } else {
                    $logger->info("Task $id ($time seconds): Successful call to $url: " . $result[1]);
                }
            }
        );
        $message = "Task $id: Deffered sending HTTP Request to $url";
        return new TaskResult($message, $promise);
    }
}
