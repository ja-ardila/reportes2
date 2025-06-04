<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

include "conexion.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["correo"])) {
    $correo = trim($_POST["correo"]);

    $sql = "SELECT id FROM usuarios WHERE usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $token = bin2hex(random_bytes(16));
        $update = $conexion->prepare("UPDATE usuarios SET token = ? WHERE usuario = ?");
        $update->bind_param("ss", $token, $correo);
        $update->execute();

        $enlace = "https://h323.com.co/reportes/recuperar.php?token=$token";

        // Configura PHPMailer
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'admin@h323.company'; // Tu correo
            $mail->Password   = 'zvwr qoho ovzn hosy'; // Contraseña app
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('admin@h323.company', 'Sistema de Reportes');
            $mail->addAddress($correo);

            $mail->isHTML(true);
            $mail->Subject = 'Recuperacion de contrasena';

            // Logo embebido (CID)
            $mail->AddEmbeddedImage(__DIR__ . '/H323_LOGO.png', 'logo_h323');

            // Cuerpo HTML
            $mail->Body = '
            <html>
            <body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
                <div style="max-width: 500px; margin: auto; background: #fff; border-radius: 10px; padding: 30px; text-align: center;">
                    <img src="cid:logo_h323" alt="Logo" style="width: 150px; margin-bottom: 20px;">
                    <h2 style="color: #005baa;">Recuperación de contraseña</h2>
                    <p>Hola,</p>
                    <p>Recibimos una solicitud para restablecer tu contraseña.</p>
                    <p>
                        <a href="' . $enlace . '" style="display: inline-block; background-color: #005baa; color: #fff; padding: 12px 20px; border-radius: 6px; text-decoration: none; margin: 20px 0;">Restablecer contraseña</a>
                    </p>
                    <p>Si no solicitaste este cambio, puedes ignorar este mensaje.</p>
                    <hr style="margin: 30px 0;">
                    <small style="color: #999;">Este mensaje fue generado automáticamente, por favor no respondas.</small>
                </div>
            </body>
            </html>';

            $mail->send();
            echo "<script>alert('Se ha enviado un correo con instrucciones.'); window.location='login.php';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error al enviar el correo: {$mail->ErrorInfo}'); window.location='olvidar_contrasena';</script>";
        }
    } else {
        echo "<script>alert('El correo no está registrado.'); window.location='olvidar_contrasena.php';</script>";
    }

    $stmt->close();
    $conexion->close();
}
?>