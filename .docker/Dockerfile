FROM php:8.4-cli

ARG UID=1000
ARG GID=1000

RUN apt-get update && \
    apt-get install -y git default-jre-headless

WORKDIR /var/www

ADD https://api.github.com/repos/php/phd/git/refs/heads/master version-phd.json
ADD https://api.github.com/repos/php/docbook-cs/git/refs/heads/main version-docbook-cs.json

RUN echo 'memory_limit = 512M' >> /usr/local/etc/php/conf.d/local.ini

RUN chown $UID:$GID /var/www

USER $UID:$GID

RUN git clone --depth 1 https://github.com/php/phd.git && \
    git clone --depth 1 https://github.com/php/docbook-cs.git
