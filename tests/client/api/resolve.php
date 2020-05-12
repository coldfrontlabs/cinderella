<?php
file_put_contents('resolve.log', print_r($_SERVER, TRUE) . file_get_contents('php://input'), FILE_APPEND);
print '{"status":"true"}';
