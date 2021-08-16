<?php

namespace Cinderella\Task;

use Amp\CallableMaker;
use Amp\Deferred;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\Loop;
use Cinderella\Cinderella;

class HttpRequest extends Task
{
    use CallableMaker;
    private $deferred;
    private $timing;

    public function defaults()
    {
        return [
            'body' => [],
            'headers' => [],
            'id' => null,
            'method' => 'GET',
            'timeout' => 15,
            'maxsize' => 10 * 1024 * 1024,
            'url' => null,
        ];
    }

    public function run(): TaskResult
    {
        $this->timing = [
            'start' => microtime(true),
        ];

        $this->deferred = new Deferred();
        Loop::defer($this->callableFromInstanceMethod('request'));
        $this
            ->cinderella
            ->getLogger()
            ->info(
                "{$this->getLoggingName()}: Deffered sending HTTP Request to {$this->options['url']}"
            );
        return new TaskResult(
            $this->getId(),
            $this->getRemoteId(),
            "",
            $this->deferred->promise()
        );
    }

    public function getLoggingName() {
        return parent::getLoggingName() . ':' . $this->options['method'] . '-' . $this->options['url'];
    }

    private function request()
    {
        $request = new Request($this->options['url'], $this->options['method']);
        if (isset($this->options['body']) and !empty($this->options['body'])) {
            $body = $this->options['body'];
            if (is_array($body)) {
                $body = json_encode($body);
            }
            $request->setHeader('Content-type', 'application/json');
            $request->setBody($body);
        }

        foreach ($this->options['headers'] as $header => $value) {
            $request->setHeader($header, $value);
        }

        if (is_numeric($this->options['maxsize'])) {
            $request->setBodySizeLimit((int)$this->options['maxsize']);
        }

        $timeout = $this->options['timeout'];
        $request->setInactivityTimeout($timeout * 1000);
        $request->setTransferTimeout($timeout * 1000);
        $request->setTcpConnectTimeout($timeout * 1000);
        $request->setTlsHandshakeTimeout($timeout * 1000);
        $client = HttpClientBuilder::buildDefault();

        $error = false;

        try {
            $response = yield $client->request($request);
            $this
                ->cinderella
                ->getLogger()
                ->debug(
                    "{$this->getLoggingName()}: Made HTTP {$this->options['method']} request to {$this->options['url']}"
                );
            $body = yield $response->getBody()->buffer();
            $this
                ->cinderella
                ->getLogger()
                ->debug(
                    "{$this->getLoggingName()}:"
                    . "Buffered body from {$this->options['method']} request to {$this->options['url']}"
                );
        } catch (\Throwable $exception) {
            $error = $exception->getMessage();
        }
        $endtime = microtime(true);
        $this->timing['end'] = $endtime;
        $this->timing['duration'] = $endtime - $this->timing['start'];

        $ret = [
            'id' => $this->getId(),
            'remoteid' => $this->options['id'],
            'url' => $this->options['url'],
            'method' => $this->options['method'],
            'time' => $this->timing,
        ];

        if ($error) {
            $ret['status'] = 'error';
            $ret['reason'] = $error;
            $this->cinderella->getLogger()->info("{$this->getLoggingName()}: An error occured: $error");
        } else {
            $this
                ->cinderella
                ->getLogger()
                ->info(
                    "{$this->getLoggingName()}: Successful call to {$this->options['url']}: "
                    . $response->getStatus()
                    . '-'
                    . $response->getReason()
                );
            $ret['status'] = $response->getStatus();
            $ret['reason'] = $response->getReason();
            if ($json = json_decode($body)) {
                $ret['body'] = $body;
            } else {
                $ret['body'] = base64_encode($body);
            }
        }
        $this->deferred->resolve($ret);
    }
}
