<?php
require \dirname(__DIR__) . '/vendor/autoload.php';
define("APP_ROOT", dirname(__FILE__));

use Amp\Loop;
use Cinderella\Cinderella;
use Symfony\Component\Yaml\Yaml;
use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\ByteStream\ResourceOutputStream;
use Monolog\Logger;
use Psr\Log\LogLevel;

$config = FALSE;

if (isset($argv[1])) {
  $config = Yaml::parseFile($argv[1]);
}
if (!$config) {
  $config = Cinderella::defaultConfig();
  if (isset($_ENV["CINDERELLA_LISTEN"])) {
    $config['listen'] = [$_ENV["CINDERELLA_LISTEN"]];
  }

  if (isset($_ENV["CINDERELLA_SCHEDULE_URL"])) {
    $config['schedule']['master']['url'] = $_ENV["CINDERELLA_SCHEDULE_URL"];
  }
}

$logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT),  LogLevel::DEBUG);
$logHandler->setFormatter(new ConsoleFormatter);
$logger = new Logger('cinderella');
$logger->pushHandler($logHandler);

Loop::setErrorHandler(function (\Throwable $e) use ($logger) {
  $logger->warning($e->getMessage());
});

$server = new Cinderella($config, $logger);
