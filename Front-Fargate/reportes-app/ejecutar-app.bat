@echo off
echo ========================================
echo   Aplicacion Angular de Reportes H323
echo ========================================
echo.
cd /d "c:\Users\lucia\OneDrive\Documentos\Maestria\reportes2\Front-Fargate\reportes-app"

echo Verificando archivos de configuracion...
if not exist "tsconfig.json" (
    echo ERROR: Falta tsconfig.json
    goto :error
)
if not exist "tsconfig.app.json" (
    echo ERROR: Falta tsconfig.app.json  
    goto :error
)
if not exist "angular.json" (
    echo ERROR: Falta angular.json
    goto :error
)
if not exist "src\main.ts" (
    echo ERROR: Falta src\main.ts
    goto :error
)

echo ✓ Archivos de configuracion OK
echo.
echo Instalando dependencias...
call npm install
if errorlevel 1 goto :error

echo.
echo Iniciando servidor de desarrollo...
echo La aplicacion se abrira en http://localhost:4200
echo Presiona Ctrl+C para detener el servidor
echo.
call ng serve --open --port 4200
goto :end

:error
echo.
echo ERROR: No se pudo ejecutar la aplicacion
echo.
echo Posibles soluciones:
echo 1. Instalar Angular CLI: npm install -g @angular/cli
echo 2. Verificar que Node.js este instalado
echo 3. Ejecutar desde VS Code con 'ng serve'
echo.

:end
pause
