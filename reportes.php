<?php
session_start();
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
$id_usuario = $_SESSION['id_usuario'];

// === LIMPIEZA AUTOMÁTICA ANUAL DE IMÁGENES ===
if (date('md') === '0101') {
    // Obtener todas las imágenes registradas en la base de datos
    $result = mysqli_query($conexion, "SELECT ruta_imagen FROM imagenes");
    $imagenes_en_bd = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $imagenes_en_bd[] = $row['ruta_imagen'];
    }

    // Escanear la carpeta de imágenes
    $archivos_en_carpeta = glob("imagenes/*.jpeg");

    // Eliminar las que no estén en la base de datos
    foreach ($archivos_en_carpeta as $archivo) {
        if (!in_array($archivo, $imagenes_en_bd)) {
            file_put_contents('limpieza_log.txt', date('Y-m-d H:i:s') . " - Eliminado: $archivo\n", FILE_APPEND);
            unlink($archivo);
        }
    }
}
// === FIN LIMPIEZA ===

// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del formulario
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
    $informe = trim($_POST['informe']);
    $observaciones = trim($_POST['observaciones']);
    $cedulat = $_POST['cedulat'];
    $nombret = $_POST['nombret'];
    $firma = $_POST['firma'];
    $cedulae = $_POST['cedulae'];
    $nombree = $_POST['nombree'];
    $firmae = $_POST['signature'];
    $id_usuario = $_SESSION['id_usuario'];
    
    $result = mysqli_query($conexion, "SELECT MAX(numero_reporte) AS ultimo FROM reportes");
    $row = mysqli_fetch_assoc($result);
    
    $ultimo = $row['ultimo']; // Ejemplo: "REP-2025-0012"
    
    if ($ultimo) {
    // Separar la parte numérica del final
    $partes = explode("-", $ultimo);
    $numero = (int)$partes[2]; // convierte la parte numérica a entero
    $nuevoNumero = $numero + 1;
    $numero_reporte = "REP-" . date('Y') . "-" . str_pad($nuevoNumero, 4, "0", STR_PAD_LEFT);
    } else {
    // Si no hay ningún reporte aún
    $numero_reporte = "REP-" . date('Y') . "-1340";
    }

    $user = $_SESSION['nombre'] ?? 'Desconocido'; 
    date_default_timezone_set('America/Bogota'); 
    $fecha = date("Y-m-d H:i:s"); // 2025-04-14 15:32:45

    // Guardar la firma como imagen en el servidor, si se proporcionó
    $file = '';
    if (!empty($firmae)) {
        $firmae = str_replace('data:image/png;base64,', '', $firmae);
        $firmae = str_replace(' ', '+', $firmae);
        $data = base64_decode($firmae);
        $file = uniqid() . '.png';
        file_put_contents('signatures/'.$file, $data);
    } else {
        $file = 0;
    }

    // 1. Buscar el último número de reporte usado
    $sql = "SELECT numero_reporte FROM reportes ORDER BY id DESC LIMIT 1";
    $resultado = mysqli_query($conexion, $sql);
    $ultimo_numero = 1339; // Valor inicial por defecto si no hay reportes

    if ($fila = mysqli_fetch_assoc($resultado)) {
        // Extraer el número secuencial desde el formato "REP-xxxx"
        if (preg_match('/REP-\d+-(\d+)/', $fila['numero_reporte'], $matches)) {
            $ultimo_numero = (int)$matches[1];
        }
    }

    // 2. Incrementar en 1
    $proximo_numero = $ultimo_numero + 1;

    // 3. Formatear número con ceros a la izquierda (ej: 1340 → 1340, 1341, etc.)
    $numero_formateado = str_pad($proximo_numero, 4, "0", STR_PAD_LEFT);

    // 4. Armar número completo con el año actual (opcional si quieres mantenerlo)
    $anio_actual = date("Y");
    $numero_reporte = "REP-$anio_actual-$numero_formateado";

    // Generar Token unico para mayor Seguridad como 'a3c9e1f21ba25f6d8a2e10f8a0b71f33'

    $token = bin2hex(random_bytes(16));

    // Insertar el reporte en la tabla "reportes"
        $stmt = $conexion->prepare("INSERT INTO reportes (
            id_usuario, numero_reporte, usuario, fecha, empresa, nit, direccion, telefono, contacto, email,
            ciudad, fecha_inicio, fecha_cierre, hora_inicio, hora_cierre, servicio_reportado,
            tipo_servicio, informe, observaciones, cedula_tecnico, nombre_tecnico, firma_tecnico,
            cedula_encargado, nombre_encargado, firma_encargado, token
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("isssssssssssssssssssssssss",
            $id_usuario, $numero_reporte, $user, $fecha, $empresa, $nit, $direccion, $telefono, $contacto, $email,
            $ciudad, $fechai, $fechac, $horai, $horac, $servicior, $tiposervicio, $informe,
            $observaciones, $cedulat, $nombret, $firma, $cedulae, $nombree, $file, $token
        );

        if ($stmt->execute()) {
            $id_reporte = $conexion->insert_id;
            echo "Reporte guardado en la base de datos.";
        } else {
            echo "Error al guardar el reporte: " . $stmt->error;
        }
        $stmt->close();
  
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
                imagejpeg($imagen, $rutaDestino, 60);
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

    // Enviar el correo electrónico
    $to = "admin@h323.company";
    $subject = "Nuevo Reporte Creado: Reporte No $numero_reporte - $empresa";
    $link_reporte = "https://h323.com.co/reportes/pdf.php?token=$token";
    $message = "
    <html>
    <head>
        <title>Nuevo Reporte Creado</title>
        <style>
            body {
                font-family: Arial, sans-serif;
            }
            .container {
                padding: 20px;
                background-color: #f4f4f4;
                border: 1px solid #ddd;
                border-radius: 5px;
                margin: 20px auto;
                max-width: 600px;
            }
            .header {
                font-size: 20px;
                font-weight: bold;
                margin-bottom: 20px;
            }
            .content {
                font-size: 16px;
            }
            .footer {
                margin-top: 20px;
                font-size: 14px;
                color: #666;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>Nuevo Reporte Creado</div>
            <div class='content'>
                Reporte No $id_reporte creado para la empresa $empresa.
                <br />
                Puedes verlo directamente haciendo clic aquí:  <a href='$link_reporte' target='_blank'>Ver Reporte</a>.
            </div>
            <div class='footer'>
                Este es un correo generado automáticamente, por favor no responder.
            </div>
        </div>
    </body>
    </html>
    ";
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= 'From: noreply@h323.com.co' . "\r\n";

    mail($to, $subject, $message, $headers);

    // Redireccionar a una página de éxito o mostrar un mensaje
    echo "<script>
            alert('Reporte creado exitosamente!');
            window.location.href = '$destino';
          </script>";

    // Limpiar los datos del formulario
    $empresa = $nit = $direccion = $telefono = $contacto = $email = $ciudad = $fechac = $fechai = $horai = $horac = $informe = $observaciones = $cedulat = $nombret = $cedulae = $nombree = $servicior = $tiposervicio = $firmae = '';
}
?>


<!DOCTYPE html>
<html>
<head>
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <title>Página de Reportes</title>
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
            background-position: right center;/
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
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var canvas = document.getElementById('signature-pad');
            var signaturePad = new SignaturePad(canvas);

            function resizeCanvas() {
                var ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext('2d').scale(ratio, ratio);
                signaturePad.clear();
            }

            window.addEventListener('resize', resizeCanvas);
            resizeCanvas();

            document.getElementById('clear').addEventListener('click', function () {
                signaturePad.clear();
            });

            document.querySelector('form').addEventListener('submit', function (e) {
                if (signaturePad.isEmpty()) {
                    var confirmWithoutSignature = confirm('No ha firmado el documento. ¿Desea continuar sin firma?');
                    if (!confirmWithoutSignature) {
                        e.preventDefault();
                        return;
                    }
                } else {
                    var dataUrl = signaturePad.toDataURL();
                    document.getElementById('signature').value = dataUrl;
                }
            });
        });
    </script>
