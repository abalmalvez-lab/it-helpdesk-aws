#!/bin/bash
# =============================================================
# IT Helpdesk — Post-Deploy Setup Script
# =============================================================
# Run this AFTER uploading application files to the web root.
# Usage: sudo bash deployment/setup-app.sh
# =============================================================

set -e

echo "IT Helpdesk — Post-Deploy Setup"
echo "================================"

# Detect web root
if [ -d /var/www/html ]; then
    WEBROOT="/var/www/html"
else
    echo "ERROR: Web root not found."
    exit 1
fi

# Detect web user
if id "www-data" &>/dev/null; then
    WEBUSER="www-data"
elif id "apache" &>/dev/null; then
    WEBUSER="apache"
else
    WEBUSER="root"
fi

echo "Web root: $WEBROOT"
echo "Web user: $WEBUSER"

# --- Step 1: Create .env from example ---
if [ ! -f "$WEBROOT/.env" ]; then
    if [ -f "$WEBROOT/.env.example" ]; then
        cp "$WEBROOT/.env.example" "$WEBROOT/.env"
        chmod 640 "$WEBROOT/.env"
        chown $WEBUSER:$WEBUSER "$WEBROOT/.env"
        echo "[OK] Created .env from .env.example"
        echo "     >>> EDIT $WEBROOT/.env with your database and API credentials <<<"
    else
        echo "[WARN] No .env.example found."
    fi
else
    echo "[OK] .env already exists."
fi

# --- Step 2: Set permissions ---
echo "Setting permissions..."
chown -R $WEBUSER:$WEBUSER $WEBROOT
find $WEBROOT -type d -exec chmod 755 {} \;
find $WEBROOT -type f -exec chmod 644 {} \;
chmod 640 "$WEBROOT/.env" 2>/dev/null || true
echo "[OK] Permissions set."

# --- Step 3: Database setup ---
read -p "Set up the database now? (y/n): " SETUP_DB
if [[ "$SETUP_DB" == "y" ]]; then
    read -p "MySQL host (default: localhost): " DB_HOST
    DB_HOST=${DB_HOST:-localhost}
    read -p "MySQL admin user (default: root): " DB_USER
    DB_USER=${DB_USER:-root}
    read -sp "MySQL password: " DB_PASS
    echo ""
    read -p "Database name (default: it_helpdesk): " DB_NAME
    DB_NAME=${DB_NAME:-it_helpdesk}

    echo "Creating database..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null

    echo "Importing schema..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$WEBROOT/database/setup.sql" 2>/dev/null

    echo "Importing seed data..."
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$WEBROOT/database/seed.sql" 2>/dev/null

    # Create application database user
    read -p "Create a dedicated app DB user? (y/n): " CREATE_USER
    if [[ "$CREATE_USER" == "y" ]]; then
        APP_USER="helpdesk_admin"
        APP_PASS=$(openssl rand -base64 16 | tr -dc 'a-zA-Z0-9' | head -c 16)
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "
            CREATE USER IF NOT EXISTS '$APP_USER'@'%' IDENTIFIED BY '$APP_PASS';
            GRANT SELECT, INSERT, UPDATE, DELETE ON \`$DB_NAME\`.* TO '$APP_USER'@'%';
            FLUSH PRIVILEGES;
        " 2>/dev/null
        echo "[OK] Created DB user: $APP_USER"
        echo "     Password: $APP_PASS"
        echo "     >>> Update these in $WEBROOT/.env <<<"
    fi

    echo "[OK] Database imported successfully."
fi

# --- Step 4: Fix password hashes ---
read -p "Generate password hashes now? (y/n): " FIX_PASS
if [[ "$FIX_PASS" == "y" ]]; then
    cd "$WEBROOT"
    php hash_passwords.php
    echo "[OK] Password hashes updated."
    echo "     >>> Delete hash_passwords.php for security: rm $WEBROOT/hash_passwords.php <<<"
fi

# --- Step 5: Download RDS SSL cert ---
if [ ! -f /etc/ssl/certs/global-bundle.pem ]; then
    echo "Downloading AWS RDS SSL certificate..."
    curl -sS -o /etc/ssl/certs/global-bundle.pem \
        https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem || true
    echo "[OK] RDS SSL certificate downloaded."
fi

echo ""
echo "=========================================="
echo "Setup complete! Access your helpdesk at:"
echo "http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4 2>/dev/null || echo 'your-ec2-ip')"
echo ""
echo "Remaining steps:"
echo "1. Edit $WEBROOT/.env with your credentials"
echo "2. Test login at the URL above"
echo "3. Delete hash_passwords.php"
echo "4. Set up HTTPS with Let's Encrypt (optional)"
echo "=========================================="
