#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

podman run --rm -dt -v $DIR:/app -p 8888:80 webdevops/php-apache
