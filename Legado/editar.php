<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Conexión
include("conexion.php");

// Comprobar si el usuario no ha iniciado sesión
if (!isset($_SESSION['id_usuario'])) {
    echo "<script>alert('Debes iniciar sesión primero'); window.location.href='login.php';</script>";
    exit;
}
$destino = 'listado.php';

if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin') {
    $destino = 'admin_dashboard.php';
}
$id_reporte = $_GET['id']; // Asegúrate de obtener el ID del reporte antes de usarlo

// Validación del ID del reporte
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    // Consulta a la base de datos
    $sql = "SELECT * FROM reportes WHERE id = $id";
    $resultado = mysqli_query($conexion, $sql);
    if (mysqli_num_rows($resultado) == 1) {
        $row = mysqli_fetch_assoc($resultado);

        // Asignar valores a variables
        $nreporte = $row['numero_reporte'];
        $empresag = $row['empresa'];
        $nitg = $row['nit'];
        $direcciong = $row['direccion'];
        $telefonog = $row['telefono'];
        $contactog = $row['contacto'];
        $emailg = $row['email'];
        $ciudadg = $row['ciudad'];
        $fechaig = $row['fecha_inicio'];
        $fechacg = $row['fecha_cierre'];
        $horaig = $row['hora_inicio'];
        $horacg = $row['hora_cierre'];
        $serviciorg = $row['servicio_reportado'];
        $tiposerviciog = $row['tipo_servicio'];
        $informeg = $row['informe'];
        $observacionesg = $row['observaciones'];
        $cedulatg = $row['cedula_tecnico'];
        $nombretg = $row['nombre_tecnico'];
        $firmag = $row['firma_tecnico'];
        $cedulaeg = $row['cedula_encargado'];
        $nombreeg = $row['nombre_encargado'];
    } else {
        echo "Reporte no encontrado.";
        exit;
    }
} else {
    echo "ID de reporte no proporcionado.";
    exit;
}

// Actualizar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $empresa = $_POST['empresa'];
    $nit = $_POST['nit'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $contacto = $_POST['contacto'];
    $email = $_POST['email'];
    $ciudad = $_POST['ciudad'];
    $fechai = $_POST['fechai'];
    $fechac = $_POST['fechac'];
    $horai = $_POST['horai'];
    $horac = $_POST['horac'];
    $servicior = $_POST['servicior'];
    $tiposervicio = $_POST['tiposervicio'];
    $informe = $_POST['informe'];
    $observaciones = $_POST['observaciones'];
    $cedulat = $_POST['cedulat'];
    $nombret = $_POST['nombret'];
    $firma = $_POST['firma'];
    $cedulae = $_POST['cedulae'];
    $nombree = $_POST['nombree'];

    $update_sql = "UPDATE reportes SET 
        empresa='$empresa', nit='$nit', direccion='$direccion', telefono='$telefono',
        contacto='$contacto', email='$email', ciudad='$ciudad', fecha_inicio='$fechai',
        fecha_cierre='$fechac', hora_inicio='$horai', hora_cierre='$horac', 
        servicio_reportado='$servicior', tipo_servicio='$tiposervicio', informe='$informe',
        observaciones='$observaciones', cedula_tecnico='$cedulat', nombre_tecnico='$nombret',
        firma_tecnico='$firma', cedula_encargado='$cedulae', nombre_encargado='$nombree'
        WHERE id=$id";

    if (mysqli_query($conexion, $update_sql)) {
        echo "<script>
                  alert('Reporte actualizado correctamente');
                  window.location.href = '" . ($_SESSION['rol'] === 'admin' ? 'admin_dashboard.php' : 'listado.php') . "';
              </script>";
    } else {
        echo "Error al actualizar: " . mysqli_error($conexion);
    }
}

// Ruta de almacenamiento de las imágenes
$rutaImagenes = 'imagenes/';

// Crear un array para almacenar los nombres de las imágenes
$nombresImagenes = [];

if (isset($_FILES['imagenes'])) {
    // Recorrer todas las imágenes cargadas
    foreach ($_FILES['imagenes']['tmp_name'] as $index => $tmpName) {
        // Verificar si se recibió correctamente el archivo temporal de la imagen
        if (is_uploaded_file($tmpName)) {
            // Obtener el nombre original de la imagen
            $nombreImagen = $_FILES['imagenes']['name'][$index];

            // Crear un nombre único para la imagen
            $nombreUnico = uniqid() . '_' . $nombreImagen;

            // Ruta y nombre de archivo para guardar en formato JPEG
            $rutaDestino = $rutaImagenes . basename($nombreUnico, '.' . pathinfo($nombreUnico, PATHINFO_EXTENSION)) . '.jpeg';

            // Convertir la imagen a formato JPEG
            $imagen = imagecreatefromstring(file_get_contents($tmpName));
            imagejpeg($imagen, $rutaDestino, 100);
            imagedestroy($imagen);

            // Agregar el nombre de la imagen convertida al array
            $nombresImagenes[] = basename($rutaDestino);

            // Guardar en la base de datos
            $stmt = $conexion->prepare("INSERT INTO imagenes (id_reporte, ruta_imagen) VALUES (?, ?)");
            $stmt->bind_param("is", $id_reporte, $rutaDestino);
            if (!$stmt->execute()) {
             echo "Error al guardar imagen en la base de datos: " . $stmt->error;
            }
            $stmt->close();
        } else {
            // Error al cargar el archivo temporal de la imagen
            echo "Error al cargar la imagen número " . ($index + 1) . "<br>";
        }
    }
}
// Mostrar imágenes asociadas a un reporte
$id_reporte = $_GET['id']; // el ID del reporte a consultar

