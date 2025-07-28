# Script de PowerShell para construir y desplegar la aplicación Angular en AWS Fargate

param(
    [string]$AppName = "reportes-frontend",
    [string]$AwsRegion = "us-east-1",
    [string]$ImageTag = "latest"
)

$ErrorActionPreference = "Stop"

Write-Host "🚀 Iniciando proceso de despliegue de $AppName..." -ForegroundColor Green

try {
    # 1. Inicializar Terraform
    Write-Host "📦 Inicializando Terraform..." -ForegroundColor Yellow
    terraform init

    # 2. Crear infraestructura básica (VPC, ECR, etc.)
    Write-Host "🏗️  Creando infraestructura base..." -ForegroundColor Yellow
    terraform apply -auto-approve

    # 3. Obtener URL del repositorio ECR
    $EcrUrl = terraform output -raw ecr_repository_url
    Write-Host "📋 Repositorio ECR: $EcrUrl" -ForegroundColor Cyan

    # 4. Autenticarse con ECR
    Write-Host "🔐 Autenticando con ECR..." -ForegroundColor Yellow
    $LoginToken = aws ecr get-login-password --region $AwsRegion
    $LoginToken | docker login --username AWS --password-stdin $EcrUrl

    # 5. Construir imagen Docker
    Write-Host "🔨 Construyendo imagen Docker..." -ForegroundColor Yellow
    Set-Location -Path "reportes-app"
    docker build -t "${AppName}:${ImageTag}" .

    # 6. Etiquetar imagen para ECR
    Write-Host "🏷️  Etiquetando imagen para ECR..." -ForegroundColor Yellow
    docker tag "${AppName}:${ImageTag}" "${EcrUrl}:${ImageTag}"

    # 7. Subir imagen a ECR
    Write-Host "📤 Subiendo imagen a ECR..." -ForegroundColor Yellow
    docker push "${EcrUrl}:${ImageTag}"

    # 8. Volver al directorio raíz
    Set-Location -Path ".."

    # 9. Actualizar infraestructura con la imagen
    Write-Host "🚀 Desplegando servicio ECS..." -ForegroundColor Yellow
    terraform apply -var="ecr_repository_url=$EcrUrl" -auto-approve

    # 10. Mostrar información de despliegue
    $LoadBalancerUrl = terraform output -raw load_balancer_url
    $ClusterName = terraform output -raw ecs_cluster_name
    $ServiceName = terraform output -raw ecs_service_name

    Write-Host "✅ Despliegue completado!" -ForegroundColor Green
    Write-Host "🌐 URL de la aplicación: $LoadBalancerUrl" -ForegroundColor Cyan
    Write-Host "📊 Cluster ECS: $ClusterName" -ForegroundColor Cyan
    Write-Host "🔧 Servicio ECS: $ServiceName" -ForegroundColor Cyan
    Write-Host "⏳ La aplicación puede tardar unos minutos en estar disponible mientras se descargan las imágenes y se inician los contenedores." -ForegroundColor Yellow

} catch {
    Write-Host "❌ Error durante el despliegue: $_" -ForegroundColor Red
    exit 1
}
