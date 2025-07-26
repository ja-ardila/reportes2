-- Script para llenar datos por defecto en la base de datos jardila_reportes2

USE jardila_reportes2;

-- Insertar usuarios por defecto
INSERT INTO usuarios (usuario, contrasena, nombre, rol, token, firma_tecnico) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin', NULL, NULL),
('jareportes', '$2y$10$OLAEaMrg4EUgOwa2ketXoOaw7bqfygpsmKrQAxq0r1NYNfAMVreKO', 'J.A. Reportes Admin', 'admin', NULL, NULL),
('jtecnico', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Juan Técnico', 'user', NULL, 'firma_juan.png'),
('mtecnico', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'María Técnico', 'user', NULL, 'firma_maria.png'),
('ptecnico', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Pedro Técnico', 'user', NULL, 'firma_pedro.png');

-- Insertar reportes de ejemplo
INSERT INTO reportes (
    numero_reporte, usuario, fecha, empresa, nit, direccion, telefono, contacto, 
    email, ciudad, fecha_inicio, fecha_cierre, hora_inicio, hora_cierre, 
    servicio_reportado, tipo_servicio, informe, observaciones, cedula_tecnico, 
    nombre_tecnico, firma_tecnico, cedula_encargado, nombre_encargado, id_usuario, 
    firma_encargado, token
) VALUES
(
    'RPT-2025-001', 'jtecnico', NOW(), 'Empresa ABC S.A.S', '900123456-1', 
    'Calle 123 #45-67', '2345678', 'Carlos Gerente', 
    'carlos@empresaabc.com', 'Bogotá', '2025-01-15', '2025-01-15', 
    '08:00:00', '17:00:00', 'Mantenimiento preventivo', 'Mantenimiento', 
    'Se realizó mantenimiento preventivo completo del sistema. Todo funcionando correctamente.', 
    'Sin observaciones adicionales', '12345678', 'Juan Técnico', 'firma_juan.png', 
    '87654321', 'Carlos Gerente', 2, 'firma_carlos.png', NULL
),
(
    'RPT-2025-002', 'mtecnico', NOW(), 'Tecnología XYZ Ltda', '800987654-2', 
    'Avenida 68 #12-34', '9876543', 'Ana Supervisora', 
    'ana@tecnologiaxyz.com', 'Medellín', '2025-01-16', '2025-01-16', 
    '09:00:00', '16:30:00', 'Reparación de equipo', 'Reparación', 
    'Se reparó el equipo principal. Se reemplazaron componentes defectuosos.', 
    'Recomendable realizar mantenimiento cada 6 meses', '23456789', 'María Técnico', 
    'firma_maria.png', '98765432', 'Ana Supervisora', 3, 'firma_ana.png', NULL
),
(
    'RPT-2025-003', 'ptecnico', NOW(), 'Industrias DEF S.A.', '700456789-3', 
    'Carrera 15 #89-12', '3456789', 'Roberto Jefe', 
    'roberto@industriasdef.com', 'Cali', '2025-01-17', NULL, 
    '07:30:00', NULL, 'Instalación nueva', 'Instalación', 
    'Instalación en progreso. Pendiente finalización mañana.', 
    'Requiere materiales adicionales', '34567890', 'Pedro Técnico', 'firma_pedro.png', 
    '56789012', 'Roberto Jefe', 4, NULL, NULL
);

-- Insertar imágenes de ejemplo
INSERT INTO imagenes (id_reporte, ruta_imagen) VALUES
(1, '/uploads/reportes/rpt_001_antes.jpg'),
(1, '/uploads/reportes/rpt_001_durante.jpg'),
(1, '/uploads/reportes/rpt_001_despues.jpg'),
(2, '/uploads/reportes/rpt_002_problema.jpg'),
(2, '/uploads/reportes/rpt_002_solucion.jpg'),
(3, '/uploads/reportes/rpt_003_instalacion1.jpg'),
(3, '/uploads/reportes/rpt_003_instalacion2.jpg');

-- Mensaje de confirmación
SELECT 'Datos por defecto insertados correctamente' AS mensaje;
