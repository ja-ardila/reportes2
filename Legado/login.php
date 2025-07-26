<?php
session_start();
include("conexion.php");


// Comprobar si el usuario ya ha iniciado sesión
if (isset($_SESSION['usuario'])) {
    // Redirigir según el rol si ya está logueado
    if ($_SESSION['rol'] === 'admin') {
        header("Location: admin_dashboard.php");
    } else {
        header("Location: listado.php");
    }
    exit;
}

// Crear conexión
$conexion = new mysqli("jardila-reportes2.ceayj8wgpqm0.us-east-1.rds.amazonaws.com:3306", "jardila_reportes", "Zsw2Xaq1", "jardila_reportes2");

// Verificar conexión
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

// Comprobar si el formulario está enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Recuperar el nombre de usuario y la contraseña enviados
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Consulta SQL
    $consulta = "SELECT id, usuario, nombre, contrasena, rol FROM usuarios WHERE usuario = ?";
    $stmt = $conexion->prepare($consulta);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $fila = $resultado->fetch_assoc();
        
        // Verificar la contraseña
        if (password_verify($password, $fila['contrasena'])) {
            $_SESSION["usuario"] = $fila["usuario"];
            $_SESSION['id_usuario'] = $fila['id'];
            $_SESSION["nombre"] = $fila["nombre"];
            $_SESSION["rol"] = $fila["rol"];
            
            // Redirigir según rol
            if ($fila['rol'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: listado.php");
            }
            exit;
        } else {
            echo "<script>alert('Contraseña incorrecta'); window.location.href='login.php';</script>";
        }
    } else {
        echo "<script>alert('Usuario no encontrado'); window.location.href='login.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Page</title>
    <style>
        body {
          font-family: Arial, sans-serif;
          background-color: #f2f2f2;
        }
        
        .container {
          width:80%;
          margin: 0 auto;
          padding: 20px;
          background-color: #ffffff;
          border: 1px solid #cccccc;
          border-radius: 5px;
          box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
          text-align: center;
          color: #333333;
        }
        
        form {
          margin-top: 20px;
        }
        
        label {
          display: block;
          margin-bottom: 5px;
          color: #666666;
        }
        
        input[type="text"],
        input[type="password"] {
          width: 96%;
          padding:2%;
          border: 1px solid #cccccc;
          border-radius: 3px;
        }
        
        input[type="submit"] {
          width: 100%;
          padding: 10px;
          background-color:  #007bff;
          color: #ffffff;
          border: none;
          border-radius: 3px;
          cursor: pointer;
        }
        
        input[type="submit"]:hover {
          background-color:#0056b3;
        }
        
        .error-message {
          color: #ff0000;
          margin-top: 10px;
        }
        @media screen and (min-width: 1200px) {
            .container {
                max-width: 400px;
                width:80%;
            }
        }
        @media screen and (max-width: 1090px) {
            form {
                width:100%;
                margin: 0 auto;
                background-color: #fff;
                padding: 4%;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            .container {
                width:80%;
            }
            body {
                font-size: 40px;
            }
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 10px;
                font-size: 40px;
                box-sizing: border-box;
            }
            input[type="submit"] {
                font-size: 40px;
            }
        }
        @media screen and (max-width: 1200px) {
            form {
                max-width: 82%;
                margin: 0 auto;
                background-color: #fff;
                padding: 4%;
                border-radius: 4px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            }
            body {
                font-size: 40px;
            }
            input[type="text"],
            input[type="password"] {
                width: 100%;
                padding: 10px;
                font-size: 40px;
                box-sizing: border-box;
            }
            input[type="submit"] {
                font-size: 40px;
            }
        }
    </style>
</head>
<body>
    <h1>Login</h1>
    <?php if (isset($error)) { ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php } ?>
    <form method="POST" action="" class="container">
        <label for="username">Usuario:</label>
        <input type="text" name="username" id="username" required><br><br>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" id="password" required><br><br>

        <input type="submit" value="Login">
      
      <div style="margin-top: 10px; text-align: center;">
        <a href="olvidar_contrasena.php" style="color: #007bff; text-decoration: none;">¿Olvidó su contraseña?</a>
      </div>
    </form>
</body>
</html>