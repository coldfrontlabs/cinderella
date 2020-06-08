#!/bin/bash

curl \
  -v \
  --data '{
    "type":"task_runner",
    "tasks":[
      {"type":"http_request","id":"pdf","url":"http://localhost:8000/?url=http://www.google.com","timeout":60}
      ],
    "resolve":{"type":"http_request","id":"resolve","url":"http://localhost:8888/api/resolve.php"}
  }' \
  http://localhost:10101/task
