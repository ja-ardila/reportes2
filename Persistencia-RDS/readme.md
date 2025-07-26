# 🗄️ Base de Datos RDS - Sistema de Reportes

Configuración de base de datos MySQL 8.0 en AWS RDS con esquema completo y datos de prueba.

## 🚀 Despliegue Rápido

```bash
# 1. Configurar variables
cp terraform.tfvars.example terraform.tfvars
# Editar terraform.tfvars con tus credenciales

# 2. Desplegar
terraform init
terraform apply -auto-approve
```

**Tiempo de despliegue:** 5-10 minutos

## � Configuración

### Variables en `terraform.tfvars`:
```hcl
aws_access_key = "tu-access-key"
aws_secret_key = "tu-secret-key"
region = "us-east-1"
db_password = "tu-password-seguro"
```

### Datos de conexión:
- **Base de datos:** jardila_reportes2
- **Usuario:** jardila_reportes
- **Puerto:** 3306

## 📊 Estructura de Datos

### Tablas principales:

**usuarios** - Gestión de acceso al sistema
- `id`, `usuario`, `contrasena`, `nombre`, `rol`, `firma_tecnico`

**reportes** - Registro de servicios técnicos  
- Información del cliente: `empresa`, `nit`, `direccion`, `telefono`
- Datos del servicio: `fecha_inicio`, `fecha_cierre`, `tipo_servicio` 
- Firmas digitales: `firma_tecnico`, `firma_encargado` (LONGTEXT base64)
- Técnico asignado: `cedula_tecnico`, `nombre_tecnico`

**imagenes** - Archivos adjuntos
- `id_reporte`, `ruta_imagen` (LONGTEXT base64)

## 🔌 Conexión

### Obtener endpoint:
```bash
terraform output db_instance_endpoint
```

### Conectar desde línea de comandos:
```bash
ENDPOINT=$(terraform output -raw db_instance_endpoint | cut -d: -f1)
mysql -h $ENDPOINT -u jardila_reportes -p jardila_reportes2
```

### Datos de prueba incluidos:
- **4 usuarios** de ejemplo (admin, técnicos)
- **3 reportes** de muestra con diferentes estados
- **Contraseña por defecto:** secret (hash bcrypt)

## � Consultas Útiles

### Ver todos los reportes:
```sql
SELECT numero_reporte, empresa, fecha, tipo_servicio FROM reportes;
```

### Estadísticas por técnico:
```sql
SELECT nombre_tecnico, COUNT(*) as total_reportes 
FROM reportes 
GROUP BY nombre_tecnico;
```

### Reportes con imágenes:
```sql
SELECT r.numero_reporte, r.empresa, COUNT(i.id) as num_imagenes
FROM reportes r 
LEFT JOIN imagenes i ON r.id = i.id_reporte
GROUP BY r.id;
```

## 🛠️ Mantenimiento

### Backup de datos:
```bash
ENDPOINT=$(terraform output -raw db_instance_endpoint | cut -d: -f1)
mysqldump -h $ENDPOINT -u jardila_reportes -p jardila_reportes2 > backup.sql
```

### Actualizar esquema:
```bash
# Modificar schema.sql y ejecutar:
terraform taint null_resource.db_setup
terraform apply
```

### Ver logs:
```bash
aws logs describe-log-groups --log-group-name-prefix "/aws/rds/instance"
```

## 🧹 Limpieza

```bash
terraform destroy -auto-approve
```

## 🔐 Seguridad

**⚠️ Para desarrollo únicamente**
- Security group abierto (0.0.0.0/0)
- Contraseñas por defecto

**Para producción:**
- Usar AWS Secrets Manager
- Configurar VPC privada  
- Cambiar contraseñas por defecto
- Habilitar backups automáticos

---

**Costo estimado:** ~$13-15 USD/mes (db.t3.micro)
