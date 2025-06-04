<?php
session_start();
require 'conexion.php';

// Verifica si el usuario es administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: listado.php");
    exit();
}

// Verifica que se haya recibido un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    // Prepara y ejecuta la eliminación segura
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        // Eliminado con éxito
        header("Location: ver_usuarios.php?msg=eliminado");
        exit();
    } else {
        echo "Error al eliminar el usuario.";
    }

    $stmt->close();
} else {
    echo "ID de usuario no válido.";
}

$conexion->close();
                ?>
                <?php
          //      // eliminar_usuarios.php - Soft delete con CSS actualizado
           //     require 'conexion.php';
             //   session_start();

                // Verifica que sea administrador
           //     if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'admin') {
         //           header('Location: listado.php');
             //       exit();
             //   }

               // if (isset($_GET['id'])) {
               //    $id = $_GET['id'];

//                    $stmt = $conn->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
  //                  $stmt->bind_param("i", $id);

//                    if ($stmt->execute()) {
  //                      header("Location: ver_usuarios.php?msg=eliminado");
    //                } else {
      //                  echo "<div class='error'>Error al eliminar usuario: " . $stmt->error . "</div>";
        //            }
          //      } else {
            //        echo "<div class='error'>ID no proporcionado.</div>";
              //  }
                ?>