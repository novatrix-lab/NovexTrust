#!/usr/bin/env bash
#
# provision.sh — one-time setup for a fresh Ubuntu 22.04 LTS server.
#
# Installs the full ComplyGCC stack: PHP 8.4 (via ondrej/php — 22.04 ships 8.1),
# MySQL 8, Redis, Nginx, Supervisor, Composer, Node 20, and Certbot.
#
# Run as root (or with sudo) on the server:
#   sudo APP_DOMAIN=app.example.com DB_PASSWORD='change-me' bash provision.sh
#
set -euo pipefail

APP_DOMAIN="${APP_DOMAIN:-app.example.com}"
APP_DIR="${APP_DIR:-/var/www/complygcc}"
DB_NAME="${DB_NAME:-complygcc}"
DB_USER="${DB_USER:-complygcc}"
DB_PASSWORD="${DB_PASSWORD:-}"
PHP_VERSION="8.4"

if [[ "${EUID}" -ne 0 ]]; then
  echo "Please run as root (sudo)." >&2
  exit 1
fi

if [[ -z "${DB_PASSWORD}" ]]; then
  echo "Set DB_PASSWORD (the MySQL app-user password)." >&2
  exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Base packages"
apt-get update -y
apt-get install -y software-properties-common curl git unzip ca-certificates gnupg lsb-release

echo "==> Swap (prevents OOM during composer/npm/MySQL on small instances)"
if ! swapon --show | grep -q .; then
  fallocate -l 2G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=2048
  chmod 600 /swapfile
  mkswap /swapfile
  swapon /swapfile
  grep -q '/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "==> PHP ${PHP_VERSION} (ondrej/php PPA — Ubuntu 22.04 default is 8.1)"
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
  php${PHP_VERSION}-fpm php${PHP_VERSION}-cli \
  php${PHP_VERSION}-mysql php${PHP_VERSION}-redis \
  php${PHP_VERSION}-mbstring php${PHP_VERSION}-xml php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip php${PHP_VERSION}-gd php${PHP_VERSION}-intl \
  php${PHP_VERSION}-bcmath php${PHP_VERSION}-readline

echo "==> MySQL 8, Redis, Nginx, Supervisor"
apt-get install -y mysql-server redis-server nginx supervisor

echo "==> Composer"
php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
EXPECTED="$(curl -s https://composer.github.io/installer.sig)"
ACTUAL="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"
if [[ "${EXPECTED}" != "${ACTUAL}" ]]; then
  echo "Composer installer checksum mismatch — aborting." >&2
  rm -f /tmp/composer-setup.php
  exit 1
fi
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php

echo "==> Node 20 (for building front-end assets)"
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y nodejs

echo "==> Certbot (Let's Encrypt)"
apt-get install -y certbot python3-certbot-nginx

echo "==> Database + app user"
mysql --execute="CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql --execute="CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';"
mysql --execute="GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

echo "==> Application directory + permissions"
DEPLOY_USER="${DEPLOY_USER:-ubuntu}"
mkdir -p "${APP_DIR}"
# App owned by the deploy user (who runs composer/artisan); the web server
# (www-data) is the group and gets write access to the runtime directories.
chown -R "${DEPLOY_USER}:www-data" "${APP_DIR}"
chmod -R ug+rwX "${APP_DIR}/backend/storage" "${APP_DIR}/backend/bootstrap/cache" 2>/dev/null || true

echo "==> Nginx site"
sed "s/APP_DOMAIN/${APP_DOMAIN}/g; s#APP_DIR#${APP_DIR}#g; s/PHP_VERSION/${PHP_VERSION}/g" \
  "$(dirname "$0")/nginx/complygcc.conf" > /etc/nginx/sites-available/complygcc
ln -sf /etc/nginx/sites-available/complygcc /etc/nginx/sites-enabled/complygcc
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo "==> Supervisor (queue workers)"
sed "s#APP_DIR#${APP_DIR}#g; s/PHP_VERSION/${PHP_VERSION}/g" \
  "$(dirname "$0")/supervisor/complygcc-worker.conf" > /etc/supervisor/conf.d/complygcc-worker.conf
supervisorctl reread && supervisorctl update

echo "==> Scheduler cron (runs as www-data)"
( crontab -u www-data -l 2>/dev/null | grep -v 'artisan schedule:run' ; \
  echo "* * * * * cd ${APP_DIR}/backend && php artisan schedule:run >> /dev/null 2>&1" ) \
  | crontab -u www-data -

systemctl enable --now php${PHP_VERSION}-fpm mysql redis-server nginx supervisor

echo
echo "Provisioning complete."
echo "Next:"
echo "  1. Deploy the code into ${APP_DIR} (git clone), then run deploy/deploy.sh"
echo "  2. Configure ${APP_DIR}/backend/.env (copy from deploy/.env.production.example)"
echo "  3. sudo certbot --nginx -d ${APP_DOMAIN}   # enable HTTPS"
