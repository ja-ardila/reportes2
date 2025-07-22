<?php
session_start();

if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header('Location: listado.php');
    exit();
}

require 'conexion.php';

if (!isset($_GET['id'])) {
    echo "ID no especificado.";
    exit();
}

$id = $_GET['id'];
$stmt = $conexion->prepare("SELECT usuario, nombre, rol FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "Usuario no encontrado.";
    exit();
}

$usuario = $resultado->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nuevo_usuario = $_POST['usuario'];
    $nombre = $_POST['nombre'];
    $rol = $_POST['rol'];

    $update = $conexion->prepare("UPDATE usuarios SET usuario = ?, nombre = ?, rol = ? WHERE id = ?");
    $update->bind_param("sssi", $nuevo_usuario, $nombre, $rol, $id);
    $update->execute();

    header('Location: ver_usuarios.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Modificar Usuario</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 40px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            max-width: 500px;
            margin: auto;
            background-color: #fff;
            padding: 25px 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-bottom: 6px;
            color: #333;
            font-weight: bold;
        }

        input[type="text"],
        input[type="email"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            font-size: 16px;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .volver {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #007bff;
            text-decoration: none;
        }

        .volver:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <h1>Modificar Usuario</h1>

    <form method="POST">
        <label for="usuario">Usuario:</label>
        <input type="text" id="usuario" name="usuario" value="<?= htmlspecialchars($usuario['usuario']) ?>" required>

        <label for="nombre">Nombre:</label>
        <input type="text" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>

        <label for="rol">Rol:</label>
        <select id="rol" name="rol" required>
            <option value="admin" <?= $usuario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            <option value="tecnico" <?= $usuario['rol'] === 'tecnico' ? 'selected' : '' ?>>Técnico</option>
            <option value="usuario" <?= $usuario['rol'] === 'usuario' ? 'selected' : '' ?>>Usuario</option>
        </select>

        <input type="submit" value="Guardar Cambios">
        <a class="volver" href="ver_usuarios.php">← Volver a la lista</a>
    </form>

</body>
</html>