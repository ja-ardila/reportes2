#!/bin/bash

# Script de testing simplificado para lambdas de reportes
# Solo prueba create-reporte y update-reporte

echo "=============================================="
echo "    TEST DE LAMBDAS DE REPORTES - AWS ACADEMY"
echo "=============================================="

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Función para imprimir con colores
print_status() {
    local color=$1
    local message=$2
    echo -e "${color}${message}${NC}"
}

# Verificar que AWS CLI está configurado
if ! aws sts get-caller-identity &> /dev/null; then
    print_status $RED "❌ Error: AWS CLI no está configurado o no tienes permisos"
    exit 1
fi

print_status $GREEN "✅ AWS CLI configurado correctamente"

# Verificar que las lambdas existen
LAMBDAS=("create-reporte" "update-reporte")
for lambda in "${LAMBDAS[@]}"; do
    if aws lambda get-function --function-name $lambda &> /dev/null; then
        print_status $GREEN "✅ Lambda $lambda encontrada"
    else
        print_status $RED "❌ Lambda $lambda no encontrada"
        exit 1
    fi
done

echo ""
print_status $YELLOW "🔄 Iniciando pruebas..."
echo ""

# 1. Probar creación de reporte
print_status $YELLOW "1. Probando creación de reporte..."

# Generar número de reporte único basado en timestamp
TIMESTAMP=$(date +%s)
REPORTE_NUM="RPT-TEST-${TIMESTAMP}"

# Crear evento de prueba para crear reporte
cat > test-create-event.json << EOF
{
  "numero_reporte": "$REPORTE_NUM",
  "empresa": "Test Company",
  "nit": "123456789",
  "direccion": "Test Address",
  "telefono": "123456789",
  "contacto": "Test Contact",
  "email": "test@test.com",
  "ciudad": "Test City",
  "servicio_reportado": "Test Service",
  "tipo_servicio": "Test Type",
  "informe": "Test Report",
  "observaciones": "Test Observations",
  "cedula_tecnico": "12345678",
  "nombre_tecnico": "Test Technician",
  "cedula_encargado": "87654321",
  "nombre_encargado": "Test Manager"
}
EOF

aws lambda invoke \
    --function-name create-reporte \
    --cli-binary-format raw-in-base64-out \
    --payload file://test-create-event.json \
    create-response.json \
    --cli-read-timeout 30 \
    --cli-connect-timeout 30

if [ $? -eq 0 ]; then
    if [ -f create-response.json ]; then
        # Extraer el ID del reporte de la respuesta anidada
        REPORTE_ID=$(cat create-response.json | jq -r '.body' | jq -r '.reporteId' 2>/dev/null)
        if [ "$REPORTE_ID" != "null" ] && [ "$REPORTE_ID" != "" ]; then
            print_status $GREEN "✅ Reporte creado exitosamente"
            echo "   ID del reporte: $REPORTE_ID"
        else
            print_status $RED "❌ Error al obtener ID del reporte creado"
            echo "   Response: $(cat create-response.json)"
            exit 1
        fi
    else
        print_status $RED "❌ Archivo create-response.json no encontrado"
        exit 1
    fi
else
    print_status $RED "❌ Error al invocar lambda create-reporte"
    exit 1
fi

echo ""

# 2. Probar actualización de reporte
print_status $YELLOW "2. Probando actualización de reporte..."

# Crear evento de prueba para actualizar reporte (usando estructura de invocación directa)
cat > test-update-event.json << EOF
{
  "body": "{\"empresa\":\"Test Company Updated\",\"observaciones\":\"Observaciones actualizadas\",\"numero_reporte\":\"${REPORTE_NUM}-UPD\"}",
  "pathParameters": {"id": "$REPORTE_ID"}
}
EOF

aws lambda invoke \
    --function-name update-reporte \
    --cli-binary-format raw-in-base64-out \
    --payload file://test-update-event.json \
    update-response.json \
    --cli-read-timeout 30 \
    --cli-connect-timeout 30

if [ $? -eq 0 ]; then
    if [ -f update-response.json ]; then
        # Verificar que la respuesta indique éxito
        STATUS_CODE=$(cat update-response.json | jq -r '.statusCode' 2>/dev/null)
        if [ "$STATUS_CODE" = "200" ]; then
            print_status $GREEN "✅ Reporte actualizado exitosamente"
            echo "   ID del reporte actualizado: $REPORTE_ID"
        else
            print_status $RED "❌ Error al actualizar el reporte"
            echo "   Response: $(cat update-response.json)"
            exit 1
        fi
    else
        print_status $RED "❌ Archivo update-response.json no encontrado"
        exit 1
    fi
else
    print_status $RED "❌ Error al invocar lambda update-reporte"
    exit 1
fi

echo ""
print_status $GREEN "🎉 Todas las pruebas completadas exitosamente!"
print_status $YELLOW "📋 Resumen:"
echo "   • Lambda create-reporte: ✅ Funcionando"
echo "   • Lambda update-reporte: ✅ Funcionando"
echo "   • Reporte de prueba ID: $REPORTE_ID"

# Limpiar archivos temporales
rm -f test-create-event.json test-update-event.json create-response.json update-response.json

echo ""
print_status $GREEN "✅ Pruebas completadas - Archivos temporales eliminados"
