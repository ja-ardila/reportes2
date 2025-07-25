#variables.tf
variable "aws_access_key" {
    description = "Access key to AWS console"
}
variable "aws_secret_key" {
    description = "Secret key to AWS console"
}
variable "region" {
    description = "AWS region"
}
variable "db_password" {
    description = "Password for the RDS database"
    sensitive = true
}
