<?php
session_start();

// Verifica si está logueado y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: listado.php");
    exit();
}
include("conexion.php");
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rol = $_POST['rol'];
    $usuario = $_POST["usuario"];
    $nombre = $_POST["nombre"];
    $firmaNombre = null;

    // Procesar firma si es técnico
    if ($rol === 'tecnico' && isset($_FILES['firma']) && $_FILES['firma']['error'] === UPLOAD_ERR_OK) {
        $tmpPath = $_FILES['firma']['tmp_name'];
        $imageInfo = getimagesize($tmpPath);

        if ($imageInfo !== false) {
            $mime = $imageInfo['mime'];

            switch ($mime) {
                case 'image/jpeg':
                    $img = imagecreatefromjpeg($tmpPath);
                    break;
                case 'image/png':
                    $img = imagecreatefrompng($tmpPath);
                    break;
                case 'image/webp':
                    $img = imagecreatefromwebp($tmpPath);
                    break;
                default:
                    die("Formato de imagen no compatible.");
            }

            // Guardar como PNG
            $firmaNombre = 'firma_' . uniqid() . '.png';
            $rutaDestino = 'firmas/' . $firmaNombre;
            imagepng($img, $rutaDestino);
            imagedestroy($img);
        }
    }

    $token = bin2hex(random_bytes(16));

    $sql = "INSERT INTO usuarios (usuario, nombre, rol, firma_tecnico, contrasena, token) VALUES (?, ?, ?, ?, NULL, ?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssss", $usuario, $nombre, $rol, $firmaNombre, $token);

    if ($stmt->execute()) {
        include("correo_contrasena.php");
        enviarCorreoAsignarContrasena($usuario, $nombre, $token);
        echo "<script>alert('Usuario registrado y correo enviado para asignar contraseña'); window.location.href='login.php';</script>";
    } else {
        echo "Error al registrar: " . $stmt->error;
    }

    $stmt->close();
    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        select {
        width: 100%;
        padding: 10px 15px;
        margin-bottom: 20px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 16px;
        background-color: #007bfff;
        box-sizing: border-box;
        transition: border-color 0.3s ease;
        }

        select:focus {
            border-color: #007bff;
            outline: none;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <h4 class="card-title text-center mb-4">Registrar nuevo usuario</h4>
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="usuario" class="form-label">Usuario (correo electrónico)</label>
                                <input type="email" class="form-control" id="usuario" name="usuario" required>
                            </div>
                            <label for="rol">Rol</label>
                            <select id="rol" name="rol" required>
                                <option value="">Selecciona un rol</option>
                                <option value="admin">Administrador</option>
                                <option value="tecnico">Técnico</option>
                                <option value="cliente">Usuario General</option>
                            </select>
                            <div id="firmaDiv" style="display:none; margin-top: 15px;">
                                <label for="firma">Subir firma (solo si es técnico):</label>
                                <input type="file" id="firma" name="firma" accept="image/*">
                            </div><br><br>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-person-plus"></i> Registrar
                                </button>
                            </div>
                            
                        </form>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="admin_dashboard.php" class="text-decoration-none">Volver</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('rol').addEventListener('change', function() {
        var rol = this.value;
        var firmaDiv = document.getElementById('firmaDiv');

        if (rol === 'tecnico') {
            firmaDiv.style.display = 'block';
        } else {
            firmaDiv.style.display = 'none';
        }
    });
    </script>
</body>
</html>