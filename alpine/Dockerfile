FROM php:7.3-fpm-alpine3.12

RUN apk --no-cache add tini git openssh-client postgresql-dev gzip \
    && apk --no-cache add --virtual devs tar curl

#Install Caddy Server, and All Middleware
RUN wget -c https://github.com/caddyserver/caddy/releases/download/v2.1.1/caddy_2.1.1_linux_amd64.tar.gz -O - | tar -C /usr/bin/ -xz caddy 

#install PHP
RUN docker-php-source extract && \
  apk add --update --no-cache autoconf g++ make && \
  docker-php-ext-install \
  pcntl \ 
  pdo \ 
  pdo_pgsql \
  pgsql && \
  docker-php-source delete

RUN yes | pecl install xdebug-2.7.2 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini 

#Remove build devs
RUN apk del devs

#Copy over a default Caddyfile
COPY ./Caddyfile /etc/Caddyfile

#USER caddy

ENTRYPOINT ["/sbin/tini"]

CMD ["caddy", "-quic", "--conf", "/etc/Caddyfile"]