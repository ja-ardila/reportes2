<?php
include("conexion.php");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);

if (
    !isset($input['token']) ||
    !isset($input['firma']) ||
    !isset($input['nombre_encargado']) ||
    !isset($input['cedula_encargado'])
) {
    echo "Datos incompletos.";
    exit;
}

$token = $input['token'];
$firmae = $input['firma'];
$nombre_encargado = $input['nombre_encargado'];
$cedula_encargado = $input['cedula_encargado'];

// Buscar el número de reporte usando el token
$stmt = $conexion->prepare("SELECT numero_reporte FROM reportes WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "Token no válido.";
    exit;
}

$row = $result->fetch_assoc();
$nreporte = $row['numero_reporte'];

// Procesar y guardar la firma como imagen
$firmae = str_replace('data:image/png;base64,', '', $firmae);
$firmae = str_replace(' ', '+', $firmae);
$data = base64_decode($firmae);
$file = uniqid() . '.png';
file_put_contents('signatures/' . $file, $data);

// Guardar datos en base de datos
$stmt = $conexion->prepare("UPDATE reportes SET firma_encargado = ?, nombre_encargado = ?, cedula_encargado = ? WHERE numero_reporte = ?");
$stmt->bind_param("ssss", $file, $nombre_encargado, $cedula_encargado, $nreporte);
$stmt->execute();

echo "Firma y datos del encargado guardados correctamente.";
?>