#!/usr/bin/env bash

# Exit on error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0;3m' # No Color
CLEAR='\033[0m'

echo -e "${GREEN}=====================================================${CLEAR}"
echo -e "${GREEN}      SolidTime SMMA Proxmox LXC Installer           ${CLEAR}"
echo -e "${GREEN}=====================================================${CLEAR}"

# Verify we are running as root
if [ "$EUID" -ne 0 ]; then
  echo -e "${RED}Error: Please run this script as root (sudo bash script.sh)${CLEAR}"
  exit 1
fi

# Gather configuration inputs
echo -e "${YELLOW}--- Configuration Setup ---${CLEAR}"
read -p "Enter your custom domain name (e.g. solidtime.mycompany.com): " DOMAIN
while [ -z "$DOMAIN" ]; do
  read -p "Domain cannot be empty. Please enter your domain: " DOMAIN
done

read -p "Enter your Cloudflare API Token (for DNS challenge): " CF_TOKEN
while [ -z "$CF_TOKEN" ]; do
  read -p "Cloudflare API Token cannot be empty. Please enter the token: " CF_TOKEN
done

# Generate a random database password
DB_PASS=$(openssl rand -hex 16)
echo -e "${GREEN}Database password generated securely.${CLEAR}"

echo -e "\n${YELLOW}--- Installation starting in 5 seconds... ---${CLEAR}"
sleep 5

# 1. Update OS and Install Prerequisites
echo -e "\n${YELLOW}[1/10] Updating packages and installing prerequisites...${CLEAR}"
apt update && apt install -y curl sudo git unzip lsb-release ca-certificates gnupg build-essential

# 2. Add Repositories (PHP, Node, PostgreSQL)
echo -e "\n${YELLOW}[2/10] Setting up software repositories...${CLEAR}"

# PHP Sury repository
curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list

# NodeSource Node.js 22 repository
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -

# PostgreSQL repository
curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg
echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt $(lsb_release -sc)-pgdg main" > /etc/apt/sources.list.d/pgdg.list

# 3. Install core packages
echo -e "\n${YELLOW}[3/10] Installing PHP 8.3, PostgreSQL 16, Node.js 22, and Caddy...${CLEAR}"
apt update
apt install -y php8.3 php8.3-fpm php8.3-bcmath php8.3-gd php8.3-intl php8.3-xml php8.3-zip php8.3-pdo-pgsql php8.3-redis php8.3-mbstring php8.3-curl nodejs postgresql-16 caddy

# Install Composer globally
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# 4. Replace Caddy with Cloudflare DNS challenge enabled binary
echo -e "\n${YELLOW}[4/10] Compiling/downloading custom Caddy with Cloudflare DNS plugin...${CLEAR}"
systemctl stop caddy
curl -L -o /usr/bin/caddy "https://caddyserver.com/api/download?os=linux&arch=amd64&p=github.com/caddy-dns/cloudflare"
chmod +x /usr/bin/caddy

# 5. Database Setup
echo -e "\n${YELLOW}[5/10] Configuring PostgreSQL database...${CLEAR}"
systemctl start postgresql
systemctl enable postgresql

sudo -u postgres psql -c "CREATE DATABASE solidtime;" || true
sudo -u postgres psql -c "CREATE USER solidtime WITH PASSWORD '${DB_PASS}';" || true
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE solidtime TO solidtime;"
sudo -u postgres psql -c "ALTER DATABASE solidtime OWNER TO solidtime;"
sudo -u postgres psql -d solidtime -c "GRANT ALL ON SCHEMA public TO solidtime;"

# 6. Deploy Custom Source Code
echo -e "\n${YELLOW}[6/10] Cloning solidtime-smma custom repository (branch: SMMA-Modified)...${CLEAR}"
rm -rf /opt/solidtime
git clone -b SMMA-Modified https://github.com/armadeas/solidtime-smma.git /opt/solidtime
cd /opt/solidtime

# 7. Configure Environment variables (.env)
echo -e "\n${YELLOW}[7/10] Creating and updating .env file...${CLEAR}"
cp .env.example .env

