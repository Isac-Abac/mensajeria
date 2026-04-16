<?php
session_start();
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
            <div class="theme-row">
                <button type="button" class="theme-toggle" data-theme-toggle>Cambiar a oscuro</button>
            </div>

            <h1 class="brand">Snipe</h1>
            <p class="subtitle">Conecta con tus amigos y comparte momentos.</p>

            <div class="tabs">
                <button class="tab-button active" data-tab="login">Iniciar sesion</button>
                <button class="tab-button" data-tab="register">Registrarse</button>
            </div>

            <form id="form-login" class="form active" autocomplete="off">
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" autocomplete="off" required>
                <input type="password" name="password" placeholder="Contrasena" autocomplete="new-password" required>
                <button type="submit">Entrar</button>
            </form>

            <form id="form-register" class="form" autocomplete="off">
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" autocomplete="off" required>
                <input type="email" name="correo" placeholder="Correo electronico" autocomplete="off" required>
                <input type="password" name="password" placeholder="Contrasena (min 6)" autocomplete="new-password" required>
                <button type="submit">Crear cuenta</button>
            </form>

            <p id="auth-message" class="message"></p>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/auth.js"></script>
</body>
</html>
