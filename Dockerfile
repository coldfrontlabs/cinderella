FROM composer:latest

COPY . /app
RUN (cd /app && composer install)

ENV CINDERELLA_LISTEN="0.0.0.0:10101"
ENV CINDERELLA_SCHEDULE_URL="file:///app/example_schedule.json"

ENTRYPOINT ["/app/bin/cinderella"]