<?php
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function enviarCorreoAsignarContrasena($correo_destino, $nombre_usuario) {
    // Generar token
    $token = bin2hex(random_bytes(32));
    $url_token = "http://h323.com.co/reportes/asignar_contrasena.php?token=" . $token;

    // Guardar token en la base de datos
    include("conexion.php");
    $consulta = $conexion->prepare("UPDATE usuarios SET token=? WHERE usuario=?");
    $consulta->bind_param("ss", $token, $correo_destino);
    $consulta->execute();
    $consulta->close();
    $conexion->close();

    // Enviar correo
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'admin@h323.company'; // Reemplazar
        $mail->Password   = 'zvwr qoho ovzn hosy'; // Reemplazar
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        $mail->setFrom('admin@h323.company', 'Sistema de Reportes');
        $mail->addAddress($correo_destino, $nombre_usuario);

        $mail->isHTML(true);
        $mail->Subject = 'Asignacion de contrasena';
      	$mail->AddEmbeddedImage(__DIR__ . '/H323_LOGO.png', 'logo_h323');
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='es'>
        <head>
          <meta charset='UTF-8'>
          <style>
            body {
              background-color: #f4f4f4;
              font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
              margin: 0;
              padding: 0;
            }
            .container {
              background-color: #ffffff;
              max-width: 600px;
              margin: 40px auto;
              padding: 30px;
              border-radius: 10px;
              box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .header {
              text-align: center;
              padding-bottom: 20px;
              border-bottom: 1px solid #e0e0e0;
            }
            .header h2 {
              color: #333;
            }
            .content {
              padding-top: 20px;
              color: #444;
              font-size: 16px;
              line-height: 1.6;
            }
            .button {
              display: inline-block;
              margin-top: 20px;
              padding: 12px 24px;
              background-color: #004080;
              color: #d70000;
              text-decoration: none;
              border-radius: 5px;
              font-weight: bold;
            }
            .footer {
              margin-top: 30px;
              font-size: 13px;
              color: #888;
              text-align: center;
            }
          </style>
        </head>
        <body>
          <div class='container'>
            <div class='header'>
              <h2>Sistema de Reportes - H323</h2>
            </div>
            <div class='content'>
            <img src='cid:logo_h323' alt='Logo' style='width:150px;'><br><br>
              <p>Hola <strong>$nombre_usuario</strong>,</p>
              <p>Se ha creado una cuenta para ti en el sistema de reportes de H323 Telecomunicaciones.</p>
              <p>Para establecer tu contraseña, por favor haz clic en el siguiente botón:</p>
              <p style='text-align: center;'>
                <a href='$url_token' class='button'>Crear contraseña</a>
              </p>
              <p>Este enlace es válido por un solo uso.</p>
              <p>Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p>
              <div class='footer'>
                Equipo de Soporte - H323 Telecomunicaciones
              </div>
            </div>
          </div>
        </body>
        </html>
        ";
        $mail->send();
        // No se muestra mensaje aquí porque ya se notifica desde insertar_usuario.php
    } catch (Exception $e) {
        // Puedes loguear errores si lo deseas
        error_log("Error al enviar correo: " . $mail->ErrorInfo);
    }
}
?>

