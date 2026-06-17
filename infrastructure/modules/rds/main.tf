locals {
  name = "${var.project_name}-${var.environment}"
}

resource "aws_db_subnet_group" "this" {
  name       = "${local.name}-db"
  subnet_ids = var.private_subnet_ids

  tags = { Name = "${local.name}-db" }
}

resource "aws_db_parameter_group" "this" {
  name   = "${local.name}-pg17"
  family = "postgres17"

  # pgcrypto (used by db/schema.sql) is a contrib extension, not a
  # parameter-group-gated one, so no shared_preload_libraries entry is
  # needed here. The CREATE EXTENSION statement runs as part of the schema
  # migration (see ../../README.md), not as part of this Terraform stack.
}

resource "aws_db_instance" "this" {
  identifier     = "${local.name}-db"
  engine         = "postgres"
  engine_version = var.engine_version

  instance_class        = var.instance_class
  allocated_storage     = var.allocated_storage
  max_allocated_storage = var.max_allocated_storage
  storage_type          = "gp3"
  storage_encrypted     = true

  db_name  = var.db_name
  username = var.db_username
  password = var.db_password
  port     = 5432

  db_subnet_group_name   = aws_db_subnet_group.this.name
  vpc_security_group_ids = [var.security_group_id]
  parameter_group_name   = aws_db_parameter_group.this.name
  publicly_accessible    = false

  multi_az                  = var.multi_az
  backup_retention_period   = var.backup_retention_period
  deletion_protection       = var.deletion_protection
  skip_final_snapshot       = var.skip_final_snapshot
  final_snapshot_identifier = var.skip_final_snapshot ? null : "${local.name}-db-final"

  auto_minor_version_upgrade = true
  copy_tags_to_snapshot      = true

  tags = { Name = "${local.name}-db" }
}
