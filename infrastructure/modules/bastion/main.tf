locals {
  name = "${var.project_name}-${var.environment}"
}

# Latest Amazon Linux 2023 AMI, published by AWS as a public SSM parameter —
# always current, no AMI ID to track or update manually.
data "aws_ssm_parameter" "al2023" {
  name = "/aws/service/ami-amazon-linux-latest/al2023-ami-kernel-default-x86_64"
}

resource "aws_iam_role" "this" {
  name = "${local.name}-bastion"

  assume_role_policy = jsonencode({
    Version = "2012-10-17"
    Statement = [{
      Effect    = "Allow"
      Principal = { Service = "ec2.amazonaws.com" }
      Action    = "sts:AssumeRole"
    }]
  })

  tags = { Name = "${local.name}-bastion" }
}

# AWS-managed policy: lets the SSM agent register the instance and lets
# Session Manager (incl. port-forwarding to a remote host, e.g. RDS)
# connect to it. No SSH, no key pair, no inbound security group rules.
resource "aws_iam_role_policy_attachment" "ssm" {
  role       = aws_iam_role.this.name
  policy_arn = "arn:aws:iam::aws:policy/AmazonSSMManagedInstanceCore"
}

resource "aws_iam_instance_profile" "this" {
  name = "${local.name}-bastion"
  role = aws_iam_role.this.name
}

resource "aws_instance" "this" {
  ami                    = data.aws_ssm_parameter.al2023.value
  instance_type          = var.instance_type
  subnet_id              = var.subnet_id
  vpc_security_group_ids = [var.security_group_id]
  iam_instance_profile   = aws_iam_instance_profile.this.name

  # Private subnet, no public IP — reachable only via SSM (outbound-only,
  # over the subnet's existing NAT gateway route), never directly from the
  # internet.
  associate_public_ip_address = false

  metadata_options {
    http_tokens = "required"
  }

  tags = { Name = "${local.name}-bastion" }
}
