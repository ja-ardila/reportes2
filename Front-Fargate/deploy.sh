#!/bin/bash

# Script para construir y desplegar la aplicación Angular en AWS Fargate

set -e

# Variables
APP_NAME="reportes-frontend"
AWS_REGION="us-east-1"
IMAGE_TAG="latest"

echo "🚀 Iniciando proceso de despliegue de ${APP_NAME}..."

# 1. Inicializar Terraform
echo "📦 Inicializando Terraform..."
terraform init

# 2. Crear infraestructura básica (VPC, ECR, etc.)
echo "🏗️  Creando infraestructura base..."
terraform apply -auto-approve

# 3. Obtener URL del repositorio ECR
ECR_URL=$(terraform output -raw ecr_repository_url)
echo "📋 Repositorio ECR: ${ECR_URL}"

# 4. Autenticarse con ECR
echo "🔐 Autenticando con ECR..."
aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${ECR_URL}

# 5. Construir imagen Docker
echo "🔨 Construyendo imagen Docker..."
cd reportes-app
docker build -t ${APP_NAME}:${IMAGE_TAG} .

# 6. Etiquetar imagen para ECR
echo "🏷️  Etiquetando imagen para ECR..."
docker tag ${APP_NAME}:${IMAGE_TAG} ${ECR_URL}:${IMAGE_TAG}

# 7. Subir imagen a ECR
echo "📤 Subiendo imagen a ECR..."
docker push ${ECR_URL}:${IMAGE_TAG}

# 8. Volver al directorio raíz
cd ..

# 9. Actualizar infraestructura con la imagen
echo "🚀 Desplegando servicio ECS..."
terraform apply -var="ecr_repository_url=${ECR_URL}" -auto-approve

# 10. Mostrar información de despliegue
echo "✅ Despliegue completado!"
echo "🌐 URL de la aplicación: $(terraform output -raw load_balancer_url)"
echo "📊 Cluster ECS: $(terraform output -raw ecs_cluster_name)"
echo "🔧 Servicio ECS: $(terraform output -raw ecs_service_name)"

echo "⏳ La aplicación puede tardar unos minutos en estar disponible mientras se descargan las imágenes y se inician los contenedores."
