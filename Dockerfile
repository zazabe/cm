FROM cargomedia/cm-application:v1

WORKDIR /app/cm

RUN apt-get update && apt-get install -y netcat curl redis-tools mysql-client

ADD composer.json /app/cm/composer.json
RUN composer up

ADD phpunit.xml /app/cm/phpunit.xml
ADD bin /app/cm/bin
ADD layout /app/cm/layout
ADD resources /app/cm/resources
ADD tests /app/cm/tests
ADD client-vendor /app/cm/client-vendor
ADD library /app/cm/library

ADD ci/run.sh ci/app-wait-services.sh ci/app-setup.sh ci/test.sh /app/cm/