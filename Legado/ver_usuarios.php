<?php
session_start();

// Verifica si está logueado y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, redirige a listado o a login
    header("Location: listado.php");
    exit();
}
include("conexion.php");

// Consulta todos los usuarios
$sql = "SELECT id, usuario, nombre, rol FROM usuarios";
$resultado = $conexion->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuarios</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 20px;
        }

        h1 {
            text-align: center;
            color: #333;
        }

        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px 15px;
            text-align: center;
        }

        th {
            background-color: #e9ecef;
            color: #333;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        a {
            text-decoration: none;
            color: #007bff;
            margin: 0 5px;
        }

        a:hover {
            text-decoration: underline;
        }

        .acciones a {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Lista de Usuarios</h1>

    <table>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Nombre</th>
            <th>Rol</th>
            <th>Acciones</th>
        </tr>

        <?php while ($row = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['usuario']) ?></td>
                <td><?= htmlspecialchars($row['nombre']) ?></td>
                <td><?= htmlspecialchars($row['rol']) ?></td>
                <td class="acciones">
                    <a href="modificar_usuarios.php?id=<?= $row['id'] ?>">Editar</a> |
                    <a href="eliminar_usuarios.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Estás seguro de eliminar este usuario?');">Eliminar</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
                <div class="text-center mt-3">
                    <a href="admin_dashboard.php" class="text-decoration-none">Volver</a>
                </div>
</body>
</html>