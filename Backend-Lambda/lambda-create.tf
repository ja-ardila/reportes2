# Lambda para crear reportes

# Configuración de Terraform y provider
terraform {
  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = "us-east-1"
}

# Obtener información de la cuenta
data "aws_caller_identity" "current" {}

resource "aws_lambda_function" "create_reporte" {
  filename         = "lambda-create-reporte-with-deps.zip"
  function_name    = "create-reporte"
  role            = "arn:aws:iam::${data.aws_caller_identity.current.account_id}:role/LabRole"
  handler         = "create-service.handler"
  runtime         = "nodejs18.x"
  timeout         = 30
  memory_size     = 256

  source_code_hash = filebase64sha256("lambda-create-reporte-with-deps.zip")

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

# Output específico para la lambda de creación
output "create_lambda_function_name" {
  value = aws_lambda_function.create_reporte.function_name
  description = "Nombre de la lambda para crear reportes"
}

output "create_lambda_function_arn" {
  value = aws_lambda_function.create_reporte.arn
  description = "ARN de la lambda para crear reportes"
}
