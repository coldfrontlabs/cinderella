# Cinderella

Cinderella is an easy to configure asynchronous background job running daemon that you trigger over HTTP.

## Why would I use this

PHP web applications don't generally live themselves are resident services, and thus have trouble managing long running background jobs.
Generally the solution is to use cron, however the restriction becomes that you can't trigger the jobs to run immediately.

Cinderella is a local service for excuting the background jobs.  You signal the job you'd like her to run via HTTP, and then you carry on.

Cinderella stays back at on the server, doing the long running background processes like picking the lentils out of the fireplace, while you app goes to the ball.

## Getting Started

### Prerequisites

- PHP 7.0 or greater
- Composer

### Install Steps

1. Git clone the master branch
2. Run ```$ composer install```

### Running

1. Define your tasks in a yaml file, like 'config.yaml'
2. Run cinderella ```$ ./bin/cinderella config.yaml```

## Configuration

Cinderella accepts and requires exactly one argument, the filename of file containing it's YAML configuration.

The configuration file defines the entirety of Cinderella's runtime.

## Deployment

Not yet ready for production, but should run nicely in a container.

## Built With

* [PHP](http://php.net/) - Language
* [AMP](https://amphp.org/) - HTTP Server and asynchronous process manager