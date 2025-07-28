#!/bin/bash

# Script de despliegue para AWS Fargate
# Este script automatiza todo el proceso de despliegue

set -e

# Variables de configuración
APP_NAME="reportes-app"
AWS_REGION="us-east-1"
AWS_ACCOUNT_ID=$(aws sts get-caller-identity --query Account --output text)
ECR_REPOSITORY_URI="${AWS_ACCOUNT_ID}.dkr.ecr.${AWS_REGION}.amazonaws.com/${APP_NAME}"

echo "🚀 Iniciando despliegue de ${APP_NAME} en AWS Fargate..."
echo "📍 Región: ${AWS_REGION}"
echo "🏢 Account ID: ${AWS_ACCOUNT_ID}"
echo "📦 ECR Repository: ${ECR_REPOSITORY_URI}"

# Función para verificar si AWS CLI está configurado
check_aws_cli() {
    echo "🔍 Verificando configuración de AWS CLI..."
    if ! aws sts get-caller-identity > /dev/null 2>&1; then
        echo "❌ AWS CLI no está configurado correctamente"
        echo "Por favor ejecuta: aws configure"
        exit 1
    fi
    echo "✅ AWS CLI configurado correctamente"
}

# Función para construir y pushear imagen Docker
build_and_push_image() {
    echo "🏗️ Construyendo imagen Docker..."
    
    # Construir la aplicación Angular
    echo "📦 Instalando dependencias de Node.js..."
    npm ci
    
    echo "🔨 Construyendo aplicación Angular..."
    npm run build
    
    # Construir imagen Docker
    echo "🐳 Construyendo imagen Docker..."
    docker build -t ${APP_NAME}:latest .
    
    # Configurar Docker para ECR
    echo "🔑 Autenticando Docker con ECR..."
    aws ecr get-login-password --region ${AWS_REGION} | docker login --username AWS --password-stdin ${ECR_REPOSITORY_URI}
    
    # Crear repositorio ECR si no existe
    echo "📋 Verificando repositorio ECR..."
    if ! aws ecr describe-repositories --repository-names ${APP_NAME} --region ${AWS_REGION} > /dev/null 2>&1; then
        echo "📦 Creando repositorio ECR..."
        aws ecr create-repository --repository-name ${APP_NAME} --region ${AWS_REGION}
    fi
    
    # Etiquetar y pushear imagen
    echo "🏷️ Etiquetando imagen..."
    docker tag ${APP_NAME}:latest ${ECR_REPOSITORY_URI}:latest
    
    echo "⬆️ Subiendo imagen a ECR..."
    docker push ${ECR_REPOSITORY_URI}:latest
    
    echo "✅ Imagen subida exitosamente"
}

# Función para desplegar infraestructura con Terraform
deploy_infrastructure() {
    echo "🏗️ Desplegando infraestructura con Terraform..."
    
    cd terraform
    
    # Inicializar Terraform
    echo "🔧 Inicializando Terraform..."
    terraform init
    
    # Planificar despliegue
    echo "📋 Planificando despliegue..."
    terraform plan
    
    # Aplicar cambios
    echo "🚀 Aplicando infraestructura..."
    terraform apply -auto-approve
    
    # Obtener outputs
    echo "📤 Obteniendo información de despliegue..."
    ECR_URL=$(terraform output -raw ecr_repository_url)
    ALB_URL=$(terraform output -raw load_balancer_url)
    
    echo "✅ Infraestructura desplegada exitosamente"
    echo "🔗 URL de la aplicación: ${ALB_URL}"
    
    cd ..
}

# Función para actualizar servicio ECS
update_ecs_service() {
    echo "🔄 Actualizando servicio ECS..."
    
    CLUSTER_NAME="${APP_NAME}-cluster"
    SERVICE_NAME="${APP_NAME}-service"
    
    # Forzar nuevo despliegue
    aws ecs update-service \
        --cluster ${CLUSTER_NAME} \
        --service ${SERVICE_NAME} \
        --force-new-deployment \
        --region ${AWS_REGION}
    
    echo "⏳ Esperando que el servicio se estabilice..."
    aws ecs wait services-stable \
        --cluster ${CLUSTER_NAME} \
        --services ${SERVICE_NAME} \
        --region ${AWS_REGION}
    
    echo "✅ Servicio actualizado exitosamente"
}

# Función principal
main() {
    check_aws_cli
    build_and_push_image
    deploy_infrastructure
    update_ecs_service
    
    echo ""
    echo "🎉 ¡Despliegue completado exitosamente!"
    echo "🔗 Tu aplicación estará disponible en: $(cd terraform && terraform output -raw load_balancer_url)"
    echo "📊 Puedes monitorear el estado en la consola AWS ECS"
}

# Ejecutar script principal
main "$@"
