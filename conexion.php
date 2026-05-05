<?php
// ------------------------------------------------------------
// Conexion principal a MySQL
// IMPORTANTE: Cambiar credenciales segun el entorno:
// Local XAMPP: localhost / root / (sin contraseña)
// Servidor remoto: 127.0.0.1 / isac / yolo
// ------------------------------------------------------------
//$Servidor = "localhost";
//$Usuario = "root";
//$password = "";//

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
