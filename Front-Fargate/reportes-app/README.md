# Sistema de Reportes H323 - Frontend Angular

## Descripción

Modernización del sistema de reportes PHP legado convertido a Angular 18. Este frontend permite crear, editar, visualizar y gestionar reportes de servicio técnico conectándose a AWS Lambda functions.

## Características Principales

### ✅ Componentes Implementados

1. **CrearReporteComponent** (`/crear-reporte`)
   - Formulario completo replicando `reportes.php`
   - Captura de firma digital con SignaturePad
   - Subida múltiple de imágenes
   - Validación de campos
   - Contadores de caracteres para textareas

2. **EditarReporteComponent** (`/editar-reporte/:id`)
   - Formulario de edición replicando `editar.php`
   - Gestión de imágenes existentes y nuevas
   - Actualización de firma digital
   - Pre-carga de datos del reporte

3. **ListadoReportesComponent** (`/listado`)
   - Lista paginada de reportes
   - Filtros por texto y fechas
   - Acciones: Ver, Editar, PDF, Eliminar
   - Indicadores visuales para reportes vencidos

4. **ReportesLambdaService**
   - Integración completa con AWS Lambda
   - Métodos CRUD para reportes
   - Gestión de imágenes
   - Manejo de errores

### 🎯 Mapeo de Campos PHP → Angular

| Campo PHP | Campo Angular | Descripción |
|-----------|---------------|-------------|
| `fechai` | `fechai` | Fecha de inicio |
| `fechac` | `fechac` | Fecha de cierre |
| `horai` | `horai` | Hora de inicio |
| `horac` | `horac` | Hora de cierre |
| `servicior` | `servicior` | Servicio reportado |
| `cedulat` | `cedulat` | Cédula del técnico |
| `nombret` | `nombret` | Nombre del técnico |
| `cedulae` | `cedulae` | Cédula del encargado |
| `nombree` | `nombree` | Nombre del encargado |

## Tecnologías Utilizadas

- **Angular 18** - Framework principal
- **TypeScript 5.4** - Lenguaje de programación
- **RxJS** - Manejo de observables
- **SignaturePad** - Captura de firmas digitales
- **AWS Lambda** - Backend serverless
- **Standalone Components** - Arquitectura moderna de Angular

## Estructura del Proyecto

```
src/
├── app/
│   ├── services/
│   │   └── reportes-lambda.service.ts    # Servicio principal
│   ├── crear-reporte/
│   │   └── crear-reporte.component.ts    # Crear nuevos reportes
│   ├── editar-reporte/
│   │   └── editar-reporte.component.ts   # Editar reportes existentes
│   ├── listado-reportes/
│   │   └── listado-reportes.component.ts # Lista y gestión
│   ├── app.component.ts                  # Componente raíz
│   └── app.routes.ts                     # Configuración de rutas
├── index.html                            # HTML principal
└── main.ts                              # Bootstrap de la aplicación
```

## Instalación y Configuración

### Pre-requisitos
- Node.js 18+
- npm 8+
- Angular CLI 18+

### Pasos de Instalación

1. **Instalar dependencias**
   ```bash
   cd Front-Fargate/reportes-app
   npm install
   ```

2. **Configurar endpoints de Lambda**
   
   Editar `src/app/services/reportes-lambda.service.ts`:
   ```typescript
   private readonly API_BASE_URL = 'https://your-api-gateway-url.execute-api.region.amazonaws.com/prod';
   ```

3. **Instalar dependencias adicionales**
   ```bash
   npm install signature_pad
   npm install @angular/forms
   npm install @angular/router
   ```

### Comandos de Desarrollo

```bash
# Servidor de desarrollo
npm start
# o
ng serve

# Compilar para producción
npm run build
# o
ng build --prod

# Ejecutar tests
npm test
```

## Configuración de AWS Lambda

El servicio está configurado para conectarse a los siguientes endpoints:

