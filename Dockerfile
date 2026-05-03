FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY . /var/www/html/

RUN echo "Listen \${PORT}" > /etc/apache2/ports.conf && \
    echo "<VirtualHost *:\${PORT}>\n    DocumentRoot /var/www/html\n    <Directory /var/www/html>\n        AllowOverride All\n        Require all granted\n    </Directory>\n</VirtualHost>" > /etc/apache2/sites-enabled/000-default.conf

CMD ["apache2-foreground"]