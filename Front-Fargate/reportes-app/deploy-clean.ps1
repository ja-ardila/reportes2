# Script de despliegue para AWS Fargate (PowerShell)
# Este script automatiza todo el proceso de despliegue en Windows

param(
    [string]$AppName = "reportes-app",
    [string]$AwsRegion = "us-east-1"
)

# Variables globales
$script:AwsAccountId = $null

# Configuración de colores para output
$Host.UI.RawUI.WindowTitle = "Despliegue AWS Fargate - $AppName"

Write-Host "🚀 Iniciando despliegue de $AppName en AWS Fargate..." -ForegroundColor Green
Write-Host "📍 Región: $AwsRegion" -ForegroundColor Cyan
Write-Host "📦 Aplicación: $AppName" -ForegroundColor Cyan

# Asegurar que AWS CLI y Terraform están en el PATH
if (-not (Get-Command aws -ErrorAction SilentlyContinue)) {
    $env:PATH += ";C:\Program Files\Amazon\AWSCLIV2"
}

if (-not (Get-Command terraform -ErrorAction SilentlyContinue)) {
    $terraformPath = Join-Path $PSScriptRoot "terraform"
    if (Test-Path $terraformPath) {
        $env:PATH += ";$terraformPath"
    }
}

# Función para verificar si AWS CLI está configurado
function Test-AwsCli {
    Write-Host "🔍 Verificando configuración de AWS CLI..." -ForegroundColor Yellow
    try {
        $identity = aws sts get-caller-identity --output json 2>$null | ConvertFrom-Json
        if ($identity -and $identity.Account) {
            $script:AwsAccountId = $identity.Account
            Write-Host "✅ AWS CLI configurado correctamente" -ForegroundColor Green
            Write-Host "🏢 Account ID: $script:AwsAccountId" -ForegroundColor Cyan
            Write-Host "👤 Usuario: $($identity.Arn)" -ForegroundColor Cyan
            return $true
        } else {
            throw "No se pudo obtener la identidad de AWS"
        }
    }
    catch {
        Write-Host "❌ AWS CLI no está configurado correctamente" -ForegroundColor Red
        Write-Host "Por favor ejecuta: aws configure" -ForegroundColor Yellow
        Write-Host "Error: $_" -ForegroundColor Red
        return $false
    }
}

# Función para verificar que Docker está funcionando
function Test-Docker {
    Write-Host "🐳 Verificando Docker..." -ForegroundColor Yellow
    try {
        $dockerVersion = docker --version 2>$null
        if ($dockerVersion) {
            Write-Host "✅ Docker disponible: $dockerVersion" -ForegroundColor Green
            # Verificar que Docker está ejecutándose
            docker info 2>$null | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✅ Docker está ejecutándose correctamente" -ForegroundColor Green
                return $true
            } else {
                Write-Host "❌ Docker no está ejecutándose. Por favor inicia Docker Desktop" -ForegroundColor Red
                return $false
            }
        } else {
            Write-Host "❌ Docker no está instalado" -ForegroundColor Red
            return $false
        }
    }
    catch {
        Write-Host "❌ Error verificando Docker: $_" -ForegroundColor Red
        return $false
    }
}

# Función para construir y pushear imagen Docker
function Build-AndPushImage {
    Write-Host "🏗️ Construyendo imagen Docker..." -ForegroundColor Yellow
    
    $EcrRepositoryUri = "$script:AwsAccountId.dkr.ecr.$AwsRegion.amazonaws.com/$AppName"
    
    try {
        # Verificar que package.json existe
        if (-not (Test-Path "package.json")) {
            throw "No se encontró package.json en el directorio actual"
        }

        # Construir la aplicación Angular (usando la imagen Docker que ya lo hace)
        Write-Host "🐳 Construyendo imagen Docker..." -ForegroundColor Yellow
        docker build -t "${AppName}:latest" .
        if ($LASTEXITCODE -ne 0) { throw "Error construyendo imagen Docker" }
        
        # Crear repositorio ECR si no existe
        Write-Host "📋 Verificando repositorio ECR..." -ForegroundColor Yellow
        $repoExists = $false
        try {
            $repoInfo = aws ecr describe-repositories --repository-names $AppName --region $AwsRegion 2>$null | ConvertFrom-Json
            $repoExists = ($repoInfo -and $repoInfo.repositories)
        }
        catch {
            $repoExists = $false
        }
        
        if (-not $repoExists) {
            Write-Host "📦 Creando repositorio ECR..." -ForegroundColor Yellow
            aws ecr create-repository --repository-name $AppName --image-scanning-configuration scanOnPush=true --region $AwsRegion
            if ($LASTEXITCODE -ne 0) { throw "Error creando repositorio ECR" }
        } else {
            Write-Host "✅ Repositorio ECR ya existe" -ForegroundColor Green
        }
        
        # Configurar Docker para ECR
        Write-Host "🔑 Autenticando Docker con ECR..." -ForegroundColor Yellow
        $loginCommand = aws ecr get-login-password --region $AwsRegion
        if ($LASTEXITCODE -ne 0) { throw "Error obteniendo token de login de ECR" }
        
        Write-Output $loginCommand | docker login --username AWS --password-stdin $EcrRepositoryUri
        if ($LASTEXITCODE -ne 0) { throw "Error autenticando con ECR" }
        
        # Etiquetar y pushear imagen
        Write-Host "🏷️ Etiquetando imagen..." -ForegroundColor Yellow
        docker tag "${AppName}:latest" "${EcrRepositoryUri}:latest"
        if ($LASTEXITCODE -ne 0) { throw "Error etiquetando imagen" }
        
        Write-Host "⬆️ Subiendo imagen a ECR..." -ForegroundColor Yellow
        docker push "${EcrRepositoryUri}:latest"
        if ($LASTEXITCODE -ne 0) { throw "Error subiendo imagen" }
        
        Write-Host "✅ Imagen subida exitosamente" -ForegroundColor Green
        return $EcrRepositoryUri
    }
    catch {
        Write-Host "❌ Error en el proceso de construcción: $_" -ForegroundColor Red
        throw
    }
}

