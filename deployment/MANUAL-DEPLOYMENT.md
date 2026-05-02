# IT Helpdesk — Manual Deployment Guide (Step-by-Step)

> **Region:** `us-east-1` (N. Virginia)
> **Instance:** EC2 t3.micro · Ubuntu 24.04 · 20 GB gp3
> **Time:** ~45-60 minutes
> **Cost:** ~$10/month after free tier

---

## 1. AWS Account & Region Setup

1. Log in to **https://console.aws.amazon.com**
2. In the **top-right corner**, click the region selector
3. Choose **US East (N. Virginia) us-east-1**
4. All resources will be created in this region

---

## 2. Set Up Budget Alert

> Prevents surprise charges. Also earns $20 in bonus credits.

1. Navigate to **Billing & Cost Management → Budgets**
2. Click **Create budget**
3. Select **Use a template → Monthly cost budget**
4. Set budget amount: `10`
5. Enter your email for notifications
6. Click **Create budget**

---

## 3. Create Key Pair

> Needed for SSH access to the EC2 instance.

**CloudShell:**

```bash
aws ec2 create-key-pair \
    --key-name helpdesk-key \
    --key-type rsa \
    --query 'KeyMaterial' \
    --output text \
    --region us-east-1 > ~/helpdesk-key.pem

chmod 400 ~/helpdesk-key.pem
echo "[OK] Key pair created: ~/helpdesk-key.pem"
```

**Or via Console:**

1. Go to **EC2 → Key Pairs** (left sidebar under Network & Security)
2. Click **Create key pair**
3. Name: `helpdesk-key`
4. Type: RSA
5. Format: `.pem`
6. Click **Create key pair** — the file auto-downloads

---

## 4. Create Security Group

> Acts as a firewall — controls which ports are accessible.

**CloudShell:**

```bash
REGION="us-east-1"

VPC_ID=$(aws ec2 describe-vpcs --filters "Name=is-default,Values=true" \
    --query 'Vpcs[0].VpcId' --output text --region $REGION)

SG_ID=$(aws ec2 create-security-group \
    --group-name it-helpdesk-sg \
    --description "IT Helpdesk - HTTP, HTTPS, SSH" \
    --vpc-id $VPC_ID \
    --query 'GroupId' --output text \
    --region $REGION)

# Allow SSH (port 22)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 22 --cidr 0.0.0.0/0 --region $REGION

# Allow HTTP (port 80)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 80 --cidr 0.0.0.0/0 --region $REGION

# Allow HTTPS (port 443)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 443 --cidr 0.0.0.0/0 --region $REGION

# Allow MySQL internally (port 3306)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 3306 --source-group $SG_ID --region $REGION

echo "[OK] Security group created: $SG_ID"
```

**Or via Console:**

1. Go to **EC2 → Security Groups** (left sidebar)
2. Click **Create security group**
3. Name: `it-helpdesk-sg`
4. Description: `IT Helpdesk - HTTP, HTTPS, SSH`
5. VPC: select the default VPC
6. Add **Inbound rules**:

| Type | Protocol | Port | Source |
|------|----------|------|--------|
| SSH | TCP | 22 | My IP |
| HTTP | TCP | 80 | 0.0.0.0/0 |
| HTTPS | TCP | 443 | 0.0.0.0/0 |

7. Click **Create security group**

---

## 5. Launch EC2 Instance

**CloudShell:**

