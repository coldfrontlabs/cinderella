#!/bin/bash

curl \
  -v \
  --data '{"type":"queued_task","task":{"type":"pick_lentils","id":"First task","time":5}}' \
  http://localhost:10101/task