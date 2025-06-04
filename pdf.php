<?php
include("conexion.php");
require('fpdf/fpdf.php');
session_start();

// Obtener el nombre del usuario desde la sesión
$usuario_que_genera = $_SESSION['nombre'] ?? 'Desconocido';

// ¿Desde dónde viene?
if (isset($_GET['token'])) {
    // Buscar por token
    $modo_acceso = 'token';
    $valor_busqueda = $_GET['token'];
    $sql = "SELECT * FROM reportes WHERE token = ?";
} elseif (isset($_GET['id']) && isset($_SESSION['nombre'])) {
    // Buscar por ID del reporte (modo sesión)
    $modo_acceso = 'id';
    $valor_busqueda = $_GET['id'];
    $sql = "SELECT * FROM reportes WHERE id = ?";
} elseif (isset($_GET['nreporte']) && isset($_SESSION['nombre'])) {
    // Buscar por número de reporte (modo sesión)
    $modo_acceso = 'nreporte';
    $valor_busqueda = $_GET['nreporte'];
    $sql = "SELECT * FROM reportes WHERE nreporte = ?";
} else {
    die("Error: No se proporcionó el token, ID o número de reporte, o no estás logueado.");
}

// Ejecutar la consulta
$stmt = $conexion->prepare($sql);

if ($modo_acceso == 'token' || $modo_acceso == 'nreporte') {
    $stmt->bind_param("s", $valor_busqueda); // token y nreporte son cadenas
} else {
    $stmt->bind_param("i", $valor_busqueda); // id es entero
}

$stmt->execute();
$resultado = $stmt->get_result();

// Verificar que la consulta devolvió resultados
if ($resultado && $resultado->num_rows > 0) {
    $fila = $resultado->fetch_assoc();

    // Asignar variables con los datos de la base de datos
    $nreporte = $fila['numero_reporte'];
    $fecha = $fila['fecha'];
    $empresa = $fila['empresa'];
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
    $cedulat = $fila['cedula_tecnico'];
    $nombret = $fila['nombre_tecnico'];
    $firma = $fila['firma_tecnico'];
    $cedulae = $fila['cedula_encargado'];
    $nombree = $fila['nombre_encargado'];
    $firmae = $fila['firma_encargado'];
    $informe = $fila['informe'];
  	$informe = str_replace("·$", "\n", $informe);
    $observaciones = $fila['observaciones'];
  	$observaciones = str_replace("·$", "\n", $observaciones);
    $id_reporte = $fila['id'];
} else {
    die("No se encontró el reporte.");
}

// Obtener imágenes del reporte
$imagenes = [];
$sqlImg = "SELECT ruta_imagen FROM imagenes WHERE id_reporte = ?";
$stmtImg = $conexion->prepare($sqlImg);
$stmtImg->bind_param("i", $id_reporte);
$stmtImg->execute();
$resImg = $stmtImg->get_result();
while ($img = $resImg->fetch_assoc()) {
    $imagenes[] = $img['ruta_imagen'];
}

class PDF extends FPDF {
    //obtener dimension iamgen
    function CellImageCentered($file, $cellWidth, $cellHeight){
        list($imageWidth, $imageHeight) = getimagesize($file);
    //calcular escala para ajustar la imagen dentro de la celda
        $scale = min($cellWidth / $imageWidth, $cellHeight / $imageHeight);
        $imageWidth *= $scale;
        $imageHeight *= $scale;
    //calcular la posiscion x e y para centrar la imagen dentro de la celda 
        $x = $this->GetX() + ($cellWidth - $imageWidth) / 2;
        $y = $this->GetY() + ($cellHeight - $imageHeight) / 2;
    //agregar la imagen a la posicion calculada 
        $this->Image($file, $x, $y, $imageWidth, $imageHeight);
    }
    
