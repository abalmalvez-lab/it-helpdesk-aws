# IT Helpdesk — Fast Deployment Guide (Script-Based)

> **Region:** `us-east-1` (N. Virginia)
> **Instance:** EC2 t3.micro · Ubuntu 24.04 · MySQL on-instance
> **Time:** ~15 minutes
> **Cost:** ~$10/month after free tier

---

## Prerequisites

- AWS account (free tier eligible)
- OpenAI API key from [platform.openai.com](https://platform.openai.com)
- The `it-helpdesk-aws.zip` application package

---

## Step 1 — Open CloudShell

1. Log into the **AWS Management Console**
2. Make sure the region selector (top-right) says **US East (N. Virginia) us-east-1**
3. Click the **CloudShell** icon (terminal icon) in the top navigation bar
4. Wait for the shell to initialize

---

## Step 2 — Run Infrastructure Script

Copy and paste the **entire block below** into CloudShell and press Enter:

```bash
#!/bin/bash
# =============================================================
# IT Helpdesk — One-Click AWS Infrastructure (us-east-1)
# Paste this entire block into CloudShell.
# =============================================================

REGION="us-east-1"
KEY_NAME="helpdesk-key"
APP_NAME="it-helpdesk"

echo "==========================================="
echo "IT Helpdesk — AWS Infrastructure Setup"
echo "Region: $REGION"
echo "==========================================="

# --- 1. Create Key Pair ---
echo ">>> Creating EC2 key pair..."
aws ec2 create-key-pair \
    --key-name $KEY_NAME \
    --key-type rsa \
    --query 'KeyMaterial' \
    --output text \
    --region $REGION > ~/${KEY_NAME}.pem

chmod 400 ~/${KEY_NAME}.pem
echo "[OK] Key pair saved to ~/${KEY_NAME}.pem"

# --- 2. Create Security Group ---
echo ">>> Creating security group..."
VPC_ID=$(aws ec2 describe-vpcs --filters "Name=is-default,Values=true" \
    --query 'Vpcs[0].VpcId' --output text --region $REGION)

SG_ID=$(aws ec2 create-security-group \
    --group-name ${APP_NAME}-sg \
    --description "IT Helpdesk - HTTP, HTTPS, SSH" \
    --vpc-id $VPC_ID \
    --query 'GroupId' --output text \
    --region $REGION)

aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 22 --cidr 0.0.0.0/0 --region $REGION

aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 80 --cidr 0.0.0.0/0 --region $REGION

aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 443 --cidr 0.0.0.0/0 --region $REGION

aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID --protocol tcp --port 3306 --source-group $SG_ID --region $REGION

echo "[OK] Security group: $SG_ID"

# --- 3. Find Ubuntu 24.04 AMI ---
echo ">>> Finding latest Ubuntu 24.04 AMI..."
AMI_ID=$(aws ec2 describe-images \
    --owners 099720109477 \
    --filters "Name=name,Values=ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*" \
    "Name=state,Values=available" \
    --query 'sort_by(Images, &CreationDate)[-1].ImageId' \
    --output text --region $REGION)

echo "[OK] AMI: $AMI_ID"

# --- 4. Launch EC2 Instance ---
echo ">>> Launching EC2 t3.micro..."
INSTANCE_ID=$(aws ec2 run-instances \
    --image-id $AMI_ID \
    --instance-type t3.micro \
    --key-name $KEY_NAME \
    --security-group-ids $SG_ID \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=${APP_NAME}}]" \
    --block-device-mappings '[{"DeviceName":"/dev/sda1","Ebs":{"VolumeSize":20,"VolumeType":"gp3"}}]' \
    --query 'Instances[0].InstanceId' --output text \
    --region $REGION)

echo "[OK] Instance: $INSTANCE_ID"
echo "     Waiting for instance to start..."
aws ec2 wait instance-running --instance-ids $INSTANCE_ID --region $REGION
echo "[OK] Instance is running."

# --- 5. Allocate & Associate Elastic IP ---
echo ">>> Allocating Elastic IP..."
EIP_ALLOC=$(aws ec2 allocate-address \
    --domain vpc \
    --query 'AllocationId' --output text \
    --region $REGION)

aws ec2 associate-address \
    --instance-id $INSTANCE_ID \
    --allocation-id $EIP_ALLOC \
    --region $REGION > /dev/null

EIP=$(aws ec2 describe-addresses \
    --allocation-ids $EIP_ALLOC \
    --query 'Addresses[0].PublicIp' --output text \
    --region $REGION)

echo "[OK] Elastic IP: $EIP"

# --- 6. Create S3 Bucket ---
echo ">>> Creating S3 bucket..."
S3_BUCKET="${APP_NAME}-files-$(date +%s)"
aws s3 mb s3://$S3_BUCKET --region $REGION

aws s3api put-public-access-block \
    --bucket $S3_BUCKET \
    --public-access-block-configuration \
    "BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true"

echo "[OK] S3 bucket: $S3_BUCKET"

# --- Summary ---
echo ""
echo "==========================================="
echo "  INFRASTRUCTURE READY"
echo "==========================================="
echo ""
echo "  EC2 Instance : $INSTANCE_ID"
echo "  Elastic IP   : $EIP"
echo "  S3 Bucket    : $S3_BUCKET"
echo "  Key Pair     : ~/${KEY_NAME}.pem"
echo "  Security Grp : $SG_ID"
echo ""
echo "  Next: SSH into the instance:"
echo "  ssh -i ~/${KEY_NAME}.pem ubuntu@$EIP"
echo ""
echo "==========================================="
```

---

## Step 3 — Wait & SSH

The instance takes ~1-2 minutes for the OS to fully boot. Then SSH in:

```bash
ssh -i ~/helpdesk-key.pem ubuntu@YOUR_ELASTIC_IP
```

> Replace `YOUR_ELASTIC_IP` with the IP from the script output.

---

## Step 4 — Install Server Stack (on EC2)

Paste this block after SSH-ing into the instance:

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 php php-mysql php-mbstring php-xml php-curl php-zip mysql-server unzip
sudo a2enmod rewrite
sudo systemctl enable apache2 mysql

# Allow .htaccess overrides
sudo bash -c 'cat > /etc/apache2/conf-available/helpdesk.conf << EOF
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
EOF'
sudo a2enconf helpdesk
sudo systemctl restart apache2

# Download RDS SSL cert (for future use)
sudo curl -sS -o /etc/ssl/certs/global-bundle.pem \
    https://truststore.pki.rds.amazonaws.com/global/global-bundle.pem

echo "[OK] Apache + PHP + MySQL installed."
```

---

## Step 5 — Upload & Deploy Application (on EC2)

```bash
# Remove default Apache page
sudo rm -f /var/www/html/index.html

# Upload the zip from your local machine (run this locally, not on EC2):
# scp -i ~/helpdesk-key.pem it-helpdesk-aws.zip ubuntu@YOUR_ELASTIC_IP:~/

# Then on EC2:
git clone https://github.com/abalmalvez-lab/it-helpdesk-aws.git it-helpdesk-aws
chmod +x it-helpdesk-aws/deployment/*.sh
sudo cp -r it-helpdesk-aws/* /var/www/html/
sudo cp it-helpdesk-aws/.env.example /var/www/html/
sudo cp it-helpdesk-aws/.htaccess /var/www/html/
sudo chown -R www-data:www-data /var/www/html
sudo chmod 640 /var/www/html/.env.example
```

---

## Step 6 — Database Setup (on EC2)

```bash
# Create database and app user
sudo mysql -e "
CREATE DATABASE it_helpdesk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'helpdesk_admin'@'localhost' IDENTIFIED BY 'Helpdesk2024Secure!';
GRANT ALL PRIVILEGES ON it_helpdesk.* TO 'helpdesk_admin'@'localhost';
FLUSH PRIVILEGES;
"

# Import schema and seed data
sudo mysql it_helpdesk < /var/www/html/database/setup.sql
sudo mysql it_helpdesk < /var/www/html/database/seed.sql

echo "[OK] Database created and seeded."
```

---

## Step 7 — Configure & Finalize (on EC2)

```bash
# Create .env
sudo cp /var/www/html/.env.example /var/www/html/.env

# Set database credentials and OpenAI key
sudo sed -i 's/DB_HOST=localhost/DB_HOST=localhost/' /var/www/html/.env
sudo sed -i 's/DB_USER=helpdesk_admin/DB_USER=helpdesk_admin/' /var/www/html/.env
sudo sed -i 's/DB_PASS=CHANGE_ME_STRONG_PASSWORD/DB_PASS=Helpdesk2024Secure!/' /var/www/html/.env
sudo sed -i 's/APP_TIMEZONE=Asia\/Singapore/APP_TIMEZONE=America\/New_York/' /var/www/html/.env

# SET YOUR OPENAI KEY (replace sk-your-key-here with your actual key):
sudo sed -i 's/OPENAI_API_KEY=sk-your-openai-api-key-here/OPENAI_API_KEY=sk-your-key-here/' /var/www/html/.env

# Fix permissions
sudo chown www-data:www-data /var/www/html/.env
sudo chmod 640 /var/www/html/.env

# Generate password hashes
cd /var/www/html && sudo php hash_passwords.php

# Security cleanup
sudo rm /var/www/html/hash_passwords.php
sudo rm -rf /var/www/html/deployment/

echo "[OK] Application configured. Access at http://$(curl -s http://169.254.169.254/latest/meta-data/public-ipv4)"
```

---

## Step 8 — Access the Application

Open your browser and go to:

```
http://YOUR_ELASTIC_IP
```

Login with:

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@helpdesk.local | Admin123! |
| Staff | staff@helpdesk.local | Staff123! |
| User | user@helpdesk.local | User123! |

> **Change all passwords immediately after first login.**

---

## Cleanup (When Done)

To avoid charges, terminate everything:

```bash
# Run in CloudShell (not on EC2)
REGION="us-east-1"

# Get resource IDs
INSTANCE_ID=$(aws ec2 describe-instances --filters "Name=tag:Name,Values=it-helpdesk" "Name=instance-state-name,Values=running" --query 'Reservations[0].Instances[0].InstanceId' --output text --region $REGION)
EIP_ALLOC=$(aws ec2 describe-addresses --filters "Name=instance-id,Values=$INSTANCE_ID" --query 'Addresses[0].AllocationId' --output text --region $REGION)

# Disassociate and release EIP
aws ec2 disassociate-address --association-id $(aws ec2 describe-addresses --allocation-ids $EIP_ALLOC --query 'Addresses[0].AssociationId' --output text --region $REGION) --region $REGION
aws ec2 release-address --allocation-id $EIP_ALLOC --region $REGION

# Terminate instance
aws ec2 terminate-instances --instance-ids $INSTANCE_ID --region $REGION

# Delete security group (wait for instance termination)
aws ec2 wait instance-terminated --instance-ids $INSTANCE_ID --region $REGION
aws ec2 delete-security-group --group-name it-helpdesk-sg --region $REGION

# Delete key pair
aws ec2 delete-key-pair --key-name helpdesk-key --region $REGION
rm -f ~/helpdesk-key.pem

echo "[OK] All AWS resources cleaned up."
```
