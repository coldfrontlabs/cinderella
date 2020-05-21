#!/bin/bash

curl \
  -v \
  -X POST \
  --data '{"type":"task_runner","tasks":[
    {"type":"http_request","id":"sucessful task with return data","url":"http://localhost:8888/api/task.php","timeout":1},
    {"type":"http_request","id":"slow but we waited","url":"http://localhost:8888/api/slow-task.php","timeout":6},
    {"type":"http_request","id":"slow but we did not wait","url":"http://localhost:8888/api/slow-task.php","timeout":1},
    {"type":"http_request","id":"200 code","url":"http://localhost:8888/api/httpcode-task.php?code=200","timeout":1},
    {"type":"http_request","id":"301 code","url":"http://localhost:8888/api/httpcode-task.php?code=301","timeout":1},
    {"type":"http_request","id":"302 code","url":"http://localhost:8888/api/httpcode-task.php?code=302","timeout":1},
    {"type":"http_request","id":"400 error","url":"http://localhost:8888/api/httpcode-task.php?code=400","timeout":1},
    {"type":"http_request","id":"401 error","url":"http://localhost:8888/api/httpcode-task.php?code=401","timeout":1},
    {"type":"http_request","id":"403 error","url":"http://localhost:8888/api/httpcode-task.php?code=403","timeout":1},
    {"type":"http_request","id":"404 error","url":"http://localhost:8888/api/httpcode-task.php?code=404","timeout":1},
    {"type":"http_request","id":"410 error","url":"http://localhost:8888/api/httpcode-task.php?code=410","timeout":1},
    {"type":"http_request","id":"500 error","url":"http://localhost:8888/api/httpcode-task.php?code=500","timeout":1},
    {"type":"http_request","id":"502 error","url":"http://localhost:8888/api/httpcode-task.php?code=502","timeout":1},
    {"type":"http_request","id":"503 error","url":"http://localhost:8888/api/httpcode-task.php?code=503","timeout":1}
  ],"resolve":{"type":"http_request","id":"first:task","url":"http://localhost:8888/api/resolve.php"}}' \
  http://localhost:10101/task