# Script de verificación de prerrequisitos
# Verifica que todos los componentes necesarios estén instalados y configurados

Write-Host "🔍 VERIFICACIÓN DE PRERREQUISITOS PARA AWS FARGATE" -ForegroundColor Magenta
Write-Host "=================================================" -ForegroundColor Magenta
Write-Host ""

# Configurar PATH si es necesario
if (-not (Get-Command aws -ErrorAction SilentlyContinue)) {
    $env:PATH += ";C:\Program Files\Amazon\AWSCLIV2"
}

if (-not (Get-Command terraform -ErrorAction SilentlyContinue)) {
    $terraformPath = Join-Path $PSScriptRoot "terraform"
    if (Test-Path $terraformPath) {
        $env:PATH += ";$terraformPath"
    }
}

$allChecksPass = $true

# Verificar Node.js
Write-Host "📦 Verificando Node.js..." -ForegroundColor Yellow
try {
    $nodeVersion = node --version 2>$null
    if ($nodeVersion) {
        Write-Host "   ✅ Node.js instalado: $nodeVersion" -ForegroundColor Green
        
        # Verificar npm
        $npmVersion = npm --version 2>$null
        if ($npmVersion) {
            Write-Host "   ✅ npm disponible: v$npmVersion" -ForegroundColor Green
        } else {
            Write-Host "   ❌ npm no está disponible" -ForegroundColor Red
            $allChecksPass = $false
        }
    } else {
        Write-Host "   ❌ Node.js no está instalado" -ForegroundColor Red
        Write-Host "   💡 Instala desde: https://nodejs.org/" -ForegroundColor Yellow
        $allChecksPass = $false
    }
} catch {
    Write-Host "   ❌ Error verificando Node.js: $_" -ForegroundColor Red
    $allChecksPass = $false
}

Write-Host ""

# Verificar Docker
Write-Host "🐳 Verificando Docker..." -ForegroundColor Yellow
try {
    $dockerVersion = docker --version 2>$null
    if ($dockerVersion) {
        Write-Host "   ✅ Docker instalado: $dockerVersion" -ForegroundColor Green
        
        # Verificar que Docker está ejecutándose
        $dockerInfo = docker info 2>$null
        if ($LASTEXITCODE -eq 0) {
            Write-Host "   ✅ Docker está ejecutándose correctamente" -ForegroundColor Green
        } else {
            Write-Host "   ⚠️ Docker está instalado pero no se está ejecutando" -ForegroundColor Yellow
            Write-Host "   💡 Inicia Docker Desktop" -ForegroundColor Yellow
            $allChecksPass = $false
        }
    } else {
        Write-Host "   ❌ Docker no está instalado" -ForegroundColor Red
        Write-Host "   💡 Instala desde: https://docker.com/get-started" -ForegroundColor Yellow
        $allChecksPass = $false
    }
} catch {
    Write-Host "   ❌ Error verificando Docker: $_" -ForegroundColor Red
    $allChecksPass = $false
}

Write-Host ""

# Verificar AWS CLI
Write-Host "☁️ Verificando AWS CLI..." -ForegroundColor Yellow
try {
    $awsVersion = aws --version 2>$null
    if ($awsVersion) {
        Write-Host "   ✅ AWS CLI instalado: $awsVersion" -ForegroundColor Green
        
        # Verificar configuración
        try {
            $identity = aws sts get-caller-identity --output json 2>$null | ConvertFrom-Json
            if ($identity -and $identity.Account) {
                Write-Host "   ✅ AWS CLI configurado correctamente" -ForegroundColor Green
                Write-Host "   🏢 Account ID: $($identity.Account)" -ForegroundColor Cyan
                Write-Host "   👤 Usuario: $($identity.Arn)" -ForegroundColor Cyan
                
                # Verificar región
                $region = aws configure get region 2>$null
                if ($region) {
                    Write-Host "   🌍 Región configurada: $region" -ForegroundColor Cyan
                } else {
                    Write-Host "   ⚠️ No hay región configurada por defecto" -ForegroundColor Yellow
                }
            } else {
                Write-Host "   ❌ AWS CLI no está configurado correctamente" -ForegroundColor Red
                Write-Host "   💡 Ejecuta: aws configure" -ForegroundColor Yellow
                $allChecksPass = $false
            }
        } catch {
            Write-Host "   ❌ AWS CLI no está configurado correctamente" -ForegroundColor Red
            Write-Host "   💡 Ejecuta: aws configure" -ForegroundColor Yellow
            $allChecksPass = $false
        }
    } else {
        Write-Host "   ❌ AWS CLI no está instalado" -ForegroundColor Red
        Write-Host "   💡 Instala desde: https://aws.amazon.com/cli/" -ForegroundColor Yellow
        $allChecksPass = $false
    }
} catch {
    Write-Host "   ❌ Error verificando AWS CLI: $_" -ForegroundColor Red
    $allChecksPass = $false
}

