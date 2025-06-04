<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Comprobar si el usuario no ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Recuperar el nombre de usuario de la sesión
$username = $_SESSION['usuario'];
$nombreusuario = $_SESSION['nombre'];

include("conexion.php");   

// Identifica filtros
$empresaFiltro = $_GET['empresa'] ?? '';
$usuarioFiltro = $_GET['usuario'] ?? '';

// Base de la consulta
$sql = "SELECT * FROM reportes WHERE 1=1";

// Agregar condiciones si hay filtros
if (!empty($empresaFiltro)) {
    $empresaFiltro = $conexion->real_escape_string($empresaFiltro);
    $sql .= " AND empresa = '$empresaFiltro'";
}

if (!empty($usuarioFiltro)) {
    $usuarioFiltro = $conexion->real_escape_string($usuarioFiltro);
    $sql .= " AND usuario = '$usuarioFiltro'";
}

$sql .= " ORDER BY id DESC";

// Ejecutar consulta
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reportes H323</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .container {
            overflow: auto;
            border-radius: 5px;
            width: 75%;
            margin-left: auto;
            margin-right: auto;
            background-color: #fff;
            margin-bottom: 10px;
            box-shadow: 0 4px 4px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
        }
        .sidebyside {
            padding: 1%;
            margin: 0;
            box-sizing: border-box; /* Incluir padding y border en el ancho total */
        }
        #nr {
            width: 12.5%; /* Ancho ajustado */
            text-align:center;
        }
        #fecha {
            width: 20%; /* Ancho ajustado */
            text-align:center;
        }
        #empresa {
            width: 35%; /* Ancho ajustado */
            text-align:center;
        }
        #editar, #imprimir, #firmar {
            width: 13%; /* Ancho ajustado */
            text-align:center;
        }
        body {
            font-family: Arial, sans-serif;
            background-color: #f1f1f1;
            margin: 0;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .report-link, a {
            display: inline-block;
            padding: 10px 40px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            text-align: center;
        }
        .report-link:hover, a:hover {
            background-color: #0056b3;
        }
        p {
            margin: 0;
            padding: 10px 0;
        }
        ul {
            padding: 0;
            list-style: none;
        }
        /* Estilos para los botones flotantes */
        .float-button {
            position: fixed;
            width: 50px;
            height: 50px;
            bottom: 20px;
            right: 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 50%;
            text-align: center;
            line-height: 50px;
            font-size: 18px;
            cursor: pointer;
        }
        .float-button:hover {
            background-color: #0056b3;
        }
        .top-button {
            bottom: 80px; /* Espacio entre los botones */
        }
        /* Ajustes para pantallas pequeñas */
        @media screen and (max-width: 1090px) {
            body {
                font-size: 18px;
            }
            h1 {
                font-size: 24px;
            }
            a, .report-link {
                font-size: 18px;
                padding: 8px 16px;
            }
            .container {
                width: 90%;
                margin-bottom: 10px;
            }
            .sidebyside {
                width: 100%;
                display: block;
                text-align: left;
                font-size: 16px;
            }
        }
        @media screen and (max-width: 768px) {
            body {
                font-size: 16px;
            }
            h1 {
                font-size: 22px;
            }
            a, .report-link {
                font-size: 16px;
                padding: 6px 12px;
            }
            .container {
                width: 95%;
            }
            .sidebyside {
                font-size: 14px;
                padding: 5px;
            }
        }
    </style>
    <script>
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        function scrollToBottom() {
            window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
    </script>
</head>
<body>
    <h1>Bienvenido a reportes, 
    <?php echo $nombreusuario;?></h1><br><br>
    
    <!-- Botones flotantes -->
    <button class="float-button top-button" onclick="scrollToTop()">&#8679;</button>
    <button class="float-button" onclick="scrollToBottom()">&#8681;</button>

    <form method="GET" style="width: 75%; margin: 0 auto 20px auto; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
    
    <!-- Botón Filtrar con estilo de los otros botones -->
    <div class='sidebyside' style='text-align:center;'>
        <strong>
            <button type="submit" style="all: unset; cursor: pointer; padding: 6px 12px; background-color: #007bff; color: white; border-radius: 5px; font-size: 16px;">
                Filtrar
            </button>
        </strong>
    </div>

    <select name="empresa" style="padding: 6px; font-size: 16px;">
        <option value="">Todas las empresas</option>
        <?php
        $empresas = $conexion->query("SELECT DISTINCT empresa FROM reportes");
        while ($row = $empresas->fetch_assoc()) {
            $selected = ($_GET['empresa'] ?? '') == $row['empresa'] ? "selected" : "";
            echo "<option value='{$row['empresa']}' $selected>{$row['empresa']}</option>";
        }
        ?>
    </select>

    <select name="usuario" style="padding: 6px; font-size: 16px;">
        <option value="">Todos los usuarios</option>
        <?php
        $usuarios = $conexion->query("SELECT DISTINCT usuario FROM reportes");
        while ($row = $usuarios->fetch_assoc()) {
            $selected = ($_GET['usuario'] ?? '') == $row['usuario'] ? "selected" : "";
            echo "<option value='{$row['usuario']}' $selected>{$row['usuario']}</option>";
        }
        ?>
    </select>
</form>


    <ul>
        <div class='container'>
            <div class='sidebyside' style='text-align:center; flex: 1 1 48%'><strong><a href="reportes.php">Crear reporte nuevo</a></strong></div>
            <div class='sidebyside' style='text-align:center; flex: 1 1 48%'><strong><a href="logout.php">Cerrar Sesión</a></strong></div>
        </div>
        <div class='container'>
            <div class='sidebyside' id='nr'><strong>&nbsp;&nbsp;&nbsp;#</strong></div>
            <div class='sidebyside' id='fecha'><strong>Fecha</strong></div>
            <div class='sidebyside' id='empresa'><strong>Empresa</strong></div>
            <div class='sidebyside' id='editar'><strong>Editar</strong></div>
            <div class='sidebyside' id='imprimir'><strong>Imprimir</strong></div>
            <div class='sidebyside' id='firmar'><strong>Firmar</strong></div>
        </div>

        <?php

        if ($resultado->num_rows > 0) {
            while ($fila = $resultado->fetch_assoc()) {
                $id = $fila['id'];
                $fecha = $fila['fecha'];
                $empresa = $fila['empresa'];
                $firma = $fila['firma_tecnico'];
                $nit = $fila['nit'];
                $direccion = $fila['direccion'];
                $telefono = $fila['telefono'];
                $contacto = $fila['contacto'];
                $email = $fila['email'];
                $ciudad = $fila['ciudad'];
                $fechai = $fila['fecha_inicio'];
                $fechac = $fila['fecha_cierre'];
                $horai = $fila['hora_inicio'];
                $horac = $fila['hora_cierre'];
                $servicior = $fila['servicio_reportado'];
                $tiposervicio = $fila['tipo_servicio'];
                $informe = $fila['informe'];
                $observaciones = $fila['observaciones'];
                $cedulat = $fila['cedula_tecnico'];
                $nombret = $fila['nombre_tecnico'];
                $cedulae = $fila['cedula_encargado'];
                $nombree = $fila['nombre_encargado'];
                $firmae = $fila['firma_encargado'];
                $nreporte = $fila['numero_reporte'];
                // Consultar las imágenes asociadas a este reporte
                $imagenes = array();
                $sql_imagenes = "SELECT ruta_imagen FROM imagenes WHERE id_reporte = $id";
                $res_imagenes = $conexion->query($sql_imagenes);

                if ($res_imagenes && $res_imagenes->num_rows > 0) {
                    while ($img = $res_imagenes->fetch_assoc()) {
                        $imagenes[] = $img['ruta_imagen'];
                    }
                }
        
                echo "<div class='container'>";
                echo "<div class='sidebyside' id='nr'><p><strong>$nreporte</strong></p></div>";
                echo "<div class='sidebyside' id='fecha'><p>$fecha</p></div>";
                echo "<div class='sidebyside' id='empresa'><p>$empresa</p></div>";
        
                echo "<div class='sidebyside' id='editar'>
                <a href='editar.php?id={$id}'>
                    <i class='fas fa-edit'></i>
                </a>
            </div>";
                                                                                                                         
        
                echo "<div class='sidebyside' id='imprimir'>
                <a href='pdf.php?id=$id' class='icon-button'>
                <i class='fas fa-print'></i></a>
            </div>";    

                echo "<div class='sidebyside' id='firmar'>
                        <a href='firmar_encargado_token.php?token=" . urlencode($fila['token']) . "' class='icon-button' title='Firmar reporte'>
                        <i class='fas fa-signature'></i></a>
                      </div>";

                echo "</div>";
            }
        } else {
            echo "<p style='text-align:center;'>No hay reportes registrados.</p>";
        }
        
        $conexion->close();
        ?>
</body>
</html>