    function CellImageCenteredLogo($file, $cellWidth, $cellHeight){
    //obtener dimension de la imagen 
        list($imageWidth, $imageHeight) = getimagesize($file);
    //calacular escala para ajustar la imagen dentro de la celda 
        $scale = min($cellWidth / $imageWidth, $cellHeight / $imageHeight) * 0.65;
        $imageWidth *= $scale*0.9;
        $imageHeight *= $scale*0.9;
    //calcular la posicion en x e y para centrar la imagen dentro de la celda
        $x = $this->GetX() + ($cellWidth - $imageWidth) / 2;
        $y = $this->GetY() + ($cellHeight - $imageHeight) / 2;
    //agregar la imagen a la posiscion calculada
        $this->Image($file, $x, $y, $imageWidth, $imageHeight);
    }
    
    function CellImageCenteredFirma($file, $cellWidth, $cellHeight){
    //obtener dimension imagen
        list($imageWidth, $imageHeight) = getimagesize($file);
    //calcular escala para ajustar la imagen dentro de la celda
        $scale = min($cellWidth / $imageWidth, $cellHeight / $imageHeight) * 0.95;
        $imageWidth *= $scale*0.95;
        $imageHeight *= $scale*0.95;
    //calcular la posiscion en x e y para centrar la imagen dentro de la celda
        $x = $this->GetX() + ($cellWidth - $imageWidth) / 2;
        $y = $this->GetY() + ($cellHeight - $imageHeight) / 2;
    //agregar la imagen a la posicion calculada
        $this->Image($file, $x, $y, $imageWidth, $imageHeight);
    }
}

$pdf = new PDF();
$pdf->SetTitle("Reporte No. $nreporte", false);
//añadir una pagina
$pdf->AddPage();

// Quién generó el reporte
$pdf->SetFont('Arial','I',10);//tipo letra
$pdf->SetTextColor(128, 128, 128);//color gris
$pdf->SetFont('','I');//solo cursiva, sin cambiar la fuente actual
$pdf->Cell(0,10,'Generado por: ' . $nombret, 0, 1, 'R');//genera el nombre de quien creo el reporte en la parte inferior alineado a la derecha
$pdf->SetTextColor(0, 0, 0);//restaurar el color negro (opcional para lo que venga despues)
$pdf->SetFont('', '');//restaurar estilo normal (opcional)

// imprimir la informacion utilizando las variables 
$pdf->Cell(95, 40, $pdf->CellImageCenteredLogo("H323_LOGO.png", 95, 40), 1, 0, "C");
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(47.5, 40, iconv('UTF-8', 'windows-1252', "REPORTE GENERAL"), 1, 0, "C");
$x1 = $pdf->GetX();
$pdf->SetX($x1);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(47.5, 12, iconv('UTF-8', 'windows-1252', "ORDEN DE SERVICIO"), 1, 1, "C");
$pdf->SetX($x1);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', $nreporte), 1, 1, "C");
$pdf->SetX($x1);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(47.5, 12, iconv('UTF-8', 'windows-1252', "FECHA"), 1, 1, "C");
$pdf->SetX($x1);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', $fecha), 1, 1, "C");

// Datos del cliente
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(190, 10, iconv('UTF-8', 'windows-1252', "DATOS DEL CLIENTE"), 1, 1, "C");
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "Empresa: ".$empresa), 1, 0, "L");
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Nit: ".$nit), 1, 0, "L");
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Teléfono: ".$telefono), 1, 1, "L");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "Dirección: ".$direccion), 1, 0, "L");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "E-mail: ".$email), 1, 1, "L");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "Contacto: ".$contacto), 1, 0, "L");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "Ciudad: ".$ciudad), 1, 1, "L");

// Fechas y Horas
$pdf->SetY($pdf->GetY()+8);
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Fecha inicio:"), 1, 0, "C");
$pdf->Cell(47.5, 8, $fechai, 1, 0, "C");
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Fecha cierre:"), 1, 0, "C");
$pdf->Cell(47.5, 8, $fechac, 1, 1, "C");
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Hora inicio:"), 1, 0, "C");
$pdf->Cell(47.5, 8, $horai, 1, 0, "C");
$pdf->Cell(47.5, 8, iconv('UTF-8', 'windows-1252', "Hora cierre:"), 1, 0, "C");
$pdf->Cell(47.5, 8, $horac, 1, 1, "C");

// Servicio reportado y tipo de servicio
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', "SERVICIO REPORTADO:"), "LR", 1, "L");
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', $servicior), "LRB", 1, "L");
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', "TIPO DE SERVICIO:"), "LR", 1, "L");
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', $tiposervicio), "LRB", 1, "L");

