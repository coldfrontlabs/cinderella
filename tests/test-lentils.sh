#!/bin/bash

curl \
  -v \
  --data '{"id":"test-id","tasks":[{"type":"pick_lentils","id":"out of the fireplace","lentils":5}]}' \
  http://localhost:10101/task