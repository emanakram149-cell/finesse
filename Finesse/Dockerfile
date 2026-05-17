FROM php:8.2-apache

# MySQL + Apache rewrite; mod_php requires prefork (only one MPM may load)
RUN docker-php-ext-install pdo_mysql \
    && a2dismod mpm_event mpm_worker 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# Allow .htaccess overrides site-wide
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p /var/www/html/frontend/assets/uploads \
    && chown -R www-data:www-data /var/www/html/frontend/assets/uploads \
    && chmod -R 775 /var/www/html/frontend/assets/uploads

RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf

COPY docker/start.sh /usr/local/bin/start.sh
RUN sed -i 's/\r$//' /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]
