#!/bin/bash

time=$((10 + $(date +%s)))

curl \
  -v \
  --data '{id":"schedule-test-id","tasks":[
	{
		"type":"schedule",
		"time":'$time',
		"tasks":[
			{"type":"pick_lentils","id":"out of the fireplace","lentils":5}
		]
	}]}' \
  http://localhost:10101/task