# Función para desplegar infraestructura con Terraform
function Deploy-Infrastructure {
    Write-Host "🏗️ Desplegando infraestructura con Terraform..." -ForegroundColor Yellow
    
    try {
        $terraformDir = "terraform"
        if (-not (Test-Path $terraformDir)) {
            throw "No se encontró el directorio terraform"
        }
        
        Push-Location $terraformDir
        
        # Verificar que existe main.tf
        if (-not (Test-Path "main.tf")) {
            throw "No se encontró main.tf en el directorio terraform"
        }
        
        # Inicializar Terraform
        Write-Host "🔧 Inicializando Terraform..." -ForegroundColor Yellow
        terraform init
        if ($LASTEXITCODE -ne 0) { throw "Error inicializando Terraform" }
        
        # Validar configuración
        Write-Host "✅ Validando configuración de Terraform..." -ForegroundColor Yellow
        terraform validate
        if ($LASTEXITCODE -ne 0) { throw "Error validando configuración de Terraform" }
        
        # Planificar despliegue
        Write-Host "📋 Planificando despliegue..." -ForegroundColor Yellow
        terraform plan -var="aws_region=$AwsRegion" -var="app_name=$AppName"
        if ($LASTEXITCODE -ne 0) { throw "Error planificando despliegue" }
        
        # Aplicar cambios
        Write-Host "🚀 Aplicando infraestructura..." -ForegroundColor Yellow
        terraform apply -auto-approve -var="aws_region=$AwsRegion" -var="app_name=$AppName"
        if ($LASTEXITCODE -ne 0) { throw "Error aplicando infraestructura" }
        
        # Obtener outputs
        Write-Host "📤 Obteniendo información de despliegue..." -ForegroundColor Yellow
        $ecrUrl = terraform output -raw ecr_repository_url 2>$null
        $albUrl = terraform output -raw load_balancer_url 2>$null
        $clusterName = terraform output -raw ecs_cluster_name 2>$null
        $serviceName = terraform output -raw ecs_service_name 2>$null
        
        Write-Host "✅ Infraestructura desplegada exitosamente" -ForegroundColor Green
        Write-Host "🔗 URL de la aplicación: $albUrl" -ForegroundColor Cyan
        
        return @{
            EcrUrl = $ecrUrl
            AlbUrl = $albUrl
            ClusterName = $clusterName
            ServiceName = $serviceName
        }
    }
    catch {
        Write-Host "❌ Error desplegando infraestructura: $_" -ForegroundColor Red
        throw
    }
    finally {
        Pop-Location
    }
}

# Función para actualizar servicio ECS
function Update-EcsService {
    param(
        [string]$ClusterName,
        [string]$ServiceName
    )
    
    Write-Host "🔄 Actualizando servicio ECS..." -ForegroundColor Yellow
    
    try {
        # Forzar nuevo despliegue
        Write-Host "🔄 Iniciando nuevo despliegue..." -ForegroundColor Yellow
        aws ecs update-service `
            --cluster $ClusterName `
            --service $ServiceName `
            --force-new-deployment `
            --region $AwsRegion
        if ($LASTEXITCODE -ne 0) { throw "Error actualizando servicio ECS" }
        
        Write-Host "⏳ Esperando que el servicio se estabilice..." -ForegroundColor Yellow
        Write-Host "   Esto puede tomar varios minutos..." -ForegroundColor Gray
        
        # Esperar estabilización con timeout
        $timeout = 600 # 10 minutos
        $startTime = Get-Date
        
        do {
            Start-Sleep -Seconds 30
            $elapsed = (Get-Date) - $startTime
            Write-Host "   Tiempo transcurrido: $([math]::Round($elapsed.TotalMinutes, 1)) minutos..." -ForegroundColor Gray
            
            if ($elapsed.TotalSeconds -gt $timeout) {
                Write-Host "⚠️ Timeout esperando estabilización. Verifica manualmente en la consola AWS." -ForegroundColor Yellow
                break
            }
        } while ($elapsed.TotalSeconds -lt $timeout)
        
        # Verificar estado final
        $serviceStatus = aws ecs describe-services --cluster $ClusterName --services $ServiceName --region $AwsRegion --query 'services[0].deployments[0].status' --output text 2>$null
        
        if ($serviceStatus -eq "PRIMARY") {
            Write-Host "✅ Servicio actualizado exitosamente" -ForegroundColor Green
        } else {
            Write-Host "⚠️ El servicio puede estar aún actualizándose. Estado: $serviceStatus" -ForegroundColor Yellow
        }
    }
    catch {
        Write-Host "❌ Error actualizando servicio: $_" -ForegroundColor Red
        throw
    }
}

