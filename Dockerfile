FROM php:8.2-cli

RUN docker-php-ext-install pdo pdo_mysql

WORKDIR /app
COPY . /app
RUN chmod +x start.sh

CMD ["sh", "start.sh"]