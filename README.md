# Cinderella

Cinderella is an easy to configure asynchronous background and scheduled job running daemon that you trigger over HTTP. It's purpose it to support a primary application.

## Features

- Background tasks: Cinderella will run tasks in the background without requiring the application to block.
- Asynchronous tasks: Cinderella can run multiple tasks at the same time so that the main application it's supporting doesn't this logic.
- Task scheduling: Tasks can be scheduling in Cinderella and will run within ~50 milliseconds of the specified time.
- Task queueing: Cinderella can be used to queue background tasks, so that they run one at a time in the background.

## Motivation

PHP web applications don't generally live as resident services.  Their life time end when the HTTP request is satisfied.  Thus they shouldn't manage long running background jobs.
Generally the solution is to use cron to manage background jobs, however this restricts when the jobs can be triggered, and means that jobs can't be scheduled to run immediately.

Cinderella is a local service for excuting the background jobs.  You signal the job you'd like her to run via HTTP, and then you carry on.

Cinderella stays back at on the server, doing the long running background processes like picking the lentils out of the fireplace, while your app goes to the ball.

## Tasks

Tasks are predefined pieces of work that are configurable by passing them options. Work for tasks is done immediately or is deferred and a return a promise to do the work instead.

Tasks are attached to endpoints in cinderella (I.E. POST/GET request to configured endpoint will execute give tasks). How to configure endpoint is covered below in the **Configuration** section.

All tasks accept the baseline argument "id". This can be set to any any string value and is kept with that task so that you can reconize it's result later.

### Types of tasks

#### Pick lentils from the fireplace (pick_lentils)

This is a demonastration task.

It accepts the argument `lentils`, and returns a promise the this background task will take `lentils` seconds to complete.

After `lentils` seconds, the task resolves.

** Arguments: **
- `id`: String to identify this task.
- `lentils`: Number of seconds to run for.

#### Try on slipper (try_on_slipper)

This is a demonastration task.

It runs immediately and returns a string.

** Arguments: **
- `id`: String to identify this task.

#### HTTP Request (http_request)

This task returns a promise to run a HTTP request as defined in the options.

** Arguments: **
- `id`: String to identify this task.
- `method`: Either 'GET' or 'POST', controls the HTTP request method.
- `body`: HTTP body to include with the HTTP request
- `headers`: An array/object of keys => values that form the HTTP headers
- `timeout`: HTTP request timeout in seconds (default's to `15`)
- `url`: The URL to make the HTTP request to.

#### Status (status)

This task immediately returns the status of the tasks that are running or pending in Cinderella.

** Arguments: **
- `id`: String to identify this task.

#### Schedule refresh (schedule_refresh)

This task returns a promise to refresh all of the task schedules.

** Arguments: **
- `id`: String to identify this task.

#### Task runner (task_runner)

A task runner task run multiple tasks at the same time. This task lets you run other tasks at the same time in one request.

It also has an optional option `resolve`, which can hold an additional task that is run after all of the tasks have finished.

** Arguments: **
- `id`: String to identify this task.
- `tasks`: An array of tasks to run asynchronously.
- `resolve`: A task to run after all of the tasks have finished.

#### Queued Task (queued_task)

This task as an option named `task` and adds to a named queues in `queue`. Each queue will run one task at a time. Once the given task has finished, a optional task in the `resolve` option is run.  The `resolve` task is not queue and will run at the same time as the next task in the queue if there is one.

** Arguments: **
- `id`: String to identify this task.
- `queue`: The name of the queue this task should be added to.
- `task`: The task that should be run.
- `resolve`: A task to run after the task has finished.

## Configuration

### Default configuration

### Setting configuration


## Getting Started

### Prerequisites

- PHP 7.0 or greater
- Composer

### Install Steps

1. Git clone the master branch
2. Run ```$ composer install```

### Running

Simply run `$ ./bin/cinderella` to start Cinderella with the default configurations.

Or if you've defined a configuration:

1. Define your tasks in a yaml file, like 'config.yaml'
2. Run cinderella `$ ./bin/cinderella config.yaml`

## Configuration

Cinderella accepts and requires exactly one argument, the filename of file containing it's YAML configuration.

The configuration file defines the entirety of Cinderella's runtime.

## Deployment

### Container

#### Running

```podman run --rm -d coldfrontlabs/cinderella:latest```

##### With a task schedule

```podman run --rm -d -e CINDERELLA_SCHEDULE_URL=http://url.to/my/schedule coldfrontlabs/cinderella:latest```

## Caveats

Cinderella is still under development and a configuration validation is still missing.

## Built With

* [PHP](http://php.net/) - Language
* [AMP](https://amphp.org/) - HTTP Server and asynchronous process manager

## Coding Standard

PSR2