<?php
// olvidar_contrasena.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar contraseña</title>
    <style>
        body {
            background-color: #f2f2f2;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #007bff;
            margin-bottom: 20px;
        }
        input[type="email"], button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #007bff;
            color: #fff;
            cursor: pointer;
            border: none;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Recuperar contraseña</h2>
    <form action="enviar_recuperacion.php" method="POST">
        <label for="correo">Correo registrado:</label>
        <input type="email" name="correo" required>
        <button type="submit">Enviar enlace de recuperación</button>
    </form>
</div>
</body>
</html>