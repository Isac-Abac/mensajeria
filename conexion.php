<?php
// ------------------------------------------------------------
// Conexion principal a MySQL
// ------------------------------------------------------------
//$Servidor = "localhost";
//$Usuario = "root";

//$Servidor = "127.0.0.1";
$Servidor = "127.0.0.1";
$Usuario = "isac";
$password = "yolo";
$BaseDeDatos = "usuarios";


// Crear conexion
$conn = new mysqli($Servidor, $Usuario, $password, $BaseDeDatos);

// Validar estado de conexion
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}

// Forzar juego de caracteres UTF-8
$conn->set_charset("utf8mb4");
?>
