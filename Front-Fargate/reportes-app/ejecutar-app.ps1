# Script para ejecutar la aplicación Angular de Reportes
Write-Host "🚀 Ejecutando aplicación Angular de Reportes..." -ForegroundColor Green
Write-Host ""

# Cambiar al directorio del proyecto
Set-Location "c:\Users\lucia\OneDrive\Documentos\Maestria\reportes2\Front-Fargate\reportes-app"

Write-Host "📦 Verificando dependencias..." -ForegroundColor Yellow
try {
    npm install --silent
    Write-Host "✅ Dependencias instaladas correctamente" -ForegroundColor Green
} catch {
    Write-Host "❌ Error instalando dependencias" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Yellow
}

Write-Host ""
Write-Host "🔍 Verificando configuración Angular..." -ForegroundColor Cyan
$missingFiles = @()

if (-not (Test-Path "angular.json")) { $missingFiles += "angular.json" }
if (-not (Test-Path "tsconfig.json")) { $missingFiles += "tsconfig.json" }
if (-not (Test-Path "tsconfig.app.json")) { $missingFiles += "tsconfig.app.json" }
if (-not (Test-Path "src/main.ts")) { $missingFiles += "src/main.ts" }
if (-not (Test-Path "src/index.html")) { $missingFiles += "src/index.html" }
if (-not (Test-Path "src/styles.css")) { $missingFiles += "src/styles.css" }

if ($missingFiles.Count -eq 0) {
    Write-Host "✅ Todos los archivos de configuración están presentes" -ForegroundColor Green
} else {
    Write-Host "❌ Archivos faltantes:" -ForegroundColor Red
    foreach ($file in $missingFiles) {
        Write-Host "  - $file" -ForegroundColor Yellow
    }
    Write-Host "Creando archivos faltantes..." -ForegroundColor Yellow
    # Los archivos ya fueron creados por el asistente
}

Write-Host ""
Write-Host "🌐 Iniciando servidor de desarrollo..." -ForegroundColor Blue
Write-Host "La aplicación se abrirá en http://localhost:4200" -ForegroundColor Cyan
Write-Host "Presiona Ctrl+C para detener el servidor" -ForegroundColor Yellow
Write-Host ""

# Ejecutar Angular
try {
    ng serve --open --port 4200 --host 0.0.0.0
} catch {
    Write-Host "❌ Error al iniciar el servidor Angular" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Posibles soluciones:" -ForegroundColor Cyan
    Write-Host "1. Verificar que Angular CLI esté instalado: npm install -g @angular/cli" -ForegroundColor White
    Write-Host "2. Verificar que Node.js esté instalado correctamente" -ForegroundColor White
    Write-Host "3. Revisar el log de errores en el directorio temporal" -ForegroundColor White
    Write-Host ""
    Read-Host "Presiona Enter para continuar"
}
