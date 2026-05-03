FROM dunglas/frankenphp:php8.4

RUN install-php-extensions pdo_mysql

ENV FRANKENPHP_CONFIG="worker ./index.php"
ENV SERVER_NAME=http://