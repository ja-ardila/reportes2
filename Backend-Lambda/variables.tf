variable "db_host" {
  description = "Database host"
  type        = string
  default     = "jardila-reportes2.cuhps5uu7rzq.us-east-1.rds.amazonaws.com"
}

variable "db_user" {
  description = "Database user"
  type        = string
  default     = "jardila_reportes"
}

variable "db_password" {
  description = "Database password"
  type        = string
  default     = "Zsw2Xaq1"
}

variable "db_name" {
  description = "Database name"
  type        = string
  default     = "jardila_reportes2"
}

variable "jwt_secret" {
  description = "JWT secret key"
  type        = string
  default     = "mi_clave_secreta_super_segura_123"
}
