<?php
file_put_contents('resolve.log', print_r($_SERVER, TRUE) . print_r(json_decode(file_get_contents('php://input'), TRUE), TRUE), FILE_APPEND);
print '{"status":"true"}';
