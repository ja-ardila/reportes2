# Despliegue en AWS Fargate - Guía Completa

Esta guía te ayudará a desplegar tu aplicación Angular `reportes-app` en AWS Fargate usando contenedores Docker y un Application Load Balancer.

## 📋 Prerrequisitos

### Software Requerido
- **Node.js** (v18 o superior)
- **Docker Desktop** 
- **AWS CLI** v2
- **Terraform** (v1.0 o superior)

### Configuración de AWS
1. Configura AWS CLI con tus credenciales:
   ```bash
   aws configure
   ```
   
2. Asegúrate de tener los siguientes permisos IAM:
   - ECR (Elastic Container Registry)
   - ECS (Elastic Container Service)  
   - EC2 (VPC, Subnets, Security Groups)
   - ELB (Application Load Balancer)
   - IAM (Roles y Policies)
   - CloudWatch (Log Groups)

## 🚀 Despliegue Automático

### Opción 1: Script PowerShell (Windows)
```powershell
# Ejecutar desde el directorio raíz del proyecto
.\deploy.ps1
```

### Opción 2: Script Bash (Linux/Mac/WSL)
```bash
# Hacer el script ejecutable
chmod +x deploy.sh

# Ejecutar desde el directorio raíz del proyecto
./deploy.sh
```

## 🔧 Despliegue Manual Paso a Paso

### 1. Construir la Aplicación Angular
```bash
# Instalar dependencias
npm ci

# Construir la aplicación para producción
npm run build
```

### 2. Construir y Subir Imagen Docker

```bash
# Variables de configuración
APP_NAME="reportes-app"
AWS_REGION="us-east-1"
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
ECR_REPOSITORY_URI="$AWS_ACCOUNT_ID.dkr.ecr.$AWS_REGION.amazonaws.com/$APP_NAME"

# Crear repositorio ECR
aws ecr create-repository --repository-name $APP_NAME --region $AWS_REGION

# Autenticar Docker con ECR
aws ecr get-login-password --region $AWS_REGION | docker login --username AWS --password-stdin $ECR_REPOSITORY_URI

# Construir imagen
docker build -t $APP_NAME:latest .

# Etiquetar imagen
docker tag $APP_NAME:latest $ECR_REPOSITORY_URI:latest

# Subir imagen
docker push $ECR_REPOSITORY_URI:latest
```

### 3. Desplegar Infraestructura con Terraform

```bash
# Ir al directorio de Terraform
cd terraform

# Inicializar Terraform
terraform init

# Revisar el plan de despliegue
terraform plan

# Aplicar la infraestructura
terraform apply
```

### 4. Verificar el Despliegue

```bash
# Obtener la URL del Load Balancer
terraform output load_balancer_url

# Verificar el estado del servicio ECS
aws ecs describe-services --cluster reportes-app-cluster --services reportes-app-service --region us-east-1
```

## 🏗️ Arquitectura Desplegada

La infraestructura incluye:

### Componentes de Red
- **VPC** con CIDR 10.0.0.0/16
- **2 Subnets Públicas** en diferentes AZs
- **Internet Gateway** para acceso público
- **Route Tables** configuradas correctamente

### Seguridad
- **Security Group para ALB**: Permite tráfico HTTP (80) y HTTPS (443)
- **Security Group para ECS**: Permite tráfico solo desde el ALB

### Contenedores y Compute
- **ECR Repository**: Para almacenar imágenes Docker
- **ECS Cluster**: Cluster de Fargate
- **ECS Task Definition**: Definición de la tarea con 256 CPU y 512 MB RAM
- **ECS Service**: Servicio con 2 instancias en modo Fargate

### Load Balancing
- **Application Load Balancer**: Distribuye tráfico entre instancias
- **Target Group**: Agrupa las tareas ECS
- **Health Checks**: Verificación en la ruta `/`

### Observabilidad
- **CloudWatch Log Group**: Para logs de la aplicación
- **Container Insights**: Métricas avanzadas del cluster

## 📊 Monitoreo y Logs

### Acceder a los Logs
```bash
# Ver logs del servicio
aws logs tail /ecs/reportes-app --follow --region us-east-1
```

### Verificar Estado del Servicio
```bash
# Estado del cluster
aws ecs describe-clusters --clusters reportes-app-cluster --region us-east-1

# Estado del servicio
aws ecs describe-services --cluster reportes-app-cluster --services reportes-app-service --region us-east-1

# Listar tareas en ejecución
aws ecs list-tasks --cluster reportes-app-cluster --service-name reportes-app-service --region us-east-1
```

## 🔄 Actualizaciones y Rollbacks

### Actualizar la Aplicación
1. Realiza cambios en el código
2. Ejecuta el script de despliegue nuevamente:
   ```bash
   ./deploy.ps1  # Windows
   ./deploy.sh   # Linux/Mac
   ```

### Rollback Manual
```bash
# Listar revisiones de la task definition
aws ecs list-task-definitions --family-prefix reportes-app --region us-east-1

# Actualizar servicio con revisión anterior
aws ecs update-service \
  --cluster reportes-app-cluster \
  --service reportes-app-service \
  --task-definition reportes-app:REVISION_NUMBER \
  --region us-east-1
```

## 🗑️ Limpieza de Recursos

Para eliminar todos los recursos creados:

```bash
cd terraform
terraform destroy
```

**⚠️ Advertencia**: Esto eliminará todos los recursos y puede resultar en pérdida de datos.

## 🔧 Personalización

### Modificar Recursos
Edita el archivo `terraform/terraform.tfvars`:

```hcl
aws_region = "us-west-2"  # Cambiar región
app_name = "mi-app"       # Cambiar nombre de la app
environment = "staging"   # Cambiar entorno
```

### Ajustar Capacidad
En `terraform/main.tf`, modifica la task definition:

```hcl
resource "aws_ecs_task_definition" "app" {
  cpu    = 512  # Aumentar CPU
  memory = 1024 # Aumentar memoria
  # ...
}
```

### Cambiar Número de Instancias
```hcl
resource "aws_ecs_service" "app" {
  desired_count = 3  # Cambiar número de instancias
  # ...
}
```

## 🆘 Resolución de Problemas

### Problemas Comunes

1. **Error de autenticación con ECR**
   ```bash
   aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin $ECR_REPOSITORY_URI
   ```

2. **Servicio no se despliega**
   - Verificar logs en CloudWatch
   - Revisar Security Groups
   - Verificar que la imagen existe en ECR

3. **Health checks fallan**
   - Verificar que la aplicación responde en el puerto 80
   - Revisar la configuración de nginx
   - Verificar la ruta de health check (`/`)

### Comandos de Diagnóstico
```bash
# Verificar estado de la task
aws ecs describe-tasks --cluster reportes-app-cluster --tasks TASK_ARN --region us-east-1

# Ver eventos del servicio
aws ecs describe-services --cluster reportes-app-cluster --services reportes-app-service --region us-east-1 --query 'services[0].events'

# Verificar target group health
aws elbv2 describe-target-health --target-group-arn TARGET_GROUP_ARN --region us-east-1
```

## 📞 Soporte

Si tienes problemas:
1. Revisa los logs en CloudWatch
2. Verifica la configuración de AWS CLI
3. Asegúrate de tener los permisos necesarios
4. Consulta la documentación oficial de AWS ECS/Fargate
