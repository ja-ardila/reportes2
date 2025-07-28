# Instrucciones para Configurar AWS CLI

## Estado Actual
✅ **Docker imagen funcionando correctamente**
✅ **Terraform instalado y listo**
✅ **Scripts de despliegue creados**

## Próximo Paso: Configurar AWS CLI

### Opción 1: Configuración Básica con Credenciales de Acceso
```powershell
aws configure
```

Te pedirá:
- AWS Access Key ID: [Tu clave de acceso]
- AWS Secret Access Key: [Tu clave secreta]
- Default region name: us-east-1 (recomendado)
- Default output format: json

### Opción 2: Usar Variables de Entorno
```powershell
$env:AWS_ACCESS_KEY_ID = "tu-access-key"
$env:AWS_SECRET_ACCESS_KEY = "tu-secret-key"
$env:AWS_DEFAULT_REGION = "us-east-1"
```

### Opción 3: Usar Perfil de AWS
```powershell
aws configure --profile reportes-app
aws configure set region us-east-1 --profile reportes-app
```

## Verificar Configuración
Una vez configurado, verifica que funciona:
```powershell
aws sts get-caller-identity
```

## Costos Estimados (Región us-east-1)
- **ECR Repository**: $0.10 por GB/mes (primeros 10GB son gratuitos)
- **ECS Fargate**: 
  - vCPU: $0.04048 por vCPU/hora
  - Memoria: $0.004445 por GB/hora
  - Para 0.25 vCPU + 0.5GB RAM = ~$0.014/hora = ~$10/mes
- **Application Load Balancer**: ~$16.20/mes + $0.008 por LCU-hora
- **VPC**: Gratuito para configuración básica

## Regiones Recomendadas (menor costo)
1. **us-east-1** (Virginia) - Más barata
2. **us-west-2** (Oregon) - Segunda opción
3. **eu-west-1** (Irlanda) - Para Europa

## Después de Configurar AWS CLI
Ejecuta el script de despliegue:
```powershell
.\deploy.ps1
```

El script automáticamente:
1. Validará las credenciales de AWS
2. Creará el repositorio ECR
3. Construirá y subirá la imagen Docker
4. Desplegará la infraestructura con Terraform
5. Configurará el servicio ECS Fargate
6. Configurará el Load Balancer
7. Te proporcionará la URL final

## Limpieza de Recursos (para evitar costos)
Cuando ya no necesites la aplicación:
```powershell
cd terraform
terraform destroy
```

## Notas Importantes
- El despliegue inicial toma aproximadamente 10-15 minutos
- La URL final estará disponible después del despliegue completo
- Se recomienda usar HTTPS en producción (requiere certificado SSL)
- El Load Balancer puede tardar 2-3 minutos en estar completamente funcional
