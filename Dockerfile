FROM composer:latest

COPY . /cinderella
WORKDIR /cinderella
RUN composer install
RUN chmod +x /cinderella/bin/cinderella

ENV CINDERELLA_SCHEDULE_URL="file:///cinderella/example_schedule.json"

ENTRYPOINT ["/cinderella/bin/cinderella"]