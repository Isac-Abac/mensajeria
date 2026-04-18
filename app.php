<?php
// ------------------------------------------------------------
// Vista principal del chat
// ------------------------------------------------------------
session_start();

// Proteger la vista si no hay sesion
if (!isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

// Nombre mostrado en cabecera
$usuario = htmlspecialchars($_SESSION['nombre_usuario'], ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Snipe</title>

    <script>
        // --------------------------------------------------------
        // Aplicar tema guardado antes de renderizar la pagina
        // --------------------------------------------------------
        (function () {
            var savedTheme = localStorage.getItem('theme');
            document.documentElement.setAttribute('data-theme', savedTheme === 'dark' ? 'dark' : 'light');
        })();
    </script>

    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="chat-body">
    <!-- Barra superior con perfil y acciones globales -->
    <header class="topbar">
        <div class="profile-header">
            <img id="profile-image" class="profile-avatar" src="assets/img/default-avatar.png" alt="Foto de perfil">
            <div>
                <h1>Snipe</h1>
                <p>Bienvenido, <strong id="usuario-actual"><?php echo $usuario; ?></strong></p>
            </div>
        </div>

        <div class="topbar-actions">
            <label for="profile-file" class="theme-toggle profile-upload-btn">Cambiar foto</label>
            <input type="file" id="profile-file" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
            <button type="button" class="theme-toggle" data-theme-toggle>Cambiar a oscuro</button>
            <a class="logout" href="logout.php">Cerrar sesion</a>
        </div>
    </header>

    <!-- Layout: lista de usuarios + area de conversacion -->
    <main class="chat-layout">
        <aside class="users-panel">
            <h2>Cuentas</h2>
            <ul id="users-list"></ul>
        </aside>

        <section class="chat-panel">
            <div id="chat-header" class="chat-header">
                <span>Selecciona una cuenta para conversar</span>
            </div>

            <div id="messages" class="messages"></div>

            <!-- Composer de mensaje -->
            <form id="send-form" class="send-form" autocomplete="off">
                <button type="button" id="attach-toggle" class="attach-toggle" title="Adjuntar">+</button>
                <textarea id="contenido" placeholder="Escribe un mensaje..." required></textarea>
                <button type="button" id="emoji-toggle" class="emoji-toggle" title="Emojis">&#128522;</button>
                <button type="button" id="mic-toggle" class="mic-toggle" title="Grabar audio">&#127908;</button>
                <button type="submit">Enviar</button>
            </form>

            <!-- Menu de adjuntos -->
            <div id="attach-menu" class="attach-menu">
                <button type="button" class="attach-option" data-attach-type="imagenes">Imagenes</button>
                <button type="button" class="attach-option" data-attach-type="videos">Videos</button>
                <button type="button" class="attach-option" data-attach-type="documentos">Documentos</button>
                <button type="button" class="attach-option" data-attach-type="audios">Audios</button>
            </div>

            <!-- Inputs ocultos para carga de archivos -->
            <input type="file" id="file-imagenes" accept="image/*" hidden>
            <input type="file" id="file-videos" accept="video/*" hidden>
            <input type="file" id="file-documentos" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx,.ppt,.pptx,.zip,.rar" hidden>
            <input type="file" id="file-audios" accept="audio/*" hidden>

            <!-- Panel de emojis -->
            <div id="emoji-panel" class="emoji-panel">
                <emoji-picker id="emoji-picker" class="emoji-picker"></emoji-picker>
            </div>

            <p id="chat-message" class="message"></p>
        </section>
    </main>

    <!-- Tooltip de hora de mensaje -->
    <div id="time-tooltip" class="time-tooltip"></div>

    <!-- Modal flotante para vista de avatar -->
    <div id="avatar-modal" class="avatar-modal">
        <div class="avatar-modal-card">
            <button type="button" id="avatar-modal-close" class="avatar-modal-close">x</button>
            <img id="avatar-modal-image" class="avatar-modal-image" src="assets/img/default-avatar.png" alt="Avatar ampliado">
            <p id="avatar-modal-name" class="avatar-modal-name"></p>
        </div>
    </div>

    <script>
        // Exponer id del usuario autenticado al frontend
        window.AUTH_USER = <?php echo (int) $_SESSION['usuario_id']; ?>;
    </script>

    <!-- Librerias y scripts de la vista -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/theme.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
