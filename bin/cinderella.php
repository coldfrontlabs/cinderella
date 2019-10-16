<?php
require \dirname(__DIR__) . '/vendor/autoload.php';
define("APP_ROOT", dirname(__FILE__));

use Cinderella\Cinderella;
use Symfony\Component\Yaml\Yaml;

$config = FALSE;

if (isset($argv[1])) {
  $config = Yaml::parseFile($argv[1]);
}
if (!$config) {
  $config = Cinderella::defaultConfig();
  if (isset($_ENV["CINDERELLA_LISTEN"])) {
    $config['listen'] = $_ENV["CINDERELLA_LISTEN"];
  }

  if (isset($_ENV["CINDERELLA_SCHEDULE_URL"])) {
    $config['schedule']['master']['url'] = $_ENV["CINDERELLA_SCHEDULE_URL"];
  }
}

$server = new Cinderella($config);