$sql = "SELECT ID, ruta_imagen FROM imagenes WHERE id_reporte = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_reporte);
$stmt->execute();
$result = $stmt->get_result();


$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Editar reporte No. <?php echo $nreporteg; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
            margin: 0;
            padding: 20px;
        }
        
        form {
            max-width: 400px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        label {
            display: block;
            margin-bottom: 10px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="number"],
        input[type="file"],
        input[type="date"],
        input[type="time"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            margin-bottom: 10px;
        }
        
        textarea {
            max-width:100%;
            min-width:100%;
            min-height:100px;
        }
        
        input[type="submit"] {
            background-color: #007bff;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            font-size: 14px;
            color: #555;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("arrow.png");
            background-repeat: no-repeat;
            /*background-position: right center;*/
            background-position: 98% center;
            cursor: pointer;
        }
        input[type="file"] {
            cursor: pointer;
        }
        input[type="date"]::-webkit-inner-spin-button,
        input[type="date"]::-webkit-calendar-picker-indicator,
        input[type="time"]::-webkit-inner-spin-button,
        input[type="time"]::-webkit-calendar-picker-indicator {
            display: none;
        }
        
        input[type="date"]::-webkit-input-placeholder,
        input[type="time"]::-webkit-input-placeholder {
            color: #999;
        }
        .botonregreso {
            border-radius: 5px;
            width:100%;
            max-width:440px;
            margin: 0 auto;
            background-color: #fff;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .boton {
            width:15%; 
            height:auto;
            padding:2%;
            padding-left:20px;
            padding-right:auto;
            text-align:center;
        }
        a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            font-size: 12px;
            text-decoration: none;
            border-radius: 4px;
        }
        a:hover {
            background-color: #0056b3;
        }
        @media screen and (max-width: 1090px) {
            form {
                max-width: 82%;
                margin: 0 auto;
                background-color: #fff;
                padding: 4%;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            .botonregreso {
                border-radius: 5px;
                width:100%;
                max-width:90%;
                margin: 0 auto;
                background-color: #fff;
                margin-bottom: 10px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            .boton {
                width:15%; 
                height:auto;
                padding:2%;
                padding-left:20px;
                padding-right:auto;
                text-align:center;
            }
            body {
                font-size: 40px;
            }
            a {
                font-size: 40px;
            }
            input[type="text"],
            input[type="password"],
            input[type="email"],
            input[type="number"],
            input[type="file"],
            input[type="date"],
            input[type="time"],
            textarea,
            select {
                width: 100%;
                padding: 10px;
                font-size: 40px;
                box-sizing: border-box;
            }
            input[type="submit"] {
                font-size: 40px;
            }
            textarea {
                min-height:500px;
            }
        }
    </style>
</head>
<body>
<div style="max-width: 800px; margin: auto; padding: 20px; background: #f9f9f9; border-radius: 10px;">
    <h1>Editar Reporte No. <?php echo $nreporte; ?></h1>
    <div class="botonregreso">
        <div class="boton">
            <a href="<?= $destino ?>" class="btn btn-primary">Atras</a>
        </div>
    </div>
    <form method="POST" action="" enctype="multipart/form-data">
        <label for="empresa">Empresa:</label>
        <input type="text" name="empresa" id="empresa" value="<?php echo $empresag?>" required><br><br>

        <label for="nit">Nit:</label>
        <input type="text" name="nit" id="nit" value="<?php echo $nitg?>" required><br><br>

        <label for="direccion">Dirección:</label>
        <input type="text" name="direccion" id="direccion" value="<?php echo $direcciong?>" required><br><br>

        <label for="telefono">Teléfono:</label>
        <input type="number" name="telefono" id="telefono" value="<?php echo $telefonog?>" required><br><br>

        <label for="contacto">Persona de contacto:</label>
        <input type="text" name="contacto" id="contacto" value="<?php echo $contactog?>" required><br><br>

        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" value="<?php echo $emailg?>" required><br><br>

        <label for="ciudad">Ciudad:</label>
        <input type="text" name="ciudad" id="ciudad" value="<?php echo $ciudadg?>" required><br><br>

        <label for="fechai">Fecha inicio:</label>
        <input type="date" name="fechai" id="fechai" value="<?php echo $fechaig?>" required><br><br>
        <label for="fechac">Fecha Cierre:</label>
        <input type="date" name="fechac" id="fechac" value="<?php echo $fechacg?>" required><br><br>
        <label for="horai">Hora Inicio:</label>
        <input type="time" name="horai" id="horai" value="<?php echo $horaig?>" required><br><br>
        <label for="horac">Hora Cierre:</label>
        <input type="time" name="horac" id="horac" value="<?php echo $horacg?>" required><br><br> 

        <label for="servicior">Servicio reportado:</label>
        <input type="text" name="servicior" id="servicior" value="<?php echo $serviciorg?>" required><br><br>

        <label for="tiposervicio">Tipo de servicio:</label>
        <input type="text" name="tiposervicio" id="tiposervicio" value="<?php echo $tiposerviciog?>" required><br><br>

        <label for="informe">Informe:</label>
        <textarea name="informe" id="informe" required style="height:200px"><?= htmlspecialchars($informeg) ?></textarea><br><br>

        <label for="observaciones">Observaciones:</label>
        <textarea name="observaciones" id="observaciones" required><?= htmlspecialchars($observacionesg) ?></textarea><br><br>

        <label for="cedulat">Cédula técnico:</label>
        <input type="number" name="cedulat" id="cedulat" value="<?php echo $cedulatg?>" required><br><br>

        <label for="firma">Firma técnico:</label>
        <select id="firma" name="firma" required>
            <option value="">-- Seleccione técnico --</option>
            <?php
              include("conexion.php");
            $query = "SELECT nombre FROM usuarios WHERE rol = 'tecnico'";
            $resultado = $conexion->query($query);
            while ($row = $resultado->fetch_assoc()) {
              $nombre = $row['nombre'];
              $partes = explode(" ", $nombre);
              if (count($partes) >= 2) {
                $iniciales = strtoupper(substr($partes[0], 0, 1) . substr($partes[1], 0, 1));
                $archivoFirma = "firma" . $iniciales . ".png";
                echo "<option value=\"$archivoFirma\">$nombre</option>";
              }
          }
          ?>
        </select><br><br>

        <label for="nombret">Nombre técnico:</label>
        <input type="text" name="nombret" id="nombret" value="<?php echo $nombretg?>" required><br><br>

        <label for="cedulae">Cédula encargado:</label>
        <input type="number" name="cedulae" id="cedulae" value="<?php echo $cedulaeg?>" required><br><br>

        <label for="nombree">Nombre encargado:</label>
        <input type="text" name="nombree" id="nombree" value="<?php echo $nombreeg?>" required><br><br>
        
        <label for="imagenes">Agregar Imágenes:</label>
        <input type="file" name="imagenes[]" id="imagenes" multiple><br><br>

        <input type="submit" value="Editar Reporte">
    </form>
    
    <?php
    echo "<h4 style='margin-top: 30px;'>Imágenes del reporte:</h4>";

    $sql = "SELECT ID, ruta_imagen FROM imagenes WHERE id_reporte = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_reporte);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';

    while ($img = $result->fetch_assoc()) {
        echo '<div style="
            flex: 1 1 calc(33.333% - 20px); 
            max-width: calc(33.333% - 10px); 
            box-sizing: border-box; 
            position: relative; 
            border: 1px solid #ccc; 
            border-radius: 10px; 
            overflow: hidden; 
            height: 180px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        ">';

        echo '<img src="' . $img['ruta_imagen'] . '" style="width: 100%; height: 100%; object-fit: cover;" 
            onclick="openModal(\'' . $img['ruta_imagen'] . '\')">';

        echo '<form method="POST" action="eliminar_imagenes.php" 
            onsubmit="return confirm(\'¿Estás seguro de que deseas eliminar esta imagen?\');"
            style="position: absolute; top: 5px; right: 5px; margin: 0;">';

        echo '<input type="hidden" name="id_imagen" value="' . $img['ID'] . '">';
        echo '<input type="hidden" name="id_reporte" value="' . $id_reporte . '">';
        echo '<button type="submit" title="Eliminar imagen" 
            style="background-color: #e74c3c; color: white; border: none; border-radius: 4px; padding: 5px 8px; font-weight: bold; cursor: pointer;">×</button>';

        echo '</form>';
        echo '</div>';
    }

    echo '</div>';
    ?>

<!-- Modal para ver la imagen en grande -->
<div id="imageModal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); justify-content: center; align-items: center; z-index: 1000;">
    <span onclick="closeModal()" style="position: absolute; top: 10px; right: 20px; font-size: 30px; color: white; cursor: pointer;">×</span>
    <img id="modalImage" src="" style="max-width: 90%; max-height: 80%; margin: auto;">
</div>

<script>
// Función para abrir el modal
function openModal(imageUrl) {
    document.getElementById("modalImage").src = imageUrl;
    document.getElementById("imageModal").style.display = "flex";
}

// Función para cerrar el modal
function closeModal() {
    document.getElementById("imageModal").style.display = "none";
}
</script>

</body>
</html>