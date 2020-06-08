#!/bin/bash

curl \
  -v \
  --data '{
    "type":"task_runner",
    "tasks":[
      {"type":"http_request","id":"pdf","url":"http://localhost:8888/api/super-slow-task.php","timeout":60}
      ]
  }' \
  http://localhost:10101/task
