-- Script de inicialización de la base de datos jardila_reportes2

-- Crear la base de datos si no existe
CREATE DATABASE IF NOT EXISTS jardila_reportes2;
USE jardila_reportes2;

-- Tabla usuarios
CREATE TABLE IF NOT EXISTS usuarios (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    usuario VARCHAR(50) UNIQUE NOT NULL,
    contrasena VARCHAR(255) NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    rol ENUM('admin', 'tecnico') DEFAULT 'tecnico',
    token VARCHAR(64),
    firma_tecnico LONGTEXT
);

-- Tabla reportes
CREATE TABLE IF NOT EXISTS reportes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    numero_reporte VARCHAR(20) UNIQUE NOT NULL,
    usuario VARCHAR(50),
    fecha DATETIME,
    empresa VARCHAR(100),
    nit VARCHAR(20),
    direccion VARCHAR(100),
    telefono VARCHAR(20),
    contacto VARCHAR(100),
    email VARCHAR(100),
    ciudad VARCHAR(50),
    fecha_inicio DATE,
    fecha_cierre DATE,
    hora_inicio TIME,
    hora_cierre TIME,
    servicio_reportado VARCHAR(255),
    tipo_servicio VARCHAR(255),
    informe VARCHAR(4500),
    observaciones VARCHAR(3000),
    cedula_tecnico VARCHAR(20),
    nombre_tecnico VARCHAR(100),
    firma_tecnico LONGTEXT,
    cedula_encargado VARCHAR(20),
    nombre_encargado VARCHAR(100),
    id_usuario INT(11),
    firma_encargado LONGTEXT,
    token VARCHAR(64),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id)
);

-- Tabla imagenes
CREATE TABLE IF NOT EXISTS imagenes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT(11) NOT NULL,
    ruta_imagen LONGTEXT,
    FOREIGN KEY (id_reporte) REFERENCES reportes(id) ON DELETE CASCADE
);
