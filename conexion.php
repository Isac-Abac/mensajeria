<?php
// ------------------------------------------------------------
// Conexion principal a MySQL
// ------------------------------------------------------------
$Servidor = "localhost";
$Usuario = "root";
$password = "";
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
