# Lambda de Actualización de Reportes

Esta lambda permite modificar reportes existentes con restricciones específicas de seguridad.

## Características

### ✅ Campos Editables
- `numero_reporte` - Número del reporte (único)
- `empresa` - Nombre de la empresa
- `nit` - NIT de la empresa
- `direccion` - Dirección
- `telefono` - Teléfono de contacto
- `contacto` - Persona de contacto
- `email` - Correo electrónico
- `ciudad` - Ciudad
- `fecha_inicio` - Fecha de inicio del servicio (YYYY-MM-DD)
- `fecha_cierre` - Fecha de cierre del servicio (YYYY-MM-DD)
- `hora_inicio` - Hora de inicio (HH:MM:SS)
- `hora_cierre` - Hora de cierre (HH:MM:SS)
- `servicio_reportado` - Descripción del servicio
- `tipo_servicio` - Tipo de servicio
- `informe` - Informe técnico
- `observaciones` - Observaciones adicionales
- `cedula_tecnico` - Cédula del técnico
- `nombre_tecnico` - Nombre del técnico
- `cedula_encargado` - Cédula del encargado
- `nombre_encargado` - Nombre del encargado
- `token` - Token personalizado

### ❌ Campos NO Editables (Protegidos)
- `id` - ID del reporte
- `firma_tecnico` - Firma del técnico
- `firma_encargado` - Firma del encargado
- `fecha` - Fecha de creación del reporte

## Uso

### Endpoint
```
PUT /reportes/{id}
```

### Headers Requeridos
```
Authorization: Bearer {jwt-token}
Content-Type: application/json
```

### Ejemplo de Request
```json
{
    "numero_reporte": "RPT-2025-002",
    "empresa": "Nueva Empresa S.A.",
    "nit": "900123456-7",
    "direccion": "Calle 100 #50-25",
    "telefono": "601-5551234",
    "contacto": "María García",
    "email": "contacto@nuevaempresa.com",
    "ciudad": "Bogotá",
    "fecha_inicio": "2025-01-15",
    "fecha_cierre": "2025-01-16",
    "hora_inicio": "08:00:00",
    "hora_cierre": "17:00:00",
    "servicio_reportado": "Mantenimiento preventivo",
    "tipo_servicio": "Preventivo",
    "informe": "Se realizó mantenimiento completo del equipo",
    "observaciones": "Cliente satisfecho con el servicio",
    "cedula_tecnico": "98765432",
    "nombre_tecnico": "Carlos Rodríguez",
    "cedula_encargado": "12345678",
    "nombre_encargado": "Ana López"
}
```

### Ejemplo de Response Exitosa
```json
{
    "message": "Reporte actualizado exitosamente",
    "reporte": {
        "id": 123,
        "numero_reporte": "RPT-2025-002",
        "empresa": "Nueva Empresa S.A.",
        "fecha": "2025-01-15 10:30:00",
        "creado_por": "Juan Pérez"
    }
}
```

## Validaciones

### Seguridad
- ✅ Autenticación JWT requerida
- ✅ Verificación de existencia del usuario
- ✅ Verificación de existencia del reporte
- ✅ Control de permisos (solo el creador puede modificar)
- ✅ Protección contra modificación de campos sensibles

### Formato de Datos
- ✅ Fechas en formato YYYY-MM-DD
- ✅ Horas en formato HH:MM:SS
- ✅ Límites de caracteres en campos de texto
- ✅ Verificación de números de reporte únicos

### Manejo de Imágenes
- ✅ Reemplazo de imágenes existentes
- ✅ Validación de formato de imagen
- ✅ Organización por carpetas de reporte

## Códigos de Error

| Código | Status | Descripción |
|--------|--------|-------------|
| `METHOD_NOT_ALLOWED` | 405 | Método HTTP no permitido |
| `NO_TOKEN` | 401 | Token de autorización faltante |
| `INVALID_TOKEN` | 401 | Token JWT inválido o expirado |
| `MISSING_REPORTE_ID` | 400 | ID del reporte no proporcionado |
| `INVALID_JSON` | 400 | Formato JSON inválido |
| `FORBIDDEN_FIELDS` | 400 | Intento de modificar campos protegidos |
| `VALIDATION_ERROR` | 400 | Error en validación de datos |
| `USER_NOT_FOUND` | 401 | Usuario no encontrado |
| `REPORTE_NOT_FOUND` | 404 | Reporte no encontrado |
| `INSUFFICIENT_PERMISSIONS` | 403 | Sin permisos para modificar |
| `DUPLICATE_REPORTE_NUMBER` | 409 | Número de reporte duplicado |
| `NO_FIELDS_TO_UPDATE` | 400 | No se proporcionaron campos para actualizar |
| `INTERNAL_ERROR` | 500 | Error interno del servidor |

## Configuración de Variables de Entorno

```bash
DB_HOST=tu-host-rds
DB_USER=tu-usuario-db
DB_PASSWORD=tu-password-db
DB_NAME=tu-nombre-db
JWT_SECRET=tu-jwt-secret-key
NODE_ENV=production
```

## Despliegue

1. Instalar dependencias:
```bash
npm install mysql2 jsonwebtoken
```

2. Empaquetar para AWS Lambda:
```bash
zip -r update-service.zip update-service.js node_modules/
```

3. Configurar en AWS Lambda con el handler: `update-service.handler`

## Consideraciones de Seguridad

- Las firmas digitales están protegidas para mantener la integridad legal
- La fecha de creación es inmutable para auditoría
- Solo el creador del reporte puede modificarlo
- Todas las modificaciones requieren autenticación válida
- Los tokens JWT deben tener expiración apropiada
