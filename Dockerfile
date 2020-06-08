FROM composer:latest

COPY . /cinderella
WORKDIR /cinderella
RUN composer install
RUN composer clear-cache
RUN chmod +x /cinderella/bin/cinderella

ENTRYPOINT ["/cinderella/bin/cinderella"]