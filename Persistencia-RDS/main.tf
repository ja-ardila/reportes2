#main.tf
#defining the provider as aws
provider "aws" {
    shared_credentials_files = ["~/.aws/credentials"]
}
#create a security group for RDS Database Instance
resource "aws_security_group" "rds_sg" {
  name = "rds_sg"
  ingress {
    from_port       = 3306
    to_port         = 3306
    protocol        = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }
  egress {
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }
}

#create a RDS Database Instance
resource "aws_db_instance" "reportes_db" {
  identifier              = "jardila-reportes2"
  engine                  = "mysql"
  instance_class          = "db.t3.micro"
  allocated_storage       = 20
  storage_type            = "gp2"
  publicly_accessible     = true
  username             = "jardila_reportes"
  password             = "Zsw2Xaq1"
  parameter_group_name    = aws_db_parameter_group.reportes-db.name
  vpc_security_group_ids = [aws_security_group.rds_sg.id]
  skip_final_snapshot  = true

  provisioner "local-exec" {
    command = <<-EOT
      # Configurar PATH para MySQL
      export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"
      
      # Extraer solo el hostname del endpoint (sin el puerto)
      DB_HOST=$(echo "${self.endpoint}" | cut -d':' -f1)
      
      # Esperar a que la base de datos esté disponible
      echo "Esperando a que la base de datos esté disponible..."
      while ! mysql -h $DB_HOST -u ${self.username} -p${self.password} -e "SELECT 1" 2>/dev/null; do
        echo "Esperando conexión a la base de datos..."
        sleep 10
      done
      
      # Ejecutar el script SQL de estructura
      echo "Ejecutando script de inicialización..."
      mysql -h $DB_HOST -u ${self.username} -p${self.password} < schema.sql
      echo "Script de estructura ejecutado exitosamente"
    EOT
  }

  # Segundo provisioner para cargar datos por defecto
  provisioner "local-exec" {
    command = <<-EOT
      # Configurar PATH para MySQL
      export PATH="/opt/homebrew/opt/mysql-client/bin:$PATH"
      
      # Extraer solo el hostname del endpoint (sin el puerto)
      DB_HOST=$(echo "${self.endpoint}" | cut -d':' -f1)
      
      # Esperar un momento después de crear las tablas
      sleep 5
      echo "Cargando datos por defecto..."
      mysql -h $DB_HOST -u ${self.username} -p${self.password} < default-data.sql
      echo "Datos por defecto cargados exitosamente"
    EOT
  }
}

resource "aws_db_parameter_group" "reportes-db" {
  name        = "reportes-db-parameter-group"
  family      = "mysql8.0"
  description = "Parameter group for MySQL 8.0"
}
