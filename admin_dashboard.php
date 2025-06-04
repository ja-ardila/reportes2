<?php
session_start();

// Verifica si está logueado y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, redirige a listado o a login
    header("Location: listado.php");
    exit();
}
include("conexion.php");

if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $contrasena = $_POST["contrasena"];
    $nombre = $_POST["nombre"];

    if (!empty($usuario) && !empty($contrasena) && !empty($nombre)) {
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $sql = "INSERT INTO usuarios (usuario, nombre, contrasena) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sss", $usuario, $nombre, $hash);

        if ($stmt->execute()) {
            echo "<script>alert('Usuario registrado correctamente'); window.location.href='login.php';</script>";
        } else {
            echo "<script>alert('Error al registrar: " . $stmt->error . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Todos los campos son obligatorios.');</script>";
    }

    $conexion->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
    .container {
    padding-right: 0 !important;
    padding-left: 0 !important;
    }
    body, html {
        margin: 0;
        padding: 0;
        height: 100%;
    }

    .wrapper {
        display: flex;
        height: 100vh;
        overflow: hidden;
    }

    .sidebar {
        width: 250px;
        background-color: #212529;
        color: white;
        display: flex;
        flex-direction: column;
        position: fixed;
        top: 0;
        bottom: 0;
        left: 0;
        z-index: 100;
        padding-top: 20px;
    }

    .sidebar h4 {
        text-align: center;
        margin-bottom: 20px;
        font-weight: bold;
    }

    .sidebar a {
        color: white;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        text-decoration: none;
        transition: background 0.3s, color 0.3s;
        background-color: transparent;
    }

    .sidebar a:hover,
    .sidebar a.active {
        background-color:rgb(63, 64, 66);
        color: white;
    }

    .sidebar a i {
        margin-right: 10px;
    }

    .main-content {
        margin-left: 130px;
        padding: 30px;
        width: calc(100% - 130px);
        height: 100vh;
        overflow-y: auto;
        background-color: #f8f9fa;
    }

    .icon {
        font-size: 18px;
    }
    
    .logo-admin {
    width: 200px;
    margin: 0 auto 20px;
    display: block;
    border-radius: 8px;
    }
    .admin-email {
        text-align: center;
        font-size: 14px;
        color: #ccc;
        margin-top: 5px;
        margin-bottom: 20px;
        word-break: break-word;
    }
    .admin-nombre {
        text-align: center;
        font-size: 14px;
        color: #ccc;
        margin-top: 0;
        margin-bottom: -20px;
        word-break: break-word;
    }
    .sidebar-links {
    margin-top: auto;
    display: flex;
    flex-direction: column;
    }
</style>

    <!-- Icons -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>
    <div class="wrapper">
        <!-- Menú lateral -->
        <div class="sidebar">
            <img src="H323_LOGO.png" alt="Logo Empresa" class="logo-admin">
            <h4>Bienvenido <br> Administrador</h4>
            <p class="admin-nombre">
                <?php echo isset($_SESSION['nombre']) ? $_SESSION['nombre'] : ''; ?>
            </p>

            <p class="admin-email">
                <?php echo isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'No identificado'; ?><br>
            </p>

            <div class="sidebar-links">
            <a href="insertar_usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) == 'insertar_usuarios.php' ? 'active' : '' ?>">
                <i class="fas fa-user-plus icon"></i> Crear usuario
            </a>
            <a href="ver_usuarios.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ver_usuarios.php' ? 'active' : '' ?>">
                <i class="fas fa-users icon"></i> Ver usuarios
            </a>
            <a href="agregar_firma_tecnico.php" class="<?= basename($_SERVER['PHP_SELF']) == 'agregar_firma.php' ? 'active' : '' ?>">
                <i class="fas fa-pen-nib icon"></i> Agregar firma técnico
            </a>
            <a href="logout.php" class="<?= basename($_SERVER['PHP_SELF']) == 'cerrar_sesion.php' ? 'active' : '' ?>">
                <i class="fas fa-sign-out-alt icon"></i> Cerrar sesión
            </a>
            </div>
        </div>

        <!-- Contenido -->
        <div class="main-content">
            <?php include("listado.php"); ?>
        </div>
    </div>
</body>
</html>