</head>
<body>
    <h1>Generar Reporte</h1>
    <div class="botonregreso">
        <div class="boton">
            <a href="<?= $destino ?>" class="btn btn-primary">Atras</a>
        </div>
    </div>
    <div class="container">
        <form method="POST" action="" enctype="multipart/form-data">
            <label for="empresa">Empresa:</label>
            <input type="text" name="empresa" id="empresa" required><br><br>
    
            <label for="nit">Nit:</label>
            <input type="text" name="nit" id="nit" required><br><br>
    
            <label for="direccion">Dirección:</label>
            <input type="text" name="direccion" id="direccion" required><br><br>
    
            <label for="telefono">Teléfono:</label>
            <input type="number" name="telefono" id="telefono" required><br><br>
    
            <label for="contacto">Persona de contacto:</label>
            <input type="text" name="contacto" id="contacto" required><br><br>
    
            <label for="email">E-mail:</label>
            <input type="email" name="email" id="email" required><br><br>
    
            <label for="ciudad">Ciudad:</label>
            <input type="text" name="ciudad" id="ciudad" required><br><br>

            <label for="fechai">Fecha inicio:</label>
            <input type="text" name="fechai" id="fechai" required><br><br>
            <label for="fechac">Fecha Cierre:</label>
            <input type="text" name="fechac" id="fechac" required><br><br>
            <label for="horai">Hora Inicio:</label>
            <input type="time" name="horai" id="horai" required><br><br>
            <label for="horac">Hora Cierre:</label>
            <input type="time" name="horac" id="horac" required><br><br>
    
            <label for="servicior">Servicio reportado:</label>
            <input type="text" name="servicior" id="servicior" required><br><br>
    
            <label for="tiposervicio">Tipo de servicio:</label>
            <input type="text" name="tiposervicio" id="tiposervicio" required><br><br>
    
            <label for="informe">Informe:</label>
            <textarea name="informe" id="informe" required style="height:200px" maxlength="4500" oninput="contarCaracteres('informe', 'contadorInforme', 4500)"></textarea><br><br>
            <small id='contadorInforme'>0 / 4500 caracteres</small><br><br>                                                                                                                                                                                                                                                                                                                                    
    
            <label for="observaciones">Observaciones:</label>
            <textarea name="observaciones" id="observaciones" required maxlength="3000" oninput="contarCaracteres('observaciones', 'contadorObs', 3000)"></textarea><br><br>
            <small id='contadorObs'>0 / 3000 caracteres</small><br><br>
    
            <label for="cedulat">Cédula técnico:</label>
            <input type="number" name="cedulat" id="cedulat" required><br><br>
    
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
            <input type="text" name="nombret" id="nombret" required><br><br>
    
            <label for="cedulae">Cédula encargado:</label>
            <input type="number" name="cedulae" id="cedulae" required><br><br>
    
            <label for="nombree">Nombre encargado:</label>
            <input type="text" name="nombree" id="nombree" required><br><br>
            
            <label for="firmae">Firma encargado:</label>
            <canvas id="signature-pad" style="border:1px solid #000; width: 100%; height: 200px;"></canvas>
            <button type="button" id="clear">Borrar Firma</button>
            <input type="hidden" name="signature" id="signature"><br><br>
    
            <label for="imagenes">Imágenes:</label>
            <input type="file" name="imagenes[]" id="imagenes" multiple><br><br>
    
            <input type="submit" value="Generar Reporte">
        </form>
        <script>
        function contarCaracteres(idCampo, idContador, maximo) {
            const campo = document.getElementById(idCampo);
            const contador = document.getElementById(idContador);
            contador.textContent = campo.value.length + " / " + maximo + " caracteres";
        }
        </script>
    </div>
  <!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
  	flatpickr("#fechai", {
        dateFormat: "Y-m-d", // formato compatible con MySQL
        defaultDate: "today"
    });
    flatpickr("#fechac", {
        dateFormat: "Y-m-d", // Formato compatible con MySQL
        defaultDate: "today"
    });
</script>
</body>
</html>