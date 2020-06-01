#!/bin/bash

curl \
  -v \
  --data '{
    "type":"queued_task",
    "task":{"type":"pick_lentils","id":"First task","time":5},
    "resolve":{"type":"pick_lentils","id":"Resolve task from first task","time":5}
  }' \
  http://localhost:10101/task

curl \
  -v \
  --data '{"type":"queued_task","task":{"type":"pick_lentils","id":"Second task","time":5}}' \
  http://localhost:10101/task

curl \
  -v \
  --data '{"type":"queued_task","task":{"type":"pick_lentils","id":"Third task","time":5}}' \
  http://localhost:10101/task

curl \
  -v \
  --data '{"type":"queued_task","task":{"type":"pick_lentils","id":"Fourth task","time":5}}' \
  http://localhost:10101/task