<?php

namespace Cinderella\Task;

use Amp\Artax\DefaultClient;
use Amp\Artax\Client;
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
        $starttime = microtime(true);

        $this->options = array_merge_recursive($this->options, $options);
        $url = $this->options['url'];
        $method = $this->options['method'] ?? 'GET';
        $timeout = isset($this->options['timeout']) && is_int($this->options['timeout']) ? $this->options['timeout'] : 15;
        $body = $this->options['body'] ?? null;
        $headers = $this->options['headers'] ?? [];
        $id = $this->getId();
        $remoteid = $this->options['id'];
        $logger = $this->cinderella->getLogger();
        $promise = \Amp\call(function () use ($client, $url, $method, $body, $headers, $id, $remoteid, $logger, $timeout, $starttime) {
            $request = new Request($url, $method);
            if (isset($body)) {
                if (is_array($body)) {
                    $body = json_encode($body);
                }
                $request = $request->withHeader('Content-type', 'application/json');
                $request = $request->withBody($body);
            }

            foreach ($headers as $header => $value) {
                $request = $request->withHeader($header, $value);
            }

            $client->setOption(Client::OP_TRANSFER_TIMEOUT, $timeout * 1000);
            $timing = [
                'start' => $starttime,
            ];

            try {
                $response = yield $client->request($request);
                $body = yield $response->getBody();

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
