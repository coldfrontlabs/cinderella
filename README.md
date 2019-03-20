# Cinderella

Cinderella is an easy to configure asynchronous job/task running daemon that you trigger over HTTP.

## Getting Started

1. Git clone the master branch
1. Run ```$ composer install``
1. Define your tasks in a yaml file, like 'config.yaml'
1. Run cinderella ```$ ./bin/cinderella config.yaml```

### Prerequisites

- PHP 7.0 or greater
- Composer

## Deployment

Not yet ready for production, but should run nicely in a container.

## Built With

* [PHP](http://php.net/) - Language
* [AMP](https://amphp.org/) - HTTP Server and asynchronous process manager

