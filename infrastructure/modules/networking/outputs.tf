output "vpc_id" {
  value = aws_vpc.this.id
}

output "public_subnet_ids" {
  value = aws_subnet.public[*].id
}

output "private_subnet_ids" {
  value = aws_subnet.private[*].id
}

output "alb_security_group_id" {
  value = aws_security_group.alb.id
}

output "hasura_task_security_group_id" {
  value = aws_security_group.hasura_task.id
}

output "backend_lambda_security_group_id" {
  value = aws_security_group.backend_lambda.id
}

output "rds_security_group_id" {
  value = aws_security_group.rds.id
}

output "bastion_security_group_id" {
  value = aws_security_group.bastion.id
}