```bash
REGION="us-east-1"

# Find latest Ubuntu 24.04 AMI
AMI_ID=$(aws ec2 describe-images \
    --owners 099720109477 \
    --filters "Name=name,Values=ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*" \
    "Name=state,Values=available" \
    --query 'sort_by(Images, &CreationDate)[-1].ImageId' \
    --output text --region $REGION)

echo "Using AMI: $AMI_ID"

# Get security group ID
SG_ID=$(aws ec2 describe-security-groups --filters "Name=group-name,Values=it-helpdesk-sg" \
    --query 'SecurityGroups[0].GroupId' --output text --region $REGION)

# Launch instance
INSTANCE_ID=$(aws ec2 run-instances \
    --image-id $AMI_ID \
    --instance-type t3.micro \
    --key-name helpdesk-key \
    --security-group-ids $SG_ID \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=it-helpdesk}]" \
    --block-device-mappings '[{"DeviceName":"/dev/sda1","Ebs":{"VolumeSize":20,"VolumeType":"gp3"}}]' \
    --query 'Instances[0].InstanceId' --output text \
    --region $REGION)

echo "[OK] Instance launched: $INSTANCE_ID"
echo "Waiting for instance to start..."
aws ec2 wait instance-running --instance-ids $INSTANCE_ID --region $REGION
echo "[OK] Instance is running."
```

**Or via Console:**

1. Go to **EC2 → Instances → Launch instances**
2. Name: `it-helpdesk`
3. AMI: **Ubuntu 24.04 LTS** (Free tier eligible)
4. Instance type: **t3.micro**
5. Key pair: select `helpdesk-key`
6. Network: select `it-helpdesk-sg` security group
7. Storage: change to **20 GiB gp3**
8. Click **Launch instance**

---

## 6. Allocate Elastic IP

> Gives a static public IP that stays the same across instance restarts.

**CloudShell:**

```bash
REGION="us-east-1"

# Get instance ID
INSTANCE_ID=$(aws ec2 describe-instances \
    --filters "Name=tag:Name,Values=it-helpdesk" "Name=instance-state-name,Values=running" \
    --query 'Reservations[0].Instances[0].InstanceId' --output text --region $REGION)

# Allocate EIP
EIP_ALLOC=$(aws ec2 allocate-address --domain vpc \
    --query 'AllocationId' --output text --region $REGION)

# Associate with instance
aws ec2 associate-address \
    --instance-id $INSTANCE_ID \
    --allocation-id $EIP_ALLOC \
    --region $REGION > /dev/null

# Show the IP
EIP=$(aws ec2 describe-addresses --allocation-ids $EIP_ALLOC \
    --query 'Addresses[0].PublicIp' --output text --region $REGION)

echo "[OK] Elastic IP: $EIP → $INSTANCE_ID"
```

**Or via Console:**

1. Go to **EC2 → Elastic IPs** (left sidebar)
2. Click **Allocate Elastic IP address** → **Allocate**
3. Select the new EIP → **Actions → Associate Elastic IP address**
4. Choose your `it-helpdesk` instance → **Associate**
5. Note the **Elastic IP address** displayed

> ⚠️ An unattached EIP costs **$3.65/month**. Always release it when terminating the instance.

---

## 7. SSH Into the Instance

**CloudShell:**

```bash
ssh -i ~/helpdesk-key.pem ubuntu@YOUR_ELASTIC_IP
```

> Replace `YOUR_ELASTIC_IP` with the IP from Step 6.
> Type `yes` when prompted to accept the host key fingerprint.

---

## 8. Install Apache, PHP, and MySQL

> Run all commands below **on the EC2 instance** (after SSH).

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install web stack
sudo apt install -y apache2 php php-mysql php-mbstring php-xml php-curl php-zip

# Install MySQL
sudo apt install -y mysql-server

# Install unzip (for uploading the app package)
sudo apt install -y unzip

# Enable required services and modules
sudo systemctl enable apache2 mysql
sudo a2enmod rewrite

# Configure Apache to allow .htaccess
sudo bash -c 'cat > /etc/apache2/conf-available/helpdesk.conf << EOF
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
EOF'
sudo a2enconf helpdesk
sudo systemctl restart apache2

