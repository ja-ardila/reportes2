<?php
include("conexion.php");
session_start();

function usuario_autenticado() {
    return isset($_SESSION['usuario']);
}

if (isset($_GET['token'])) {
    $valor = $_GET['token'];
    $stmt = $conexion->prepare("SELECT * FROM reportes WHERE token = ?");
} elseif (isset($_GET['nreporte']) && usuario_autenticado()) {
    $valor = $_GET['nreporte'];
    $stmt = $conexion->prepare("SELECT * FROM reportes WHERE numero_reporte = ?");
} else {
    echo "Acceso no autorizado.";
    exit;
}

$stmt->bind_param("s", $valor);
$stmt->execute();
$resultado = $stmt->get_result();

if ($resultado->num_rows === 0) {
    echo "Reporte no encontrado.";
    exit;
}

$reporte = $resultado->fetch_assoc();
$nreporte = $reporte['numero_reporte'];
$token = $reporte['token'];

if (!empty($reporte['firma_encargado'])) {
    // Mostrar modal en lugar de redirigir directamente
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Reporte ya firmado</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f0f2f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .modal {
                background: white;
                padding: 30px;
                border-radius: 12px;
                max-width: 400px;
                text-align: center;
                box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            }
            h2 {
                color: #1e3a8a;
                margin-bottom: 20px;
            }
            p {
                color: #334155;
                margin-bottom: 30px;
            }
            a.boton {
                background-color:  #007bff;
                color: white;
                padding: 12px 24px;
                border-radius: 8px;
                text-decoration: none;
                font-weight: bold;
                display: inline-block;
                margin: 8px;
            }
            a.boton:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="modal">
            <h2>Reporte ya firmado</h2>
            <p>Este reporte ya fue firmado por el encargado. No es posible modificar la firma nuevamente.</p>
            <a class="boton" href="pdf.php?token=<?php echo urlencode($token); ?>" target="_blank">Ver PDF</a>
            <a class="boton" href="index.php">Volver</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firmae = $_POST['signature'];
    $firmae = str_replace('data:image/png;base64,', '', $firmae);
    $firmae = str_replace(' ', '+', $firmae);
    $data = base64_decode($firmae);
    $file = uniqid() . '.png';
    file_put_contents('signatures/' . $file, $data);

    $query = "UPDATE reportes SET firma_encargado = ? WHERE numero_reporte = ?";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param("ss", $file, $nreporte);
    $stmt->execute();

    echo "<script>alert('Firma guardada correctamente.'); window.location.href='gracias.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Firmar Reporte</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f0f2f5;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 30px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        h2, h3 {
            text-align: center;
        }

        iframe {
            width: 100%;
            height: 500px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        canvas {
            border: 2px dashed #888;
            border-radius: 5px;
            background-color: #fff;
            width: 100%;
            height: 200px;
            display: block;
        }

        .buttons {
            margin-top: 15px;
            text-align: center;
        }

        .buttons button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
        }

        .buttons button:hover {
            background-color: #0056b3;
        }

        p {
            font-size: 14px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Revisar Reporte N° <?php echo htmlspecialchars($nreporte); ?></h2>
    <iframe src="pdf.php?token=<?php echo urlencode($token); ?>"></iframe>

    <div class="firma-box">
        <h3>Firma del Encargado</h3>
        <p>Si el encargado está presente, puede firmar ahora. Si no, envíele el enlace para que firme remotamente.</p>
        <form method="POST" onsubmit="return guardarFirma();">
            <canvas id="firma-canvas"></canvas>
            <input type="hidden" name="signature" id="signature">
            <div class="buttons">
                <button type="submit">Firmar ahora</button>
                <button type="button" onclick="limpiarFirma()">Limpiar</button>
                <button type="button" onclick="enviarParaFirmar()">Enviar para firmar</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    const canvas = document.getElementById('firma-canvas');
    const signaturePad = new SignaturePad(canvas);

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext("2d").scale(ratio, ratio);
        signaturePad.clear();
    }

    window.addEventListener("resize", resizeCanvas);
    resizeCanvas();

    function limpiarFirma() {
        signaturePad.clear();
    }

    function guardarFirma() {
        if (signaturePad.isEmpty()) {
            alert("Por favor, firme antes de enviar.");
            return false;
        }
        const firmaData = signaturePad.toDataURL('image/png');
        document.getElementById('signature').value = firmaData;
        return true;
    }

    function enviarParaFirmar() {
        const link = window.location.origin + window.location.pathname + '?token=<?php echo urlencode($token); ?>';
        prompt("Copia este enlace y envíalo al encargado para que firme remotamente:", link);
    }
</script>

</body>
</html>