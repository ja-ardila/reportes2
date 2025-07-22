<?php
include("conexion.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_imagen = $_POST['id_imagen'];
    $ruta_imagen = $_POST['ruta_imagen'];
    $id_reporte = $_POST['id_reporte'];

    // Elimina el archivo físicamente del servidor
    if (file_exists($ruta_imagen)) {
        unlink($ruta_imagen);
    }

    // Elimina el registro de la base de datos
    $sql = "DELETE FROM imagenes WHERE ID = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id_imagen);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Redirecciona de vuelta a la página de edición
    $id_reporte = $_POST['id_reporte'];
    header("Location: editar.php?id=" . $id_reporte);
    exit();
} else {
    echo "Acceso no permitido.";
}
?>
