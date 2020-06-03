<?php

namespace Cinderella\Task;

use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Cinderella\Cinderella;

class HttpRequest extends Task
{

    public function defaults()
    {
        return [
            'body' => null,
            'headers' => [],
            'id' => null,
            'method' => 'GET',
            'timeout' => 15,
            'url' => null,
        ];
    }

    public function run($options = []): TaskResult
    {
        $starttime = microtime(true);
        $client = HttpClientBuilder::buildDefault();

        $this->options = array_merge_recursive($this->options, $options);
        $body = $this->options['body'];
        $headers = $this->options['headers'];
        $id = $this->getId();
        $logger = $this->cinderella->getLogger();
        $method = $this->options['method'];
        $remoteid = $this->options['id'];
        $timeout = $this->options['timeout'];
        $url = $this->options['url'];

        $promise = \Amp\call(function () use (
            $body,
            $client,
            $headers,
            $id,
            $logger,
            $method,
            $remoteid,
            $starttime,
            $timeout,
            $url
        ) {
            $request = new Request($url, $method);
            if (isset($body)) {
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                $request->setHeader('Content-type', 'application/json');
                $request->setBody($body);
            }

            foreach ($headers as $header => $value) {
                $request->setHeader($header, $value);
            }

            $request->setInactivityTimeout($timeout * 1000);
            $timing = [
                'start' => $starttime,
            ];

            try {
                $response = yield $client->request($request);
                $body = yield $response->getBody()->buffer();

                $endtime = microtime(true);
                $timing['end'] = $endtime;
                $timing['duration'] = $endtime - $timing['start'];
                return [
                    'id' => $id,
                    'remoteid' => $remoteid,
                    'url' => $url,
                    'method' => $method,
                    'status' => $response->getStatus(),
                    'reason' => $response->getReason(),
                    'body' => $body,
                    'time' => $timing,
                ];
            } catch (\Throwable $exception) {
                $endtime = microtime(true);
                $timing['end'] = $endtime;
                $timing['duration'] = $endtime - $timing['start'];
                return [
                    'id' => $id,
                    'remoteid' => $remoteid,
                    'url' => $url,
                    'method' => $method,
                    'status' => 'error',
                    'reason' => $exception->getMessage(),
                    'time' => $timing,
                ];
            }
        });

        $promise->onResolve(
            function ($error = null, $result = null) use ($id, $starttime, $url, $logger) {
                $time = microtime(true) - $starttime;
                if ($error) {
                    $logger->error("Task $id ($time seconds): an error occured: " . $error->getMessage());
                } else {
                    $logger->info("Task $id ($time seconds): Successful call to $url: "
                        . $result['status'] . '-' . $result['reason']);
                }
                return $result;
            }
        );
        $message = "Task $id: Deffered sending HTTP Request to $url";
        return new TaskResult($id, $this->remoteId, $message, $promise, []);
    }
}
