#!/bin/bash

curl \
  -v \
  --data '{"type":"queued_task","task":{"type":"http_request","id":"status","url":"http://localhost:10101/status","timeout":5}}' \
  http://localhost:10101/task