Write-Host ""

# Verificar Terraform
Write-Host "🏗️ Verificando Terraform..." -ForegroundColor Yellow
try {
    $terraformVersion = terraform --version 2>$null
    if ($terraformVersion) {
        $versionLine = ($terraformVersion -split "`n")[0]
        Write-Host "   ✅ Terraform instalado: $versionLine" -ForegroundColor Green
    } else {
        Write-Host "   ❌ Terraform no está instalado" -ForegroundColor Red
        Write-Host "   💡 Se descargará automáticamente durante el despliegue" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   ❌ Error verificando Terraform: $_" -ForegroundColor Red
}

Write-Host ""

# Verificar estructura del proyecto
Write-Host "📁 Verificando estructura del proyecto..." -ForegroundColor Yellow

$requiredFiles = @(
    "package.json",
    "Dockerfile", 
    "nginx.conf",
    "terraform/main.tf"
)

$missingFiles = @()
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "   ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "   ❌ $file (faltante)" -ForegroundColor Red
        $missingFiles += $file
        $allChecksPass = $false
    }
}

if ($missingFiles.Count -eq 0) {
    Write-Host "   ✅ Todos los archivos necesarios están presentes" -ForegroundColor Green
}

Write-Host ""

# Verificar permisos AWS (opcional)
if ($allChecksPass) {
    Write-Host "🔐 Verificando permisos AWS básicos..." -ForegroundColor Yellow
    
    $services = @("ecr", "ecs", "ec2", "elasticloadbalancing", "iam", "logs")
    $permissionIssues = @()
    
    foreach ($service in $services) {
        try {
            switch ($service) {
                "ecr" { 
                    aws ecr describe-repositories --max-items 1 --region us-east-1 2>$null | Out-Null
                    if ($LASTEXITCODE -eq 0) {
                        Write-Host "   ✅ ECR: OK" -ForegroundColor Green
                    } else {
                        Write-Host "   ⚠️ ECR: Posibles problemas de permisos" -ForegroundColor Yellow
                        $permissionIssues += "ECR"
                    }
                }
                "ecs" { 
                    aws ecs list-clusters --max-items 1 --region us-east-1 2>$null | Out-Null
                    if ($LASTEXITCODE -eq 0) {
                        Write-Host "   ✅ ECS: OK" -ForegroundColor Green
                    } else {
                        Write-Host "   ⚠️ ECS: Posibles problemas de permisos" -ForegroundColor Yellow
                        $permissionIssues += "ECS"
                    }
                }
                "ec2" { 
                    aws ec2 describe-vpcs --max-items 1 --region us-east-1 2>$null | Out-Null
                    if ($LASTEXITCODE -eq 0) {
                        Write-Host "   ✅ EC2: OK" -ForegroundColor Green
                    } else {
                        Write-Host "   ⚠️ EC2: Posibles problemas de permisos" -ForegroundColor Yellow
                        $permissionIssues += "EC2"
                    }
                }
            }
        } catch {
            Write-Host "   ⚠️ $service`: Error verificando permisos" -ForegroundColor Yellow
            $permissionIssues += $service
        }
    }
    
    if ($permissionIssues.Count -gt 0) {
        Write-Host "   ⚠️ Posibles problemas de permisos en: $($permissionIssues -join ', ')" -ForegroundColor Yellow
        Write-Host "   💡 Asegúrate de tener permisos adecuados para estos servicios" -ForegroundColor Yellow
    }
}

Write-Host ""
Write-Host "=== RESUMEN ===" -ForegroundColor Magenta

if ($allChecksPass) {
    Write-Host "🎉 ¡Todos los prerrequisitos están cumplidos!" -ForegroundColor Green
    Write-Host ""
    Write-Host "💡 Puedes proceder con el despliegue ejecutando:" -ForegroundColor Cyan
    Write-Host "   .\deploy.ps1" -ForegroundColor White
    Write-Host ""
    Write-Host "📚 Para más información, consulta:" -ForegroundColor Cyan
    Write-Host "   - DEPLOYMENT_GUIDE.md" -ForegroundColor White
    Write-Host "   - https://docs.aws.amazon.com/ecs/latest/developerguide/fargate.html" -ForegroundColor White
} else {
    Write-Host "❌ Algunos prerrequisitos no están cumplidos" -ForegroundColor Red
    Write-Host ""
    Write-Host "🔧 Por favor resuelve los problemas indicados arriba antes de continuar" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "📚 Para ayuda con la instalación:" -ForegroundColor Cyan
    Write-Host "   - Node.js: https://nodejs.org/" -ForegroundColor White
    Write-Host "   - Docker: https://docker.com/get-started" -ForegroundColor White
    Write-Host "   - AWS CLI: https://aws.amazon.com/cli/" -ForegroundColor White
    Write-Host "   - Guía completa: DEPLOYMENT_GUIDE.md" -ForegroundColor White
}

Write-Host ""
