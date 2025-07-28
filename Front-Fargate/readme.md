# Despliegue del Frontend en AWS Fargate

Este directorio contiene la configuración necesaria para desplegar la aplicación Angular del sistema de reportes en AWS Fargate usando contenedores Docker.

## Arquitectura

La infraestructura desplegada incluye:

- **VPC** con subnets públicas y privadas
- **Application Load Balancer (ALB)** para distribución de tráfico
- **AWS ECR** para almacenamiento de imágenes Docker
- **AWS ECS con Fargate** para ejecución de contenedores
- **CloudWatch** para logs y monitoreo
- **Security Groups** para control de acceso

## Prerrequisitos

1. **AWS CLI** instalado y configurado
2. **Docker** instalado y funcionando
3. **Terraform** instalado (versión >= 1.0)
4. **Node.js** y **npm** instalados
5. Credenciales de AWS configuradas con permisos para:
   - ECS, ECR, VPC, ALB, IAM, CloudWatch

## Estructura de Archivos

```
Front-Fargate/
├── reportes-app/           # Aplicación Angular
│   ├── Dockerfile         # Imagen Docker de la aplicación
│   ├── nginx.conf         # Configuración de Nginx
│   └── .dockerignore      # Archivos excluidos del build
├── providers.tf           # Configuración del provider AWS
├── variables.tf           # Variables de Terraform
├── terraform.tfvars       # Valores de las variables
├── network.tf            # Configuración de VPC y networking
├── ecr.tf               # Repositorio de contenedores
├── alb.tf               # Load Balancer
├── ecs.tf               # Cluster ECS y servicio Fargate
├── outputs.tf           # Outputs de Terraform
├── deploy.ps1           # Script de despliegue para Windows
├── deploy.sh            # Script de despliegue para Linux/Mac
└── README.md           # Este archivo
```

## Despliegue

### Opción 1: Script Automatizado (Recomendado)

#### Windows (PowerShell)
```powershell
.\deploy.ps1
```

#### Linux/Mac (Bash)
```bash
chmod +x deploy.sh
./deploy.sh
```

### Opción 2: Paso a Paso Manual

1. **Inicializar Terraform**
   ```bash
   terraform init
   ```

2. **Crear infraestructura base**
   ```bash
   terraform plan
   terraform apply
   ```

3. **Obtener URL del ECR**
   ```bash
   ECR_URL=$(terraform output -raw ecr_repository_url)
   ```

4. **Autenticarse con ECR**
   ```bash
   aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin $ECR_URL
   ```

5. **Construir y subir imagen Docker**
   ```bash
   cd reportes-app
   docker build -t reportes-frontend:latest .
   docker tag reportes-frontend:latest $ECR_URL:latest
   docker push $ECR_URL:latest
   cd ..
   ```

6. **Desplegar servicio ECS**
   ```bash
   terraform apply -var="ecr_repository_url=$ECR_URL"
   ```

## Configuración

### Variables principales

- `app_name`: Nombre de la aplicación (default: "reportes-frontend")
- `aws_region`: Región de AWS (default: "us-east-1")
- `container_port`: Puerto del contenedor (default: 80)
- `desired_count`: Número de instancias (default: 2)
- `cpu`: CPU asignada (default: "256")
- `memory`: Memoria asignada (default: "512")

### Personalización

Puedes modificar `terraform.tfvars` para ajustar la configuración:

```hcl
app_name = "mi-frontend"
desired_count = 3
cpu = "512"
memory = "1024"
```

## Outputs

Después del despliegue exitoso, obtendrás:

- **load_balancer_url**: URL de acceso a la aplicación
- **ecr_repository_url**: URL del repositorio ECR
- **ecs_cluster_name**: Nombre del cluster ECS
- **ecs_service_name**: Nombre del servicio ECS

## Monitoreo

- **CloudWatch Logs**: `/ecs/reportes-frontend`
- **Health Check**: `http://load-balancer-url/health`
- **ECS Console**: Para ver el estado de los contenedores

## Actualizaciones

Para actualizar la aplicación:

1. Realiza cambios en el código de `reportes-app/`
2. Ejecuta el script de despliegue nuevamente
3. El sistema actualizará automáticamente los contenedores

## Limpieza

Para eliminar todos los recursos:

```bash
terraform destroy
```

## Troubleshooting

### Problemas comunes

1. **Error de autenticación con ECR**
   - Verifica que AWS CLI esté configurado correctamente
   - Asegúrate de tener permisos para ECR

2. **Contenedores no inician**
   - Revisa los logs en CloudWatch
   - Verifica que el health check endpoint `/health` funcione

3. **Load Balancer no responde**
   - Espera 2-5 minutos para que los target groups se registren
   - Verifica los security groups

4. **Error de construcción Docker**
   - Asegúrate de que Docker esté funcionando
   - Verifica que `npm run build` funcione localmente

## Costos Estimados

El costo aproximado mensual incluye:
- ECS Fargate: ~$15-30/mes (dependiendo del uso)
- ALB: ~$18/mes
- NAT Gateway: ~$32/mes
- CloudWatch: ~$5/mes

Total estimado: ~$70-85/mes para ambiente de producción

## Seguridad

- Los contenedores se ejecutan en subnets privadas
- Solo el ALB tiene acceso público
- Security groups restringen el tráfico
- Logs centralizados en CloudWatch
