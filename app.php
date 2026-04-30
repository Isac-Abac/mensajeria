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

        <!-- Navegacion principal entre secciones -->
        <nav class="topbar-nav" aria-label="Navegacion principal">
            <button type="button" class="topbar-nav-btn" data-view-target="chat">Chat</button>
            <button type="button" class="topbar-nav-btn active" data-view-target="inicio">Inicio</button>
            <button type="button" class="topbar-nav-btn" data-view-target="amigos">Amigos</button>
        </nav>

        <div class="topbar-actions">
            <label for="profile-file" class="theme-toggle profile-upload-btn">Cambiar foto</label>
            <input type="file" id="profile-file" accept="image/png,image/jpeg,image/webp,image/gif" hidden>
            <button type="button" class="theme-toggle" data-theme-toggle>Cambiar a oscuro</button>
            <a class="logout" href="logout.php">Cerrar sesion</a>
        </div>
    </header>

    <!-- Contenido principal por secciones -->
    <main class="app-shell">
        <!-- Seccion Inicio: publicaciones -->
        <section id="inicio-view" class="app-view active">
            <div class="feed-layout">
                <article class="feed-card">
                    <h2>Publicaciones</h2>
                    <p class="feed-subtitle">Comparte fotos, videos o texto al estilo Instagram.</p>

                    <form id="publication-form" class="publication-form" autocomplete="off">
                        <textarea id="publication-text" placeholder="Escribe una publicacion..." rows="3"></textarea>
                        <input type="file" id="publication-file" accept="image/*,video/*" hidden>
                        <div class="publication-actions">
                            <label for="publication-file" class="theme-toggle publication-file-btn">Adjuntar media</label>
                            <button type="submit" class="publication-submit">Publicar</button>
                        </div>
                    </form>
                </article>

                <article class="feed-card">
                    <div class="feed-card-head">
                        <h3>Tu muro</h3>
                        <button type="button" id="refresh-feed" class="theme-toggle">Actualizar</button>
                    </div>
                    <div id="feed-list" class="feed-list"></div>
                </article>
            </div>
        </section>

        <!-- Seccion Amigos -->
        <section id="amigos-view" class="app-view">
            <div class="friends-layout">
                <article class="friends-card">
                    <h2>Amigos</h2>
                    <p class="feed-subtitle">Envía solicitudes y revisa las que te llegan.</p>

                    <form id="friend-form" class="friend-form" autocomplete="off">
                        <input type="text" id="friend-username" placeholder="Nombre de usuario" required>
                        <button type="submit">Agregar amigo</button>
                    </form>
                </article>

                <article class="friends-card">
                    <div class="feed-card-head">
                        <h3>Solicitudes de amistad</h3>
                        <button type="button" id="refresh-requests" class="theme-toggle">Actualizar</button>
                    </div>
                    <div id="requests-list" class="friends-list"></div>
                </article>

                <article class="friends-card">
                    <div class="feed-card-head">
                        <h3>Mis amigos</h3>
                        <button type="button" id="refresh-friends" class="theme-toggle">Actualizar</button>
                    </div>
                    <div id="friends-list" class="friends-list"></div>
                </article>
            </div>
        </section>

        <!-- Seccion Chat -->
        <section id="chat-view" class="app-view">
            <div class="chat-layout">
                <aside class="users-panel">
                    <h2>amigos en linea</h2>
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
            </div>
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
