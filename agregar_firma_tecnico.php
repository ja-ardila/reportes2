<?php
session_start();

// Verifica si está logueado y es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    // Si no es admin, redirige a listado o a login
    header("Location: listado.php");
    exit();
}
include("conexion.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir Firma Técnico</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .firma-container {
            max-width: 500px;
            margin: 80px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .firma-container h3 {
            margin-bottom: 20px;
            font-weight: bold;
            color: #343a40;
        }

        label {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .form-control {
            margin-bottom: 15px;
        }

        .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body>

<div class="firma-container">
    <h3>Subir Firma de Técnico</h3>

    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="tecnico" class="form-label">Seleccionar técnico:</label>
            <select name="id_tecnico" id="id_tecnico" class="form-select" required>
                <option value="">-- Seleccione un técnico --</option>
                <?php
                $query = "SELECT id, nombre FROM usuarios WHERE rol = 'tecnico'";
                $resultado = $conexion->query($query);
                while ($row = $resultado->fetch_assoc()) {
                    echo "<option value=\"{$row['id']}\">{$row['nombre']}</option>";
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="firma" class="form-label">Subir imagen de firma (.png, .jpg, .jpeg, .webp):</label>
            <input type="file" name="firma" accept=".png,.jpg,.jpeg,.webp" class="form-control" required>
        </div>
        <br>

        <button type="submit" class="btn btn-primary">Guardar firma</button>
    </form>

    <div class="text-center mt-3">
        <a href="admin_dashboard.php" class="text-decoration-none">Volver</a>
    </div>
    <br>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["firma"])) {
    $idTecnico = $_POST["id_tecnico"];

    // Buscar el nombre del técnico
    $queryNombre = $conexion->prepare("SELECT nombre FROM usuarios WHERE id = ?");
    $queryNombre->bind_param("i", $idTecnico);
    $queryNombre->execute();
    $resultado = $queryNombre->get_result();

    if ($resultado->num_rows > 0) {
        $row = $resultado->fetch_assoc();
        $nombreTecnico = $row["nombre"];

        $partes = explode(" ", $nombreTecnico);
        if (count($partes) >= 2) {
            $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
            $nombreArchivo = "firma" . $iniciales . ".png";
            $rutaDestino = "firmas/" . $nombreArchivo;

            // Validar tipo de imagen y convertir a PNG
            $tmpName = $_FILES["firma"]["tmp_name"];
            $info = getimagesize($tmpName);

            if ($info !== false) {
                $mime = $info['mime'];
                switch ($mime) {
                    case 'image/jpeg':
                        $image = imagecreatefromjpeg($tmpName);
                        break;
                    case 'image/png':
                        $image = imagecreatefrompng($tmpName);
                        break;
                    case 'image/webp':
                        $image = imagecreatefromwebp($tmpName);
                        break;
                    default:
                        echo "<div class='alert alert-warning mt-3'>⚠️ Formato no compatible. Usa JPG, PNG o WEBP.</div>";
                        exit;
                }

                // Convertir a PNG y guardar
                if (imagepng($image, $rutaDestino)) {
                    imagedestroy($image);

                    // Guardar en la base de datos
                    $queryUpdate = $conexion->prepare("UPDATE usuarios SET firma_tecnico = ? WHERE id = ?");
                    $queryUpdate->bind_param("si", $rutaDestino, $idTecnico);
                    if ($queryUpdate->execute()) {
                        echo "<div class='alert alert-success mt-3'>✅ Firma guardada y asociada correctamente al técnico <strong>$nombreTecnico</strong>.</div>";
                    } else {
                        echo "<div class='alert alert-danger mt-3'>❌ Error al actualizar la base de datos.</div>";
                    }
                } else {
                    echo "<div class='alert alert-danger mt-3'>❌ Error al guardar la imagen convertida.</div>";
                }

            } else {
                echo "<div class='alert alert-danger mt-3'>❌ El archivo no es una imagen válida.</div>";
            }
        } else {
            echo "<div class='alert alert-warning mt-3'>⚠️ El nombre del técnico debe tener al menos dos palabras.</div>";
        }
    } else {
        echo "<div class='alert alert-danger mt-3'>❌ Técnico no encontrado.</div>";
    }
}
?>
</div>

</body>
</html>
