#!/usr/bin/env bash
set -Eeuo pipefail

# Configuration personnalisable :
#   APP_DIR=/var/www/workshop SERVER_NAME=example.org ./setup_debian13.sh
APP_DIR="${APP_DIR:-/var/www/html}"
SERVER_NAME="${SERVER_NAME:-_}"
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VHOST_NAME="000-default.conf"
VHOST_PATH="/etc/apache2/sites-available/${VHOST_NAME}"

if [[ "${EUID}" -ne 0 ]]; then
    echo "Erreur : ce script doit être exécuté avec sudo ou en root." >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

echo "[1/7] Installation des paquets Apache et PHP..."
apt-get update
apt-get install -y \
    apache2 \
    php \
    php-cli \
    libapache2-mod-php \
    php-pgsql \
    php-mbstring \
    php-xml \
    php-curl \
    unzip \
    git

if ! php -r 'exit(extension_loaded("pdo_pgsql") ? 0 : 1);'; then
    echo "Erreur : l'extension PHP PDO PostgreSQL n'est pas chargee." >&2
    echo "Le paquet php-pgsql est requis pour la connexion a Supabase." >&2
    exit 1
fi

echo "[2/7] Activation des modules Apache..."
PHP_MODULE="php$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')"
a2enmod "${PHP_MODULE}"
a2enmod dir

if [[ ! -d "${PROJECT_ROOT}/public" ]]; then
    echo "Erreur : le dossier public est introuvable dans ${PROJECT_ROOT}." >&2
    exit 1
fi

echo "[3/7] Deploiement du projet dans ${APP_DIR}..."
install -d -o www-data -g www-data -m 755 "${APP_DIR}"

if [[ "${PROJECT_ROOT}" != "${APP_DIR}" ]]; then
    cp -a "${PROJECT_ROOT}/." "${APP_DIR}/"
fi

if [[ ! -f "${APP_DIR}/.env" ]]; then
    echo "Attention : ${APP_DIR}/.env est absent. La connexion Supabase ne fonctionnera pas encore." >&2
fi

echo "[4/7] Configuration du VirtualHost Apache..."
cat > "${VHOST_PATH}" <<EOF
<VirtualHost *:80>
    DocumentRoot ${APP_DIR}/public

    <Directory ${APP_DIR}/public>
        AllowOverride None
        Options -Indexes +FollowSymLinks
        DirectoryIndex index.php
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF

a2dissite 000-default.conf 2>/dev/null || true
a2ensite "${VHOST_NAME}"

# Le serveur web doit pouvoir lire l'application, mais pas modifier sa configuration.
echo "[5/7] Reglage des permissions..."
chown -R www-data:www-data "${APP_DIR}"
find "${APP_DIR}" -type d -exec chmod 755 {} +
find "${APP_DIR}" -type f -exec chmod 644 {} +

if [[ -f "${APP_DIR}/.env" ]]; then
    chmod 640 "${APP_DIR}/.env"
fi

echo "[6/7] Verification de la configuration Apache..."
apache2ctl configtest

echo "[7/7] Demarrage et activation d'Apache..."
systemctl enable apache2
systemctl restart apache2

echo
echo "Installation terminee."
echo "URL : http://${SERVER_NAME}"
echo "DocumentRoot : ${APP_DIR}/public"
echo "Supabase : verifiez les variables DB_* dans ${APP_DIR}/.env"
echo "Test PHP : echo '<?php phpinfo();' > ${APP_DIR}/public/info.php (a supprimer ensuite)"
