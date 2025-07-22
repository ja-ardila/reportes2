<?php
include("conexion.php");

$mensaje = "";
$token = $_GET['token'] ?? '';
$token_valido = false;

// Verificar si el token existe en la base de datos
if (!empty($token)) {
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($resultado->num_rows > 0) {
        $token_valido = true;
    }
    $stmt->close();
}

// Procesar formulario de recuperación
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $nueva_contrasena = $_POST['contrasena'];
    $confirmar_contrasena = $_POST['confirmar_contrasena'];

    if (strlen($nueva_contrasena) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres.";
    } elseif ($nueva_contrasena !== $confirmar_contrasena) {
        $mensaje = "Las contraseñas no coinciden.";
    } else {
        $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows > 0) {
            $usuario = $resultado->fetch_assoc();
            $id_usuario = $usuario['id'];

            $hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
            $update = $conexion->prepare("UPDATE usuarios SET contrasena = ?, token = NULL WHERE id = ?");
            $update->bind_param("si", $hash, $id_usuario);

            if ($update->execute()) {
                $mensaje = "Tu contraseña ha sido restablecida exitosamente. Ahora puedes <a href='login.php'>iniciar sesión</a>.";
                $token_valido = false;
            } else {
                $mensaje = "Error al actualizar la contraseña.";
            }
            $update->close();
        } else {
            $mensaje = "Token inválido o expirado.";
        }
        $stmt->close();
    }

    $conexion->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <style>
        /* Mismos estilos que asignar_contraseña.php */
        body { font-family: Arial, sans-serif; background-color: #f1f4fa; display: flex; align-items: center; justify-content: center; height: 100vh; }
        .formulario { background: white; padding: 25px 30px; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); width: 100%; max-width: 400px; color: #333; }
        h3 { margin-bottom: 20px; color: #007bff; }
        label { display: block; margin-top: 10px; font-size: 14px; }
        input[type="password"] { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #ccc; border-radius: 5px; }
        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 10px; top: 35%; cursor: pointer; user-select: none; font-size: 16px; }
        input[type="submit"] { margin-top: 20px; width: 100%; background-color: #007bff; color: white; border: none; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer; }
        .mensaje { margin-top: 15px; color: green; font-size: 14px; }
        .error { color: red; font-size: 14px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="formulario">
        <h3>Recuperar contraseña</h3>

        <?php if (!empty($mensaje)): ?>
            <p class="<?= strpos($mensaje, 'error') !== false ? 'error' : 'mensaje' ?>">
                <?= $mensaje ?>
            </p>
        <?php elseif ($token_valido): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                <label>Nueva contraseña:</label>
                <div class="password-container">
                    <input type="password" name="contrasena" id="contrasena" minlength="6" required>
                    <span class="toggle-password" onclick="togglePassword('contrasena', this)">👁️</span>
                </div>

                <label>Confirmar contraseña:</label>
                <div class="password-container">
                    <input type="password" name="confirmar_contrasena" id="confirmar_contrasena" minlength="6" required>
                    <span class="toggle-password" onclick="togglePassword('confirmar_contrasena', this)">👁️</span>
                </div>

                <input type="submit" value="Actualizar contraseña">
                <p class="error error-js"></p>
            </form>
        <?php else: ?>
            <p class="mensaje">El enlace de recuperación no es válido o ya fue utilizado.</p>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(id, el) {
            const input = document.getElementById(id);
            if (input.type === "password") {
                input.type = "text";
                el.textContent = "🙈";
            } else {
                input.type = "password";
                el.textContent = "👁️";
            }
        }

        document.querySelector("form")?.addEventListener("submit", function(event) {
            const contrasena = document.getElementById("contrasena").value;
            const confirmar = document.getElementById("confirmar_contrasena").value;
            let error = "";

            if (contrasena.length < 6) {
                error = "La contraseña debe tener al menos 6 caracteres.";
            } else if (contrasena !== confirmar) {
                error = "Las contraseñas no coinciden.";
            }

            if (error) {
                event.preventDefault();
                document.querySelector(".error-js").textContent = error;
            }
        });
    </script>
</body>
</html>