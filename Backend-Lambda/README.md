# 🚀 Backend Lambda - Sistema de Reportes

Sistema de funciones Lambda para crear y actualizar reportes técnicos con soporte para imágenes en base64 y firmas digitales.

## 📦 Funciones Disponibles

- **`create-reporte`** - Crear nuevos reportes con imágenes y firmas
- **`update-reporte`** - Actualizar reportes (protege firmas y fechas)

## ⚡ Despliegue Rápido

```bash
# 1. Configurar credenciales AWS
export AWS_ACCESS_KEY_ID="tu-access-key"
export AWS_SECRET_ACCESS_KEY="tu-secret-key"

# 2. Desplegar
terraform init
terraform apply -auto-approve

# 3. Probar funciones
./test-simple.sh
```

## � Configuración

### Variables necesarias en `terraform.tfvars`:
```hcl
aws_access_key = "tu-access-key"
aws_secret_key = "tu-secret-key" 
region = "us-east-1"
db_password = "tu-password-rds"
```

## � Uso de las Funciones

### Crear Reporte
```json
{
  "numero_reporte": "RPT-2025-001",
  "empresa": "Empresa XYZ",
  "usuario": "admin",
  "imagenes": [
    "data:image/jpeg;base64,/9j/4AAQSkZJRgABAQAAAQ...",
    "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=="
  ],
  "firma_tecnico": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg=="
}
```

### Actualizar Reporte
```json
{
  "id": 1,
  "empresa": "Nueva Empresa",
  "observaciones": "Trabajo completado exitosamente"
}
```

**⚠️ Campos protegidos:** `id`, `firma_tecnico`, `firma_encargado`, `fecha`
## 🧪 Pruebas

### Probar creación de reporte:
```bash
aws lambda invoke --function-name create-reporte \
  --payload file://test-create-event.json response.json
cat response.json
```

### Probar actualización de reporte:
```bash
aws lambda invoke --function-name update-reporte \
  --payload file://test-update-event.json response.json
cat response.json
```

### Ver logs:
```bash
aws logs tail /aws/lambda/create-reporte --follow
aws logs tail /aws/lambda/update-reporte --follow
```

## � Actualizar Código

Cuando modifiques el código:

```bash
# Actualizar create-service
zip -r lambda-create-reporte-with-deps.zip create-service.js node_modules/ package.json
terraform apply

# Actualizar update-service  
zip -r lambda-update-reporte-with-deps.zip update-service.js node_modules/ package.json
terraform apply
```

## ✨ Características

- ✅ Soporte para múltiples imágenes en base64
- ✅ Validación de formatos de imagen (JPEG, PNG, GIF)
- ✅ Firmas digitales protegidas contra modificación
- ✅ Conexión directa a RDS MySQL
- ✅ Manejo de errores detallado
- ✅ Logs estructurados para debugging

## 📚 Archivos de Referencia

- `DEPLOYMENT-GUIDE.md` - Guía detallada de despliegue
- `UPDATE-SERVICE-README.md` - Documentación del servicio de actualización
- `test-simple.sh` - Script de pruebas simplificado
