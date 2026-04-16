<?php

$Servidor = "localhost";
$Usuario = "root";
$password = "";
$BaseDeDatos = "usuarios";

$conn = new mysqli($Servidor, $Usuario, $password, $BaseDeDatos);

if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
