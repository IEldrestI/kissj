FROM php:7.3-apache

# Tools installation

RUN apt-get update && \ 
  apt-get install -y  \
  curl \
  vim \
  git \
  zip \
  libpq-dev

RUN docker-php-ext-install \
  pcntl \ 
  pdo \ 
  pdo_pgsql \
  pgsql
  
RUN yes | pecl install xdebug-2.7.2 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini 
	

# Apache2
RUN a2enmod rewrite



# Composer 
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY ./composer.json /var/www/html/
RUN composer install --no-interaction

# Copy app
COPY ./src /var/www/html/src
COPY ./adminer /var/www/html/adminer
COPY ./bin /var/www/html/bin 
COPY ./docs /var/www/html/docs 
COPY ./logs /var/www/html/logs
COPY ./public /var/www/html/public 
COPY ./tests /var/www/html/tests
COPY ./uploads /var/www/html/uploads
COPY ./.htaccess /var/www/html/.htaccess
RUN touch /var/www/html/.env






