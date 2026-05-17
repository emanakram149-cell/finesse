#!/bin/bash
set -e

# Railway/php-apache: ensure only prefork MPM is loaded (mod_php requirement)
a2dismod mpm_event mpm_worker 2>/dev/null || true
a2enmod mpm_prefork 2>/dev/null || true

PORT="${PORT:-80}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-enabled/000-default.conf 2>/dev/null || true

exec apache2-foreground
