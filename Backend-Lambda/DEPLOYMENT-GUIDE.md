# Guía de Despliegue - Lambdas de Reportes con Terraform (AWS Academy)

## 📋 Resumen
Esta configuración despliega dos lambdas en AWS Academy (sin API Gateway debido a limitaciones):
- **create-reporte**: Para crear nuevos reportes
- **update-reporte**: Para actualizar reportes existentes

Las lambdas se invocan directamente usando AWS CLI o SDK.

## 🏗️ Arquitectura Desplegada

```
AWS Lambda Functions (Invocación Directa)
├── create-reporte       → Crear nuevos reportes
└── update-reporte       → Actualizar reportes existentes
```

## 📁 Archivos Incluidos

### Terraform
- `main.tf` - Configuración principal de las lambdas
- `lambda-functions.tf` - Definición de lambdas adicionales
- `variables.tf` - Variables de configuración
- `terraform.tfvars` - Valores de las variables

### Lambdas (Archivos ZIP)
- `lambda-create-reporte-with-deps.zip` - Lambda de creación
- `lambda-update-reporte-with-deps.zip` - Lambda de actualización

### Código Fuente
- `create-service.js` - Código de la lambda de creación
- `update-service.js` - Código de la lambda de actualización

### Eventos de Prueba
- `test-simple.sh` - Script automatizado de pruebas

## 🚀 Pasos de Despliegue

### 1. Verificar Archivos ZIP
```bash
# Los archivos ZIP deben existir en el directorio
ls -la lambda-*-with-deps.zip
```

### 2. Inicializar Terraform
```bash
terraform init
```

### 3. Verificar Configuración
```bash
terraform validate
terraform plan
```

### 4. Desplegar
```bash
terraform apply
```

### 5. Verificar Despliegue
```bash
# Ver outputs
terraform output

# Verificar lambdas en AWS
aws lambda list-functions --query 'Functions[?contains(FunctionName, `reporte`)]'
```

## 🔧 Configuración de Variables

Las variables se configuran en `terraform.tfvars`:

```hcl
db_host     = "tu-host-rds.amazonaws.com"
db_user     = "tu-usuario-db"
db_password = "tu-password-db"
db_name     = "tu-nombre-db"
jwt_secret  = "tu-jwt-secret-super-seguro"
```

## 📡 Invocación de Lambdas

### Script de Testing Automatizado
```bash
# Ejecutar el script de pruebas completo
./test-simple.sh
```

### Invocación Manual

#### 1. Crear Reporte
```bash
aws lambda invoke \
  --function-name create-reporte \
  --cli-binary-format raw-in-base64-out \
  --payload '{"numero_reporte":"RPT-001","empresa":"Test Company","nit":"123456789"}' \
  create-response.json

# Ver la respuesta
cat create-response.json
```

#### 2. Actualizar Reporte
```bash
aws lambda invoke \
  --function-name update-reporte \
  --cli-binary-format raw-in-base64-out \
  --payload '{"body":"{\"empresa\":\"Company Updated\"}","pathParameters":{"id":"1"}}' \
  update-response.json

# Ver la respuesta
cat update-response.json
```

## 🔍 Verificación de Funcionalidad

### Script de Test Automatizado
```bash
# El script test-simple.sh maneja todo el flujo de pruebas automáticamente
./test-simple.sh
```

### Test Manual Paso a Paso
```bash
#!/bin/bash

echo "=== Test Manual de Lambdas de Reportes ==="

# 1. Crear reporte
echo "1. Creando reporte..."
TIMESTAMP=$(date +%s)
aws lambda invoke \
  --function-name create-reporte \
  --cli-binary-format raw-in-base64-out \
  --payload "{\"numero_reporte\":\"RPT-${TIMESTAMP}\",\"empresa\":\"Test Company\",\"nit\":\"123456789\"}" \
  create-response.json

REPORTE_ID=$(cat create-response.json | jq -r '.body' | jq -r '.reporteId')
echo "Reporte creado con ID: $REPORTE_ID"

# 2. Actualizar reporte
echo "2. Actualizando reporte..."
aws lambda invoke \
  --function-name update-reporte \
  --cli-binary-format raw-in-base64-out \
  --payload "{\"body\":\"{\\\"empresa\\\":\\\"Test Company Updated\\\"}\",\"pathParameters\":{\"id\":\"$REPORTE_ID\"}}" \
  update-response.json

echo "3. Resultados:"
echo "- Creación: $(cat create-response.json)"
echo "- Actualización: $(cat update-response.json)"

echo "=== Test completado ==="
```

## 🛠️ Actualización de Código

### Para actualizar la lambda de creación:
```bash
zip -r lambda-create-reporte-with-deps.zip create-service.js node_modules/ package.json
terraform apply
```

### Para actualizar la lambda de actualización:
```bash
zip -r lambda-update-reporte-with-deps.zip update-service.js node_modules/ package.json
terraform apply
```

## 📊 Monitoreo

### CloudWatch Logs
- `/aws/lambda/create-reporte`
- `/aws/lambda/update-reporte`

### Ver logs en tiempo real:
```bash
# Logs de creación
aws logs tail /aws/lambda/create-reporte --follow

# Logs de actualización
aws logs tail /aws/lambda/update-reporte --follow
```

## 🔐 Seguridad

### Variables de Entorno Sensibles
- `DB_PASSWORD`: Contraseña de base de datos
- `JWT_SECRET`: Clave secreta para tokens JWT (opcional para invocación directa)

### Permisos IAM Requeridos
- `lambda-run-role`: Rol para ejecutar las lambdas
- Permisos de acceso a RDS
- Permisos de escritura en CloudWatch Logs

## 🧹 Limpieza/Destrucción

Para eliminar todos los recursos:
```bash
terraform destroy
```

## 📝 Outputs Importantes

Después del despliegue, obtendrás:
- `create_lambda_function_name`: Nombre de la lambda de creación
- `update_lambda_function_name`: Nombre de la lambda de actualización
- `lambda_invocation_commands`: Comandos para invocar las lambdas

## ⚠️ Consideraciones para AWS Academy

1. **Sin API Gateway**: Las lambdas se invocan directamente
2. **Rol IAM**: Usa el rol predefinido `lambda-run-role`
3. **Limitaciones**: No se pueden usar algunos servicios como API Gateway
4. **Costos**: Monitorea el uso para evitar límites de AWS Academy
5. **Timeouts**: Las lambdas tienen timeout de 30 segundos

## 🔄 Troubleshooting

### Error: Role not found
```bash
# Verificar que el rol existe
aws iam get-role --role-name lambda-run-role
```

### Error: Archive not found
```bash
# Verificar que los ZIP existen
ls -la lambda-*-with-deps.zip
```

### Error de conectividad a RDS
- Verificar security groups
- Verificar que RDS está en la misma VPC/región
- Verificar credenciales de base de datos

## 📱 Integración con Frontend

Para usar estas lambdas en un frontend:

```javascript
// Usar AWS SDK para JavaScript
import { LambdaClient, InvokeCommand } from "@aws-sdk/client-lambda";

const lambda = new LambdaClient({ region: "us-east-1" });

// Crear reporte
const createReporte = async (reporteData) => {
  const command = new InvokeCommand({
    FunctionName: "create-reporte",
    Payload: JSON.stringify(reporteData)
  });
  
  const result = await lambda.send(command);
  return JSON.parse(new TextDecoder().decode(result.Payload));
};
```