// Técnico
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "Nombre del técnico / ingeniero"), 1, 0, "C");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', $nombret), 1, 1, "C");

// Normalizar saltos de línea
$informe = preg_replace("/\r\n|\r|\n/", "\n", $informe);
$observaciones = preg_replace("/\r\n|\r|\n/", "\n", $observaciones);

// Informe y Observaciones
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', "INFORME TÉCNICO:"), "LR", 1, "L");
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(190, 3, iconv('UTF-8', 'windows-1252', $informe), "LRB", "L", 0);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(190, 8, iconv('UTF-8', 'windows-1252', "OBSERVACIONES:"), "LR", 1, "L");
$pdf->SetFont('Arial', '', 8);
$pdf->MultiCell(190, 3, iconv('UTF-8', 'windows-1252', $observaciones), "LRB", "L", 0);


// Mostrar imagenes y firmas
$y = $pdf->GetY();
$i = 0;
$j = 0;
$cellHeight = 44.3;
$cellWidth = 190/3;
$id_reporte = $fila['id']; 
$imagenes = [];
$sql_imagenes = "SELECT ruta_imagen FROM imagenes WHERE id_reporte = '$id_reporte'";
$resultado_imagenes = $conexion->query($sql_imagenes);

if ($resultado_imagenes && $resultado_imagenes->num_rows > 0) {
    while ($fila_img = $resultado_imagenes->fetch_assoc()) {
        $imagenes[] = $fila_img['ruta_imagen'];
    }
}

foreach($imagenes as $imagen){
    $j = $j+1;
    if($pdf->GetY()>233){
        $pdf->AddPage();
    }
    if($j == 3){
        $i = 1;
        $j = 0;
    }else{
        $i = 0;
    }
    $pdf->Cell($cellWidth, $cellHeight, $pdf->CellImageCentered($imagen, $cellWidth, $cellHeight), 1, $i, "R");
    
}
if ($j == 1){
    $pdf->Cell($cellWidth, $cellHeight, null, 1, 0, "R");
    $pdf->Cell($cellWidth, $cellHeight, null, 1, 1, "R");
}elseif($j == 2){
    $pdf->Cell($cellWidth, $cellHeight, null, 1, 1, "R");
}
$y = $pdf->GetY();
$pdf->SetY($y+4);
$pdf->SetFont('Arial', 'B', 8);
if($pdf->GetY()>233){
        $pdf->AddPage();
    }
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "ENTREGA:"), 1, 0, "L");
$pdf->Cell(95, 8, iconv('UTF-8', 'windows-1252', "RECIBO A SATISFACCIÓN CLIENTE:"), 1, 1, "L");
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(95, 24, $pdf->CellImageCenteredFirma("firmas/$firma", 95, 24), "LRT", 0, "L");

if(empty($firmae)){
    $firmaePDF = null;
}else{
    $firmaePDF = $pdf->CellImageCenteredFirma("signatures/$firmae", 95, 24);
}

$pdf->Cell(95, 24, $firmaePDF, "LRT", 1, "L");
$pdf->Cell(95, 5, iconv('UTF-8', 'windows-1252', "CC: $cedulat"), "LR", 0, "C");
$pdf->Cell(95, 5, iconv('UTF-8', 'windows-1252', "Nombre encargado: $nombree"), "LR", 1, "C");
$pdf->Cell(95, 5, iconv('UTF-8', 'windows-1252', "Firma y cédula"), "LRB", 0, "C");
$pdf->Cell(95, 5, iconv('UTF-8', 'windows-1252', "CC: $cedulae"), "LRB", 1, "C");

$y = $pdf->GetY();
$pdf->SetY($y+4);
$pdf->Cell(190, 5, iconv('UTF-8', 'windows-1252', "Cra 7 # 180 - 30 Torre A Oficina 304 PBX 3004048"), 1, 1, "C");
$pdf->Cell(190, 5, iconv('UTF-8', 'windows-1252', "info@h323.com.co"), 1, 1, "C", 0, "mailto:info@h323.com.co");

// Generar el PDF
$pdf->Output();
?>


