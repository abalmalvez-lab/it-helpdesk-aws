#!/bin/bash
# =============================================================
# SmartDesk — AWS Infrastructure Setup (CLI Reference)
# =============================================================
# This script documents the AWS CLI commands to provision all
# infrastructure. Run these commands from your local machine
# with AWS CLI configured.
#
# Region: us-east-1 (N. Virginia)
# =============================================================

REGION="us-east-1"
KEY_NAME="helpdesk-key"
APP_NAME="it-helpdesk"

echo "==========================================="
echo "SmartDesk — AWS Infrastructure Setup"
echo "Region: $REGION"
echo "==========================================="

# ===========================================
# 1. Create Key Pair (for SSH access)
# ===========================================
echo ">>> Creating EC2 key pair..."
aws ec2 create-key-pair \
    --key-name $KEY_NAME \
    --key-type rsa \
    --query 'KeyMaterial' \
    --output text \
    --region $REGION > ${KEY_NAME}.pem

chmod 400 ${KEY_NAME}.pem
echo "[OK] Key pair saved to ${KEY_NAME}.pem"

# ===========================================
# 2. Create Security Group
# ===========================================
echo ">>> Creating security group..."
VPC_ID=$(aws ec2 describe-vpcs --filters "Name=is-default,Values=true" \
    --query 'Vpcs[0].VpcId' --output text --region $REGION)

SG_ID=$(aws ec2 create-security-group \
    --group-name ${APP_NAME}-sg \
    --description "SmartDesk - HTTP, HTTPS, SSH" \
    --vpc-id $VPC_ID \
    --query 'GroupId' --output text \
    --region $REGION)

# Allow SSH (restrict to your IP in production!)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID \
    --protocol tcp --port 22 --cidr 0.0.0.0/0 \
    --region $REGION

# Allow HTTP
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID \
    --protocol tcp --port 80 --cidr 0.0.0.0/0 \
    --region $REGION

# Allow HTTPS
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID \
    --protocol tcp --port 443 --cidr 0.0.0.0/0 \
    --region $REGION

# Allow MySQL from within the security group (for RDS)
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID \
    --protocol tcp --port 3306 --source-group $SG_ID \
    --region $REGION

echo "[OK] Security group: $SG_ID"

# ===========================================
# 3. Launch EC2 Instance (t3.micro)
# ===========================================
echo ">>> Launching EC2 instance..."
# Ubuntu 24.04 LTS AMI (check for latest in your region)
# AMI_ID="ami-0c1907b2c6e49a7ab"  # Ubuntu 24.04 us-east-1

# Find the latest Ubuntu 24.04 AMI
AMI_ID=$(aws ec2 describe-images \
    --owners 099720109477 \
    --filters "Name=name,Values=ubuntu/images/hvm-ssd-gp3/ubuntu-noble-24.04-amd64-server-*" \
    "Name=state,Values=available" \
    --query 'sort_by(Images, &CreationDate)[-1].ImageId' \
    --output text --region $REGION)

INSTANCE_ID=$(aws ec2 run-instances \
    --image-id $AMI_ID \
    --instance-type t3.micro \
    --key-name $KEY_NAME \
    --security-group-ids $SG_ID \
    --user-data file://deployment/ec2-user-data.sh \
    --tag-specifications "ResourceType=instance,Tags=[{Key=Name,Value=${APP_NAME}}]" \
    --block-device-mappings '[{"DeviceName":"/dev/sda1","Ebs":{"VolumeSize":20,"VolumeType":"gp3"}}]' \
    --query 'Instances[0].InstanceId' --output text \
    --region $REGION)

echo "[OK] Instance launched: $INSTANCE_ID"
echo "     Waiting for instance to start..."
aws ec2 wait instance-running --instance-ids $INSTANCE_ID --region $REGION

# ===========================================
# 4. Allocate Elastic IP
# ===========================================
echo ">>> Allocating Elastic IP..."
EIP_ALLOC=$(aws ec2 allocate-address \
    --domain vpc \
    --query 'AllocationId' --output text \
    --region $REGION)

PUBLIC_IP=$(aws ec2 associate-address \
    --instance-id $INSTANCE_ID \
    --allocation-id $EIP_ALLOC \
    --query 'AssociationId' --output text \
    --region $REGION)

EIP=$(aws ec2 describe-addresses \
    --allocation-ids $EIP_ALLOC \
    --query 'Addresses[0].PublicIp' --output text \
    --region $REGION)

echo "[OK] Elastic IP: $EIP associated with $INSTANCE_ID"

# ===========================================
# 5. Create S3 Bucket (for attachments/backups)
# ===========================================
echo ">>> Creating S3 bucket..."
S3_BUCKET="${APP_NAME}-files-$(date +%s)"
aws s3 mb s3://$S3_BUCKET --region $REGION

# Block public access
aws s3api put-public-access-block \
    --bucket $S3_BUCKET \
    --public-access-block-configuration \
    "BlockPublicAcls=true,IgnorePublicAcls=true,BlockPublicPolicy=true,RestrictPublicBuckets=true"

echo "[OK] S3 bucket: $S3_BUCKET"

# ===========================================
# 6. (OPTIONAL) Create RDS MySQL Instance
# ===========================================
# Uncomment if using a separate RDS database instead of MySQL on EC2.
#
# echo ">>> Creating RDS instance..."
# aws rds create-db-instance \
#     --db-instance-identifier ${APP_NAME}-db \
#     --db-instance-class db.t3.micro \
#     --engine mysql \
#     --engine-version 8.0 \
#     --master-username admin \
#     --master-user-password "YourStrongPassword123!" \
#     --allocated-storage 20 \
#     --storage-type gp3 \
#     --vpc-security-group-ids $SG_ID \
#     --db-name it_helpdesk \
#     --backup-retention-period 7 \
#     --no-publicly-accessible \
#     --region $REGION
#
# echo "[OK] RDS instance creating (takes 5-10 minutes)..."
# aws rds wait db-instance-available \
#     --db-instance-identifier ${APP_NAME}-db --region $REGION
#
# RDS_ENDPOINT=$(aws rds describe-db-instances \
#     --db-instance-identifier ${APP_NAME}-db \
#     --query 'DBInstances[0].Endpoint.Address' --output text \
#     --region $REGION)
# echo "[OK] RDS endpoint: $RDS_ENDPOINT"

# ===========================================
# Summary
# ===========================================
echo ""
echo "==========================================="
echo "AWS Infrastructure Ready!"
echo "==========================================="
echo ""
echo "EC2 Instance:  $INSTANCE_ID"
echo "Public IP:     $EIP"
echo "S3 Bucket:     $S3_BUCKET"
echo "Key Pair:      ${KEY_NAME}.pem"
echo ""
echo "Next steps:"
echo "1. SSH into the instance:"
echo "   ssh -i ${KEY_NAME}.pem ubuntu@$EIP"
echo ""
echo "2. Upload application files:"
echo "   scp -i ${KEY_NAME}.pem -r ./* ubuntu@$EIP:/var/www/html/"
echo ""
echo "3. Run the setup script:"
echo "   sudo bash /var/www/html/deployment/setup-app.sh"
echo ""
echo "4. Access the application:"
echo "   http://$EIP"
echo ""
echo "Estimated monthly cost: ~\$12 (EC2 + EBS + S3)"
echo "==========================================="
