FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app
COPY . /app

CMD php -d extension=pdo_mysql -S 0.0.0.0:$PORT -t /app