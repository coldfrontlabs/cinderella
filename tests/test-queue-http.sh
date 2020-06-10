#!/bin/bash

curl \
  -v \
  --data '{
    "type":"queued_task",
    "task":{
      "type":"task_runner",
      "tasks":[{"type":"http_request","id":"request","url":"https://github.com/coldfrontlabs/cinderella","timeout":5}],
      "resolve":{"type":"http_request","method":"POST","id":"resolve","url":"http://localhost:8888/api/resolve.php","timeout":5}
    }
  }' \
  http://localhost:10101/task
