output "endpoint" {
  value = aws_db_instance.this.endpoint
}

output "address" {
  value = aws_db_instance.this.address
}

output "port" {
  value = aws_db_instance.this.port
}

output "db_name" {
  value = aws_db_instance.this.db_name
}

output "db_instance_id" {
  # .id resolves to RDS's internal resource ID (format "db-xxxx...") in the
  # provider version this pins to, not the human-readable identifier
  # ("equalify-prod-db") that --db-instance-identifier actually expects
  # everywhere (modify-db-instance, describe-db-instances, ...). .identifier
  # is the argument we actually set below, echoed back correctly.
  value = aws_db_instance.this.identifier
}
