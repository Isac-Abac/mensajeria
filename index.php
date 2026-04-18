<?php
// ------------------------------------------------------------
// Pantalla de acceso (login / registro)
// ------------------------------------------------------------
session_start();

// Si ya existe sesion, enviar al chat principal
if (isset($_SESSION['usuario_id'])) {
    header('Location: app.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajeria - Login</title>

    <script>
        // --------------------------------------------------------
        // Aplicar tema guardado antes de pintar la vista
        // --------------------------------------------------------
        (function () {
            var savedTheme = localStorage.getItem('theme');
            document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
        })();
    </script>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="auth-body">
    <main class="auth-container">
        <section class="auth-card">
            <!-- Boton para alternar tema -->
            <div class="theme-row">
                <button type="button" class="theme-toggle" data-theme-toggle>Cambiar a oscuro</button>
            </div>

            <!-- Encabezado de marca -->
            <h1 class="brand">Snipe</h1>
            <p class="subtitle">Conecta con tus amigos y comparte momentos.</p>

            <!-- Tabs de acceso -->
            <div class="tabs">
                <button class="tab-button active" data-tab="login">Iniciar sesion</button>
                <button class="tab-button" data-tab="register">Registrarse</button>
            </div>

            <!-- Formulario de login -->
            <form id="form-login" class="form active" autocomplete="off">
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" autocomplete="off" required>
                <input type="password" name="password" placeholder="Contrasena" autocomplete="new-password" required>
                <button type="submit">Entrar</button>
            </form>

            <!-- Formulario de registro -->
            <form id="form-register" class="form" autocomplete="off">
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" autocomplete="off" required>
                <input type="email" name="correo" placeholder="Correo electronico" autocomplete="off" required>
                <input type="password" name="password" placeholder="Contrasena (min 6)" autocomplete="new-password" required>
                <button type="submit">Crear cuenta</button>
            </form>

            <!-- Mensajeria de error/respuesta -->
            <p id="auth-message" class="message"></p>
        </section>
    </main>

    <!-- Librerias y scripts de la vista -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
