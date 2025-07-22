<?php
session_start();
// Check if the user is not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}else{
    $username = $_SESSION['username'];
    $nombre = $_SESSION['nombre'];
    header("Location: listado.php");
    exit;
}
?>