### Endpoints Requeridos

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/reportes` | Listar todos los reportes |
| GET | `/reportes/{id}` | Obtener reporte específico |
| POST | `/reportes` | Crear nuevo reporte |
| PUT | `/reportes/{id}` | Actualizar reporte |
| DELETE | `/reportes/{id}` | Eliminar reporte |
| GET | `/reportes/{id}/imagenes` | Obtener imágenes del reporte |
| POST | `/reportes/{id}/imagenes` | Subir imagen |
| DELETE | `/reportes/{reporteId}/imagenes/{imagenId}` | Eliminar imagen |

### Formato de Respuesta Lambda

```typescript
interface ApiResponse<T> {
  success: boolean;
  data?: T;
  message?: string;
  error?: string;
}
```

## Funcionalidades Principales

### 1. Crear Reporte
- Formulario con validación completa
- Captura de firma digital del encargado
- Subida múltiple de imágenes con preview
- Contadores de caracteres en tiempo real
- Dropdown de firmas de técnicos predefinidas

### 2. Editar Reporte
- Pre-carga de datos existentes
- Gestión de imágenes (mantener, eliminar, agregar)
- Actualización de firma digital
- Preservación de datos no modificados

### 3. Listado de Reportes
- Vista tipo tarjetas con información resumida
- Filtros por texto (empresa, NIT, número)
- Filtros por rango de fechas
- Paginación automática
- Indicadores visuales para reportes vencidos

### 4. Gestión de Imágenes
- Vista previa antes de subir
- Eliminación individual
- Soporte para múltiples formatos
- Nombres únicos generados automáticamente

## Características Técnicas

### Componentes Standalone
Todos los componentes utilizan la nueva arquitectura standalone de Angular 18:

```typescript
@Component({
  selector: 'app-crear-reporte',
  standalone: true,
  imports: [CommonModule, FormsModule],
  // ...
})
```

### Manejo de Estados
- Loading states para operaciones asíncronas
- Error handling con mensajes usuario-friendly
- Validación de formularios en tiempo real

### Responsive Design
- Diseño adaptable a móviles y tablets
- Grid system flexible
- Navegación optimizada para dispositivos móviles

## Integración con Sistema Legado

### Compatibilidad de Datos
El sistema mantiene **100% compatibilidad** con la base de datos PHP existente:

- Nombres de campos idénticos
- Tipos de datos respetados
- Estructura de tablas sin cambios
- Imágenes almacenadas en el mismo formato

### Migración Gradual
- Ambos sistemas pueden coexistir
- Datos creados en PHP son editables en Angular
- Datos de Angular son compatibles con PHP

## Próximas Funcionalidades

### En Desarrollo
- [ ] Generación de PDF
- [ ] Exportación a Excel
- [ ] Notificaciones por email
- [ ] Dashboard con estadísticas
- [ ] Búsqueda avanzada
- [ ] Historial de cambios

### Mejoras Planificadas
- [ ] PWA (Progressive Web App)
- [ ] Modo offline
- [ ] Sincronización automática
- [ ] Firma electrónica avanzada
- [ ] Geolocalización automática

## Solución de Problemas

### Errores Comunes

1. **Error de CORS**
   ```
   Configurar headers CORS en AWS API Gateway
   ```

2. **Error de importación FormsModule**
   ```bash
   npm install @angular/forms
   ```

3. **SignaturePad no funciona**
   ```bash
   npm install signature_pad
   ```

### Logs y Debugging
- Consola del navegador para errores frontend
- Network tab para verificar llamadas a Lambda
- CloudWatch logs para errores de backend

## Contribución

### Estructura de Commits
```
feat: nueva funcionalidad
fix: corrección de bug
docs: documentación
style: formato/estilo
refactor: refactorización
test: tests
```

### Testing
```bash
# Unit tests
npm test

# E2E tests
npm run e2e

# Coverage
npm run test:coverage
```

## Licencia

© 2024 H323 - Sistema interno de reportes de servicio

## Contacto

Para soporte técnico o consultas sobre la implementación, contactar al equipo de desarrollo.

---

**Nota**: Este sistema reemplaza progresivamente el sistema PHP legado manteniendo total compatibilidad de datos.