echo "[OK] Web stack installed: Apache + PHP + MySQL"
```

---

## 9. Secure MySQL

```bash
sudo mysql_secure_installation
```

When prompted:
- Would you like to setup VALIDATE PASSWORD component? → `N`
- New root password → enter a strong password and save it
- Remove anonymous users? → `Y`
- Disallow root login remotely? → `Y`
- Remove test database? → `Y`
- Reload privilege tables? → `Y`

---

## 10. Create Database and Application User

```bash
sudo mysql -e "
CREATE DATABASE it_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'helpdesk_admin'@'localhost' IDENTIFIED BY 'Helpdesk2024Secure!';
GRANT ALL PRIVILEGES ON it_helpdesk.* TO 'helpdesk_admin'@'localhost';
FLUSH PRIVILEGES;
"

echo "[OK] Database 'it_helpdesk' created."
echo "[OK] User 'helpdesk_admin' created."
```

---

## 11. Upload Application Files

**Option A — From your local machine (SCP):**

```bash
# Run this on your LOCAL machine (not on EC2)
scp -i ~/helpdesk-key.pem it-helpdesk-aws.zip ubuntu@YOUR_ELASTIC_IP:~/
```

**Option B — Upload to S3 first, then download on EC2:**

```bash
# On your local machine:
aws s3 cp it-helpdesk-aws.zip s3://YOUR_BUCKET_NAME/ --region us-east-1

# On EC2:
aws s3 cp s3://YOUR_BUCKET_NAME/it-helpdesk-aws.zip ~/
```

**Then on EC2, extract and deploy:**

```bash
# Remove default Apache page
sudo rm -f /var/www/html/index.html

