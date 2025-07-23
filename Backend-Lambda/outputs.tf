
output "lambda_function_name" {
  value = aws_lambda_function.create_reporte.function_name
  description = "Nombre de la lambda de creación (compatibilidad)"
}

output "lambda_function_arn" {
  value = aws_lambda_function.create_reporte.arn
  description = "ARN de la lambda de creación (compatibilidad)"
}

output "lambda_invoke_arn" {
  value = aws_lambda_function.create_reporte.invoke_arn
  description = "ARN de invocación de la lambda de creación"
}

# Comandos de invocación para ambas lambdas
output "lambda_invocation_commands" {
  value = {
    create_reporte = "aws lambda invoke --function-name ${aws_lambda_function.create_reporte.function_name} --cli-binary-format raw-in-base64-out --payload '{\"numero_reporte\":\"RPT-001\"}' response.json"
    update_reporte = "aws lambda invoke --function-name ${aws_lambda_function.update_reporte.function_name} --cli-binary-format raw-in-base64-out --payload '{\"body\":\"{}\",\"pathParameters\":{\"id\":\"1\"}}' response.json"
  }
  description = "Comandos para invocar las lambdas directamente desde AWS CLI"
}
