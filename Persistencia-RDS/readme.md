# 🗄️ Base de Datos RDS MySQL - Sistema de Reportes

Este módulo despliega una base de datos MySQL 8.0 en AWS RDS con esquema completo para gestión de reportes técnicos.

## 🎯 Descripción

La base de datos incluye:
- **Usuarios**: Administradores y técnicos del sistema
- **Reportes**: Registro detallado de servicios técnicos
- **Imágenes**: Referencias a archivos adjuntos
- **Datos de ejemplo**: Para pruebas inmediatas

## 🚀 Despliegue Rápido

```bash
# 1. Clonar e ir al directorio
cd rds-order-service

# 2. Inicializar Terraform
terraform init

# 3. Desplegar
terraform apply --auto-approve
```

## 📋 Configuración Detallada

### Variables principales (main.tf)
```hcl
# Base de datos
allocated_storage    = 20
storage_type        = "gp2" 
engine              = "mysql"
engine_version      = "8.0"
instance_class      = "db.t3.micro"

# Credenciales
db_name  = "jardila_reportes2"
username = "jardila_reportes"
password = "Zsw2Xaq1"
```

### Security Group
- **Puerto**: 3306 (MySQL)
- **Acceso**: 0.0.0.0/0 (abierto para desarrollo)
- **Protocolo**: TCP

## 🏗️ Arquitectura de Datos

### Esquema de Tablas

#### `usuarios` - Gestión de acceso
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,     -- Hash bcrypt
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'user') NOT NULL,
    token TEXT,                           -- JWT tokens
    firma_tecnico VARCHAR(255)            -- Ruta firma digital
);
```

#### `reportes` - Registro de servicios
```sql
CREATE TABLE reportes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_reporte VARCHAR(50) UNIQUE NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    fecha DATETIME DEFAULT CURRENT_TIMESTAMP,
    
    -- Datos del cliente
    empresa VARCHAR(100),
    nit VARCHAR(20),
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    contacto VARCHAR(100),
    email VARCHAR(100),
    ciudad VARCHAR(50),
    
    -- Programación del servicio
    fecha_inicio DATE,
    fecha_cierre DATE,
    hora_inicio TIME,
    hora_cierre TIME,
    
    -- Detalles técnicos
    servicio_reportado TEXT,
    tipo_servicio VARCHAR(50),
    informe TEXT,
    observaciones TEXT,
    
    -- Técnico asignado
    cedula_tecnico VARCHAR(20),
    nombre_tecnico VARCHAR(100),
    firma_tecnico VARCHAR(255),
    
    -- Persona encargada
    cedula_encargado VARCHAR(20),
    nombre_encargado VARCHAR(100),
    firma_encargado VARCHAR(255),
    
    -- Referencias
    id_usuario INT,
    token TEXT,
    
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);
```

#### `imagenes` - Archivos adjuntos
```sql
CREATE TABLE imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    ruta_imagen VARCHAR(500) NOT NULL,
    FOREIGN KEY (id_reporte) REFERENCES reportes(id) ON DELETE CASCADE
);
```

## 📊 Datos Iniciales

### Usuarios predefinidos
| Usuario   | Nombre                    | Rol   | Contraseña |
|-----------|---------------------------|-------|------------|
| admin     | Administrador del Sistema | admin | secret     |
| jtecnico  | Juan Técnico             | user  | secret     |
| mtecnico  | María Técnico            | user  | secret     |
| ptecnico  | Pedro Técnico            | user  | secret     |

*Contraseña hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`*

### Reportes de ejemplo
- **RPT-2025-001**: Mantenimiento preventivo (Completado)
- **RPT-2025-002**: Reparación de equipo (Completado)  
- **RPT-2025-003**: Instalación nueva (En progreso)

## 🔧 Operaciones Comunes

### Conectarse a la base de datos
```bash
# Obtener endpoint
ENDPOINT=$(terraform output -raw db_instance_endpoint | cut -d: -f1)

# Conectar
mysql -h $ENDPOINT -u jardila_reportes -p'Zsw2Xaq1' jardila_reportes2
```

### Comandos útiles SQL
```sql
-- Ver todas las tablas
SHOW TABLES;

-- Estadísticas de reportes
SELECT 
    tipo_servicio,
    COUNT(*) as total,
    AVG(DATEDIFF(fecha_cierre, fecha_inicio)) as dias_promedio
FROM reportes 
WHERE fecha_cierre IS NOT NULL
GROUP BY tipo_servicio;

-- Reportes por técnico
SELECT 
    nombre_tecnico,
    COUNT(*) as total_reportes,
    SUM(CASE WHEN fecha_cierre IS NOT NULL THEN 1 ELSE 0 END) as completados
FROM reportes 
GROUP BY nombre_tecnico;

-- Últimos 5 reportes
SELECT numero_reporte, empresa, fecha, tipo_servicio 
FROM reportes 
ORDER BY fecha DESC 
LIMIT 5;
```

### Backup de datos
```bash
# Crear backup
mysqldump -h $ENDPOINT -u jardila_reportes -p'Zsw2Xaq1' jardila_reportes2 > backup.sql

# Restaurar backup
mysql -h $ENDPOINT -u jardila_reportes -p'Zsw2Xaq1' jardila_reportes2 < backup.sql
```

## 📈 Monitoreo

### Métricas importantes
- **Conexiones activas**: Verificar en AWS Console
- **CPU/Memoria**: Monitorear en CloudWatch
- **Storage**: Verificar espacio disponible

### Logs de base de datos
```bash
# Ver logs en CloudWatch
aws logs describe-log-groups --log-group-name-prefix "/aws/rds/instance"
```

## 🛠️ Mantenimiento

### Actualizar esquema
1. Modificar `schema.sql`
2. Ejecutar: `terraform taint null_resource.db_setup`
3. Aplicar: `terraform apply`

### Agregar datos
1. Modificar `default-data.sql`
2. Ejecutar: `terraform taint null_resource.db_data`
3. Aplicar: `terraform apply`

### Escalar recursos
```hcl
# En main.tf
instance_class = "db.t3.small"  # Cambiar tamaño
allocated_storage = 50          # Aumentar storage
```

## ⚠️ Consideraciones de Seguridad

### Para Producción
- [ ] Cambiar contraseñas por defecto
- [ ] Usar AWS Secrets Manager
- [ ] Configurar VPC privada
- [ ] Habilitar SSL/TLS
- [ ] Configurar backups automáticos
- [ ] Restringir security groups

### Security Group actual
```hcl
# ⚠️ SOLO PARA DESARROLLO
ingress {
  from_port   = 3306
  to_port     = 3306
  protocol    = "tcp"
  cidr_blocks = ["0.0.0.0/0"]  # Abierto a internet
}
```

## 📤 Outputs

Después del despliegue:
```bash
terraform output
```

Proporciona:
- `db_instance_endpoint`: Endpoint de conexión
- `security_group_id`: ID del security group

## 🧹 Limpieza

```bash
# Destruir infraestructura
terraform destroy --auto-approve
```

## 🐛 Troubleshooting

### Error de conexión
```bash
# Verificar security group
aws ec2 describe-security-groups --group-ids $(terraform output -raw security_group_id)

# Verificar estado de RDS
aws rds describe-db-instances --db-instance-identifier jardila-reportes2
```

### Error en provisioners
```bash
# Ver logs detallados
terraform apply -auto-approve

# Re-ejecutar setup
terraform taint null_resource.db_setup
terraform apply
```

---

**Tiempo estimado de despliegue**: 5-10 minutos  
**Costo estimado**: ~$13-15 USD/mes (db.t3.micro)
