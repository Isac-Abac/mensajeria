<?php
// ------------------------------------------------------------
// Cierre de sesion de usuario
// ------------------------------------------------------------
session_start();
session_unset();
session_destroy();

// Redireccion al login
header('Location: index.php');
exit;
?>
