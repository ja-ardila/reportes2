<?php
$host = "localhost:3306";   
$usuario = "jardila_reportes";
$contrasena = "Zsw2Xaq1";    
$base_de_datos = "jardila_reportes2"; 

// Crear conexión
$conexion = new mysqli("localhost:3306", "jardila_reportes", "Zsw2Xaq1", "jardila_reportes2");

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}
?>
