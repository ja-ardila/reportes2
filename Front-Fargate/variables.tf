# Variables
variable "app_name" {
  description = "Nombre de la aplicación"
  type        = string
  default     = "reportes-frontend"
}

variable "environment" {
  description = "Ambiente de despliegue"
  type        = string
  default     = "prod"
}

variable "aws_region" {
  description = "Región de AWS"
  type        = string
  default     = "us-east-1"
}

variable "container_port" {
  description = "Puerto del contenedor"
  type        = number
  default     = 80
}

variable "desired_count" {
  description = "Número deseado de tareas"
  type        = number
  default     = 2
}

variable "cpu" {
  description = "CPU para la tarea de Fargate"
  type        = string
  default     = "256"
}

variable "memory" {
  description = "Memoria para la tarea de Fargate"
  type        = string
  default     = "512"
}

variable "ecr_repository_url" {
  description = "URL del repositorio ECR"
  type        = string
}

variable "image_tag" {
  description = "Tag de la imagen Docker"
  type        = string
  default     = "latest"
}