# Extract and copy files
cd ~
unzip it-helpdesk-aws.zip
sudo cp -r it-helpdesk-aws/* /var/www/html/
sudo cp it-helpdesk-aws/.env.example /var/www/html/
sudo cp it-helpdesk-aws/.htaccess /var/www/html/

echo "[OK] Application files deployed to /var/www/html/"
```

---

## 12. Import Database Schema and Seed Data

```bash
# Import table structure
sudo mysql it_helpdesk < /var/www/html/database/setup.sql

# Import sample data (100+ tickets, users, categories)
sudo mysql it_helpdesk < /var/www/html/database/seed.sql

echo "[OK] Database schema and seed data imported."
```

---

## 13. Configure Environment

```bash
# Create .env from template
sudo cp /var/www/html/.env.example /var/www/html/.env

# Edit the .env file
sudo nano /var/www/html/.env
```

Set these values in the editor:

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=it_helpdesk
DB_USER=helpdesk_admin
DB_PASS=Helpdesk2024Secure!

OPENAI_API_KEY=sk-your-actual-openai-api-key

APP_ENV=production
APP_DEBUG=false
APP_TIMEZONE=America/New_York
```

Save: `Ctrl+O` → `Enter` → `Ctrl+X`

---

## 14. Set File Permissions and Generate Password Hashes

```bash
# Set ownership to Apache user
sudo chown -R www-data:www-data /var/www/html

# Protect .env file
sudo chmod 640 /var/www/html/.env

# Generate bcrypt password hashes for demo accounts
cd /var/www/html && sudo php hash_passwords.php

echo "[OK] Permissions set and password hashes generated."
```

---

## 15. Create S3 Bucket (Optional)

> For ticket attachments and database backups.

**CloudShell (not EC2):**

```bash
S3_BUCKET="it-helpdesk-files-$(date +%s)"

aws s3 mb s3://$S3_BUCKET --region us-east-1

aws s3api put-public-access-block \
    --bucket $S3_BUCKET \
    --public-access-block-configuration \
    "BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true"

echo "[OK] S3 bucket created: $S3_BUCKET"
```

---

## 16. Download RDS SSL Certificate

> Needed if you later switch from local MySQL to Amazon RDS.

```bash
sudo curl -sS -o /etc/ssl/certs/global-bundle.pem \
    https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem

echo "[OK] RDS SSL certificate downloaded."
```

---

## 17. Post-Deployment Security

```bash
# Delete the password hash generator (security risk)
sudo rm -f /var/www/html/hash_passwords.php

# Delete deployment scripts (not needed in production)
sudo rm -rf /var/www/html/deployment/

# Disable debug mode (already set in .env but double-check)
sudo sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' /var/www/html/.env

echo "[OK] Security hardening complete."
```

**Also update the Security Group:**

1. Go to **EC2 → Security Groups → it-helpdesk-sg**
2. Edit **Inbound rules**
3. Change SSH (port 22) source from `0.0.0.0/0` to **My IP**
4. Click **Save rules**

---

## 18. Verify the Application

Open your browser and go to:

```
http://YOUR_ELASTIC_IP
```

### Verification Checklist

| # | Test | Expected Result |
|---|------|-----------------|
| 1 | Go to http://ELASTIC_IP | Login page loads (no demo credentials shown) |
| 2 | Login as admin@helpdesk.local / Admin123! | Dashboard with 8 stat cards and 3 charts |
| 3 | Click **Submit Ticket** in sidebar | Ticket creation form loads |
| 4 | Fill title + description → Click **AI Classify** | AI suggests category and priority |
| 5 | Submit the ticket | Ticket created with TKT-XXXXXXXX number |
| 6 | Click ticket to view details | Full detail page with activity log |
| 7 | Click the **AI Chat** button (bottom-right) | Chat widget opens, responds to messages |
| 8 | Go to **Ticket Volume** report | Chart and table render with data |
| 9 | Click **Generate** on AI Insights | AI generates observations and recommendations |
| 10 | Logout → Login as user@helpdesk.local / User123! | Only sees own tickets, limited sidebar |

---

## 19. Optional: Install SSL (HTTPS)

> Requires a registered domain name pointed to your Elastic IP.

```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d your-domain.com
```

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Cannot login | Run: `cd /var/www/html && sudo php hash_passwords.php` |
| Database error | Check `.env` credentials match Step 10 |
| AI features not working | Verify `OPENAI_API_KEY` is set correctly in `.env` |
| 403 Forbidden | Run: `sudo a2enmod rewrite && sudo systemctl restart apache2` |
| Permission denied | Run: `sudo chown -R www-data:www-data /var/www/html` |
| Blank page | Set `APP_DEBUG=true` in `.env`, check `/var/log/apache2/error.log` |
| EIP charges after termination | Release the EIP in EC2 → Elastic IPs |

---

## Cleanup (Terminate Everything)

> Run in **CloudShell** when you want to remove all resources and stop charges.

```bash
REGION="us-east-1"

# Get resource IDs
INSTANCE_ID=$(aws ec2 describe-instances \
    --filters "Name=tag:Name,Values=it-helpdesk" "Name=instance-state-name,Values=running" \
    --query 'Reservations[0].Instances[0].InstanceId' --output text --region $REGION)

EIP_ALLOC=$(aws ec2 describe-addresses \
    --filters "Name=instance-id,Values=$INSTANCE_ID" \
    --query 'Addresses[0].AllocationId' --output text --region $REGION)

ASSOC_ID=$(aws ec2 describe-addresses \
    --allocation-ids $EIP_ALLOC \
    --query 'Addresses[0].AssociationId' --output text --region $REGION)

# Release Elastic IP
aws ec2 disassociate-address --association-id $ASSOC_ID --region $REGION
aws ec2 release-address --allocation-id $EIP_ALLOC --region $REGION

# Terminate instance
aws ec2 terminate-instances --instance-ids $INSTANCE_ID --region $REGION
aws ec2 wait instance-terminated --instance-ids $INSTANCE_ID --region $REGION

# Delete security group and key pair
aws ec2 delete-security-group --group-name it-helpdesk-sg --region $REGION
aws ec2 delete-key-pair --key-name helpdesk-key --region $REGION
rm -f ~/helpdesk-key.pem

echo "[OK] All AWS resources cleaned up. No further charges."
```
