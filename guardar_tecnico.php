<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rol = $_POST["rol"];
    $usuario = $_POST["usuario"];
    $nombre = $_POST["nombre"];

    // Subir la firma si existe
    $firma_nombre = null;
    if ($rol === "Técnico" && isset($_FILES["firma"]) && $_FILES["firma"]["error"] === 0) {
        $directorio = "firmas_tecnicos/";
        if (!file_exists($directorio)) {
            mkdir($directorio, 0777, true);
        }

        $firma_nombre = $directorio . uniqid() . "_" . basename($_FILES["firma"]["name"]);
        move_uploaded_file($_FILES["firma"]["tmp_name"], $firma_nombre);
    }

    // Insertar en la base de datos
    $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, nombre, rol, firma_tecnico) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $usuario, $nombre, $rol, $firma_nombre);

    if ($stmt->execute()) {
        include("correo_contraseña.php");
        enviarCorreoAsignarContrasena($usuario, $nombre);
        echo "<script>alert('Técnico registrado. Correo enviado para asignar contraseña.'); window.location.href='listado.php';</script>";
    } else {
        echo "Error al registrar: " . $stmt->error;
    }

    $stmt->close();
    $conexion->close();
}
?>