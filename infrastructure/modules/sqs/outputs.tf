output "scan_html_queue_url" {
  value = aws_sqs_queue.scan_html.url
}

output "scan_html_queue_arn" {
  value = aws_sqs_queue.scan_html.arn
}

output "scan_pdf_queue_url" {
  value = aws_sqs_queue.scan_pdf.url
}

output "scan_pdf_queue_arn" {
  value = aws_sqs_queue.scan_pdf.arn
}
