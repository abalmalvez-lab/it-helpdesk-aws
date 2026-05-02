#!/bin/bash
# =============================================================
# IT Helpdesk — EC2 User-Data Bootstrap Script
# =============================================================
# Run this as user-data when launching an EC2 t3.micro instance
# with Amazon Linux 2023 or Ubuntu 24.04.
#
# This script installs Apache, PHP 8.x, MySQL 8, and deploys
# the helpdesk application automatically.
# =============================================================

set -e
exec > /var/log/helpdesk-setup.log 2>&1

echo "=========================================="
echo "IT Helpdesk — EC2 Setup Starting"
echo "$(date)"
echo "=========================================="

# --- Detect OS ---
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
else
    OS="unknown"
fi

echo "Detected OS: $OS"

# ===========================================
# AMAZON LINUX 2023 / AL2
# ===========================================
if [[ "$OS" == "amzn" ]]; then
    echo ">>> Installing packages (Amazon Linux)..."
    dnf update -y
    dnf install -y httpd php8.2 php8.2-mysqlnd php8.2-mbstring php8.2-xml php8.2-curl php8.2-json php8.2-zip
    dnf install -y mariadb105-server mariadb105
    # If using local MySQL instead of RDS:
    systemctl enable mariadb
    systemctl start mariadb
    systemctl enable httpd
    systemctl start httpd

# ===========================================
# UBUNTU 24.04
# ===========================================
elif [[ "$OS" == "ubuntu" ]]; then
    echo ">>> Installing packages (Ubuntu)..."
    export DEBIAN_FRONTEND=noninteractive
    apt-get update -y
    apt-get install -y apache2 php php-mysql php-mbstring php-xml php-curl php-zip
    apt-get install -y mysql-server
    systemctl enable mysql
    systemctl start mysql
    systemctl enable apache2
    systemctl start apache2
    # Enable mod_rewrite
    a2enmod rewrite
    systemctl restart apache2
fi

# --- Configure Apache ---
echo ">>> Configuring Apache..."
if [[ "$OS" == "amzn" ]]; then
    WEBROOT="/var/www/html"
    APACHE_CONF="/etc/httpd/conf/httpd.conf"
    # Enable AllowOverride for .htaccess
    sed -i '/<Directory "\/var\/www\/html">/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' $APACHE_CONF
    systemctl restart httpd
elif [[ "$OS" == "ubuntu" ]]; then
    WEBROOT="/var/www/html"
    APACHE_CONF="/etc/apache2/sites-available/000-default.conf"
    # Enable AllowOverride
    cat > /etc/apache2/conf-available/helpdesk.conf << 'APACHEEOF'
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
APACHEEOF
    a2enconf helpdesk
    systemctl restart apache2
fi

# --- Deploy Application ---
echo ">>> Deploying application to $WEBROOT..."
# Remove default index.html
rm -f $WEBROOT/index.html

# The application files should be uploaded here.
# Option 1: Copy from S3
# aws s3 cp s3://your-bucket/it-helpdesk-aws.zip /tmp/
# unzip /tmp/it-helpdesk-aws.zip -d $WEBROOT/

# Option 2: Clone from git
# git clone https://github.com/your-repo/it-helpdesk.git $WEBROOT/

echo ">>> Application files should be deployed to $WEBROOT"
echo ">>> You can upload via SCP, S3, or Git."

# --- Download RDS SSL Certificate ---
echo ">>> Downloading AWS RDS SSL certificate bundle..."
curl -o /etc/ssl/certs/global-bundle.pem \
    https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem 2>/dev/null || true

# --- Set Permissions ---
echo ">>> Setting file permissions..."
if [[ "$OS" == "amzn" ]]; then
    chown -R apache:apache $WEBROOT
    chmod -R 755 $WEBROOT
elif [[ "$OS" == "ubuntu" ]]; then
    chown -R www-data:www-data $WEBROOT
    chmod -R 755 $WEBROOT
fi

# --- PHP Configuration ---
echo ">>> Tuning PHP..."
PHP_INI=$(php -r "echo php_ini_loaded_file();")
if [ -f "$PHP_INI" ]; then
    sed -i 's/upload_max_filesize = .*/upload_max_filesize = 10M/' $PHP_INI
    sed -i 's/post_max_size = .*/post_max_size = 12M/' $PHP_INI
    sed -i 's/max_execution_time = .*/max_execution_time = 60/' $PHP_INI
    sed -i 's/display_errors = .*/display_errors = Off/' $PHP_INI
fi

# --- Firewall (if using iptables/firewalld) ---
if command -v firewall-cmd &>/dev/null; then
    firewall-cmd --permanent --add-service=http
    firewall-cmd --permanent --add-service=https
    firewall-cmd --reload
fi

echo "=========================================="
echo "IT Helpdesk — EC2 Setup Complete"
echo "$(date)"
echo ""
echo "Next steps:"
echo "1. Upload application files to $WEBROOT"
echo "2. Copy .env.example to .env and configure"
echo "3. Import database/setup.sql and database/seed.sql"
echo "4. Run hash_passwords.php once"
echo "5. Access via http://<your-ec2-public-ip>"
echo "=========================================="