# Función principal
function Main {
    try {
        Write-Host ""
        Write-Host "=== VERIFICACIONES PREVIAS ===" -ForegroundColor Magenta
        
        if (-not (Test-AwsCli)) {
            return
        }
        
        if (-not (Test-Docker)) {
            return
        }
        
        Write-Host ""
        Write-Host "=== CONSTRUCCIÓN Y PUSH DE IMAGEN ===" -ForegroundColor Magenta
        $ecrUri = Build-AndPushImage
        
        Write-Host ""
        Write-Host "=== DESPLIEGUE DE INFRAESTRUCTURA ===" -ForegroundColor Magenta
        $infraOutputs = Deploy-Infrastructure
        
        Write-Host ""
        Write-Host "=== ACTUALIZACIÓN DE SERVICIO ECS ===" -ForegroundColor Magenta
        Update-EcsService -ClusterName $infraOutputs.ClusterName -ServiceName $infraOutputs.ServiceName
        
        Write-Host ""
        Write-Host "=== RESUMEN DEL DESPLIEGUE ===" -ForegroundColor Magenta
        Write-Host "🎉 ¡Despliegue completado exitosamente!" -ForegroundColor Green
        Write-Host ""
        Write-Host "📋 Información del despliegue:" -ForegroundColor Cyan
        Write-Host "   🔗 URL de la aplicación: $($infraOutputs.AlbUrl)" -ForegroundColor White
        Write-Host "   📦 Repositorio ECR: $ecrUri" -ForegroundColor White
        Write-Host "   🏢 Account ID: $script:AwsAccountId" -ForegroundColor White
        Write-Host "   📍 Región: $AwsRegion" -ForegroundColor White
        Write-Host "   🎯 Cluster ECS: $($infraOutputs.ClusterName)" -ForegroundColor White
        Write-Host "   ⚙️ Servicio ECS: $($infraOutputs.ServiceName)" -ForegroundColor White
        Write-Host ""
        Write-Host "📊 Comandos útiles para monitoreo:" -ForegroundColor Yellow
        Write-Host "   aws ecs describe-services --cluster $($infraOutputs.ClusterName) --services $($infraOutputs.ServiceName) --region $AwsRegion" -ForegroundColor Gray
        Write-Host "   aws logs tail /ecs/$AppName --follow --region $AwsRegion" -ForegroundColor Gray
        Write-Host ""
        Write-Host "⚠️ IMPORTANTE: La aplicación puede tardar unos minutos en estar completamente disponible." -ForegroundColor Yellow
        Write-Host "   Puedes verificar el estado en la consola AWS ECS o usar los comandos de monitoreo." -ForegroundColor Yellow
        
        # Guardar información en archivo
        $deployInfo = @{
            AppName = $AppName
            Region = $AwsRegion
            AccountId = $script:AwsAccountId
            EcrUri = $ecrUri
            AppUrl = $infraOutputs.AlbUrl
            ClusterName = $infraOutputs.ClusterName
            ServiceName = $infraOutputs.ServiceName
            DeploymentTime = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        }
        
        $deployInfo | ConvertTo-Json -Depth 3 | Out-File -FilePath "deployment-info.json" -Encoding UTF8
        Write-Host "📝 Información de despliegue guardada en deployment-info.json" -ForegroundColor Cyan
    }
    catch {
        Write-Host ""
        Write-Host "❌ ERROR EN EL DESPLIEGUE" -ForegroundColor Red
        Write-Host "Error: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "🔧 Pasos para resolución de problemas:" -ForegroundColor Yellow
        Write-Host "1. Verifica que AWS CLI esté configurado: aws sts get-caller-identity" -ForegroundColor Gray
        Write-Host "2. Verifica que Docker esté ejecutándose: docker info" -ForegroundColor Gray
        Write-Host "3. Revisa los logs de CloudWatch si la infraestructura se desplegó" -ForegroundColor Gray
        Write-Host "4. Consulta la documentación en DEPLOYMENT_GUIDE.md" -ForegroundColor Gray
        exit 1
    }
}

# Ejecutar script principal
Main