sed -i "s|^APP_ENV=.*|APP_ENV=production|" .env
sed -i "s|^APP_DEBUG=.*|APP_DEBUG=false|" .env
sed -i "s|^APP_URL=.*|APP_URL=https://${DOMAIN}|" .env
sed -i "s|^APP_ENABLE_REGISTRATION=.*|APP_ENABLE_REGISTRATION=true|" .env
sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=pgsql|" .env
sed -i "s|^DB_HOST=.*|DB_HOST=127.0.0.1|" .env
sed -i "s|^DB_PORT=.*|DB_PORT=5432|" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=solidtime|" .env
sed -i "s|^DB_USERNAME=.*|DB_USERNAME=solidtime|" .env
sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|^FILESYSTEM_DISK=.*|FILESYSTEM_DISK=local|" .env
sed -i "s|^PUBLIC_FILESYSTEM_DISK=.*|PUBLIC_FILESYSTEM_DISK=public|" .env
sed -i "s|^MAIL_MAILER=.*|MAIL_MAILER=log|" .env

# Handle secure cookie and force HTTPS configurations
sed -i "s|^SESSION_SECURE_COOKIE=.*|SESSION_SECURE_COOKIE=true|" .env
grep -q "^SESSION_SECURE_COOKIE=" .env || echo "SESSION_SECURE_COOKIE=true" >>.env
sed -i "s|^APP_FORCE_HTTPS=.*|APP_FORCE_HTTPS=true|" .env
grep -q "^APP_FORCE_HTTPS=" .env || echo "APP_FORCE_HTTPS=true" >>.env

# 8. Build Backend and Frontend dependencies
echo -e "\n${YELLOW}[8/10] Installing Composer and NPM packages & building frontend...${CLEAR}"
composer install --no-dev --optimize-autoloader

# Generate secure app keys
php artisan self-host:generate-keys >/tmp/solidtime.keys 2>/dev/null
while IFS= read -r line; do
  KEY="${line%%=*}"
  [[ -z "$KEY" || "${KEY:0:1}" == "#" ]] && continue
  sed -i "/^${KEY}=/d" .env
  echo "$line" >>.env
done </tmp/solidtime.keys
rm -f /tmp/solidtime.keys

npm install
npm run build
rm -rf node_modules

# Ensure proper logs and storage structure
mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chown -R www-data:www-data /opt/solidtime
chmod -R 775 storage bootstrap/cache

# 9. Initialize Database and Passport
echo -e "\n${YELLOW}[9/10] Initializing database schemas and passport clients...${CLEAR}"
php artisan storage:link
php artisan migrate --force
php artisan passport:client --personal --name="API" -n
php artisan optimize:clear

# 10. Configure Caddy with Cloudflare DNS challenge
echo -e "\n${YELLOW}[10/10] Configuring Caddy Server with Cloudflare DNS challenge...${CLEAR}"

# Create Systemd override directory for Caddy
mkdir -p /etc/systemd/system/caddy.service.d

# Write systemd environment override
cat <<EOF >/etc/systemd/system/caddy.service.d/override.conf
[Service]
Environment="CLOUDFLARE_API_TOKEN=${CF_TOKEN}"
EOF

# Write Caddyfile
cat <<EOF >/etc/caddy/Caddyfile
${DOMAIN} {
    tls {
        dns cloudflare {env.CLOUDFLARE_API_TOKEN}
    }

    root * /opt/solidtime/public
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
    encode gzip
}
EOF

# Grant Caddy access to www-data group (for fastcgi socket access)
usermod -aG www-data caddy

# Reload systemd configuration and start services
systemctl daemon-reload
systemctl enable --now php8.3-fpm
systemctl restart caddy
systemctl restart php8.3-fpm

echo -e "\n${GREEN}=====================================================${CLEAR}"
echo -e "${GREEN}      Installation Completed Successfully!           ${CLEAR}"
echo -e "${GREEN}=====================================================${CLEAR}"
echo -e "You can now access your application via: ${YELLOW}https://${DOMAIN}${CLEAR}"
echo -e "Make sure your local DNS points ${YELLOW}${DOMAIN}${CLEAR} to this container's IP."
