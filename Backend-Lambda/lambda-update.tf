resource "aws_lambda_function" "update_reporte" {
  filename         = "lambda-update-reporte-with-deps.zip"
  function_name    = "update-reporte"
  role            = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/lambda-run-role"
  handler         = "update-service.handler"
  runtime         = "nodejs18.x"
  timeout         = 30
  memory_size     = 256

  source_code_hash = filebase64sha256("lambda-update-reporte-with-deps.zip")

  environment {
    variables = {
      DB_HOST     = var.db_host
      DB_USER     = var.db_user
      DB_PASSWORD = var.db_password
      DB_NAME     = var.db_name
      JWT_SECRET  = var.jwt_secret
      NODE_ENV    = "production"
    }
  }
}

# Output específico para la lambda de actualización
output "update_lambda_function_name" {
  value = aws_lambda_function.update_reporte.function_name
  description = "Nombre de la lambda para actualizar reportes"
}

output "update_lambda_function_arn" {
  value = aws_lambda_function.update_reporte.arn
  description = "ARN de la lambda para actualizar reportes"
}
