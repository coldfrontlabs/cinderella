<?php

if (
  isset($_GET['code'])
  && is_numeric($_GET['code'])
  && $_GET['code'] >= 100
  && $_GET['code'] <= 505
) {
  $code = $_GET['code'];
} else {
  $code = 200;
}

http_response_code($code);

print json_encode(['result' => $code]);