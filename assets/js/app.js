const usersList = document.getElementById('users-list');
const chatHeader = document.getElementById('chat-header');
const messagesWrap = document.getElementById('messages');
const sendForm = document.getElementById('send-form');
const contenido = document.getElementById('contenido');
const chatMessage = document.getElementById('chat-message');
const profileImage = document.getElementById('profile-image');
const profileFile = document.getElementById('profile-file');
const timeTooltip = document.getElementById('time-tooltip');
const avatarModal = document.getElementById('avatar-modal');
const avatarModalImage = document.getElementById('avatar-modal-image');
const avatarModalName = document.getElementById('avatar-modal-name');
const avatarModalClose = document.getElementById('avatar-modal-close');
const usuarioActualEl = document.getElementById('usuario-actual');
const emojiToggle = document.getElementById('emoji-toggle');
const emojiPanel = document.getElementById('emoji-panel');
const emojiPicker = document.getElementById('emoji-picker');

const AVATAR_POR_DEFECTO = 'assets/img/default-avatar.png';
let destinatarioSeleccionado = null;
let usuarioChatActual = null;
let autoRefresh = null;

function mostrarAlerta(titulo, texto, icono = 'info') {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        window.Swal.fire({ title: titulo, text: texto, icon: icono, confirmButtonText: 'Aceptar' });
        return;
    }
    alert(`${titulo}: ${texto}`);
}

async function requestData(url, options = {}) {
    const res = await fetch(url, options);
    const raw = await res.text();
    let data;
    try {
        data = JSON.parse(raw);
    } catch (e) {
        data = { ok: false, mensaje: raw || 'Respuesta invalida del servidor' };
    }
    return { res, data };
}

function formatearFechaHora(fechaSQL) {
    const iso = fechaSQL.replace(' ', 'T');
    const fecha = new Date(iso);
    if (Number.isNaN(fecha.getTime())) return fechaSQL;
    return new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium', timeStyle: 'short' }).format(fecha);
}

function resolverAvatar(ruta, bustCache = false) {
    if (!ruta) return AVATAR_POR_DEFECTO;
    return bustCache ? `${ruta}?v=${Date.now()}` : ruta;
}

function mostrarHoraFlotante(elemento, fechaSQL) {
    if (!timeTooltip) return;
    timeTooltip.textContent = formatearFechaHora(fechaSQL);
    const rect = elemento.getBoundingClientRect();
    timeTooltip.style.left = `${rect.left + rect.width / 2}px`;
    timeTooltip.style.top = `${rect.top - 10}px`;
    timeTooltip.classList.add('visible');
    clearTimeout(mostrarHoraFlotante.timeoutId);
    mostrarHoraFlotante.timeoutId = setTimeout(() => timeTooltip.classList.remove('visible'), 1800);
}

function cerrarMenusMensaje() {
    document.querySelectorAll('.message-menu').forEach((menu) => menu.classList.remove('visible'));
}

function abrirAvatarFlotante(usuario) {
    if (!avatarModal || !avatarModalImage || !avatarModalName || !usuario) return;
    avatarModalImage.src = resolverAvatar(usuario.foto_perfil);
    avatarModalName.textContent = '@' + usuario.nombre_usuario;
    avatarModal.classList.add('visible');
}

function abrirMiAvatarFlotante() {
    abrirAvatarFlotante({
        nombre_usuario: usuarioActualEl ? usuarioActualEl.textContent.trim() : 'Mi usuario',
        foto_perfil: profileImage ? profileImage.getAttribute('src') : null
    });
}

function cerrarAvatarFlotante() {
    if (!avatarModal) return;
    avatarModal.classList.remove('visible');
}

function insertarEmoji(emoji) {
    const start = contenido.selectionStart ?? contenido.value.length;
    const end = contenido.selectionEnd ?? contenido.value.length;
    const before = contenido.value.slice(0, start);
    const after = contenido.value.slice(end);
    contenido.value = `${before}${emoji}${after}`;
    const nextPos = start + emoji.length;
    contenido.focus();
    contenido.setSelectionRange(nextPos, nextPos);
}

function renderHeaderChat(usuario) {
    if (!usuario) {
        chatHeader.innerHTML = '<span>Selecciona una cuenta para conversar</span>';
        return;
    }

    chatHeader.innerHTML = '';
    const avatar = document.createElement('img');
    avatar.className = 'chat-header-avatar';
    avatar.src = resolverAvatar(usuario.foto_perfil);
    avatar.alt = 'Avatar de ' + usuario.nombre_usuario;
    avatar.title = 'Ver avatar';
    avatar.addEventListener('click', () => abrirAvatarFlotante(usuario));

    const nombre = document.createElement('span');
    nombre.className = 'chat-header-name';
    nombre.textContent = 'Chat con @' + usuario.nombre_usuario;

    chatHeader.appendChild(avatar);
    chatHeader.appendChild(nombre);
}

async function cargarPerfil() {
    try {
        const { res, data } = await requestData('api/get_profile.php');
        if (res.ok && data.ok && data.perfil && data.perfil.foto_perfil) {
            profileImage.src = resolverAvatar(data.perfil.foto_perfil, true);
        }
    } catch (_) {}
}

async function subirFotoPerfil(archivo) {
    const formData = new FormData();
    formData.append('foto', archivo);
    const { res, data } = await requestData('api/upload_profile.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo subir la imagen');

    if (data.foto_perfil) {
        profileImage.src = resolverAvatar(data.foto_perfil, true);
        if (usuarioChatActual && Number(usuarioChatActual.id) === Number(destinatarioSeleccionado)) {
            usuarioChatActual.foto_perfil = data.foto_perfil;
            renderHeaderChat(usuarioChatActual);
        }
    }
}

async function editarMensaje(msg) {
    let nuevoTexto = '';
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const result = await Swal.fire({
            title: 'Editar mensaje',
            input: 'text',
            inputValue: msg.contenido,
            inputLabel: 'Nuevo contenido',
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => (!value || !value.trim() ? 'El mensaje no puede estar vacio' : null)
        });
        if (!result.isConfirmed) return;
        nuevoTexto = result.value.trim();
    } else {
        const promptText = prompt('Nuevo contenido del mensaje:', msg.contenido);
        if (promptText === null) return;
        nuevoTexto = promptText.trim();
        if (!nuevoTexto) throw new Error('El mensaje no puede estar vacio');
    }

    const formData = new FormData();
    formData.append('mensaje_id', String(msg.id));
    formData.append('contenido', nuevoTexto);
    const { res, data } = await requestData('api/edit_message.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo editar el mensaje');
}

async function eliminarMensaje(msg) {
    if (window.Swal && typeof window.Swal.fire === 'function') {
        const confirmacion = await Swal.fire({
            title: 'Eliminar mensaje',
            text: 'Esta accion no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar',
            cancelButtonText: 'Cancelar'
        });
        if (!confirmacion.isConfirmed) return;
    } else if (!confirm('Esta accion no se puede deshacer. ¿Eliminar mensaje?')) {
        return;
    }

    const formData = new FormData();
    formData.append('mensaje_id', String(msg.id));
    const { res, data } = await requestData('api/delete_message.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo eliminar el mensaje');
}

window.addEventListener('load', () => {
    contenido.value = '';
    if (chatMessage) chatMessage.textContent = '';
});
window.addEventListener('pageshow', () => {
    contenido.value = '';
    if (chatMessage) chatMessage.textContent = '';
});

document.addEventListener('click', (e) => {
    if (!e.target.closest('.bubble')) timeTooltip.classList.remove('visible');
    if (!e.target.closest('.message-menu-wrap')) cerrarMenusMensaje();
    if (e.target === avatarModal) cerrarAvatarFlotante();
    if (emojiPanel && !e.target.closest('#emoji-panel') && !e.target.closest('#emoji-toggle')) {
        emojiPanel.classList.remove('visible');
    }
});

if (avatarModalClose) avatarModalClose.addEventListener('click', cerrarAvatarFlotante);
if (profileImage) {
    profileImage.style.cursor = 'pointer';
    profileImage.addEventListener('click', abrirMiAvatarFlotante);
}
if (emojiToggle && emojiPanel) {
    emojiToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        emojiPanel.classList.toggle('visible');
    });
}
if (emojiPicker) {
    emojiPicker.addEventListener('emoji-click', (event) => {
        const emoji = event?.detail?.unicode || '';
        if (!emoji) return;
        insertarEmoji(emoji);
    });
}

async function cargarUsuarios() {
    try {
        const { res, data } = await requestData('api/get_users.php');
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error', data.mensaje || 'No se pudieron cargar usuarios', 'error');
            return;
        }

        usersList.innerHTML = '';
        if (data.usuarios.length === 0) {
            usersList.innerHTML = '<li>No hay otras cuentas registradas.</li>';
            return;
        }

        data.usuarios.forEach((user) => {
            const li = document.createElement('li');
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'user-item-btn';
            btn.addEventListener('click', () => seleccionarUsuario(user, btn));

            const avatar = document.createElement('img');
            avatar.className = 'user-item-avatar';
            avatar.src = resolverAvatar(user.foto_perfil);
            avatar.alt = 'Avatar de ' + user.nombre_usuario;
            avatar.addEventListener('click', (e) => {
                e.stopPropagation();
                abrirAvatarFlotante(user);
            });

            const label = document.createElement('span');
            label.className = 'user-item-name';
            label.textContent = '@' + user.nombre_usuario;

            btn.appendChild(avatar);
            btn.appendChild(label);
            li.appendChild(btn);
            usersList.appendChild(li);
        });
    } catch (error) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
}

async function seleccionarUsuario(user, buttonRef) {
    destinatarioSeleccionado = Number(user.id);
    usuarioChatActual = user;
    renderHeaderChat(user);
    chatMessage.textContent = '';

    document.querySelectorAll('#users-list button').forEach((btn) => btn.classList.remove('active'));
    buttonRef.classList.add('active');

    await cargarMensajes();
    if (autoRefresh) clearInterval(autoRefresh);
    autoRefresh = setInterval(cargarMensajes, 3000);
}

async function cargarMensajes() {
    if (!destinatarioSeleccionado) return;

    try {
        const { res, data } = await requestData(`api/get_messages.php?destinatario_id=${destinatarioSeleccionado}`);
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error', data.mensaje || 'No se pudo cargar el chat', 'error');
            return;
        }

        messagesWrap.innerHTML = '';

        data.mensajes.forEach((msg) => {
            const esMio = Number(msg.remitente_id) === Number(window.AUTH_USER);
            const fueVisto = Number(msg.visto) === 1;
            const row = document.createElement('div');
            row.className = 'message-row ' + (esMio ? 'me' : 'other');

            const bubble = document.createElement('div');
            bubble.className = 'bubble ' + (esMio ? 'me' : 'other');
            bubble.dataset.messageId = String(msg.id);

            const text = document.createElement('div');
            text.className = 'bubble-text';
            text.textContent = msg.contenido;
            bubble.appendChild(text);

            if (esMio && fueVisto) {
                const estado = document.createElement('div');
                estado.className = 'message-status seen';
                estado.textContent = '✓✓';
                estado.title = msg.visto_en ? `Visto: ${formatearFechaHora(msg.visto_en)}` : 'Visto';
                bubble.appendChild(estado);
            }

            bubble.addEventListener('click', (e) => {
                e.stopPropagation();
                mostrarHoraFlotante(bubble, msg.enviado_en);
            });

            row.appendChild(bubble);

            if (esMio && !fueVisto) {
                const menuWrap = document.createElement('div');
                menuWrap.className = 'message-menu-wrap';

                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'message-menu-trigger';
                trigger.textContent = '⋯';
                trigger.title = 'Opciones';

                const menu = document.createElement('div');
                menu.className = 'message-menu';

                const editBtn = document.createElement('button');
                editBtn.type = 'button';
                editBtn.className = 'message-menu-item';
                editBtn.textContent = 'Editar';
                editBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    try {
                        await editarMensaje(msg);
                        await cargarMensajes();
                    } catch (error) {
                        mostrarAlerta('Error', error.message, 'error');
                    }
                });

                const deleteBtn = document.createElement('button');
                deleteBtn.type = 'button';
                deleteBtn.className = 'message-menu-item delete';
                deleteBtn.textContent = 'Eliminar';
                deleteBtn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    try {
                        await eliminarMensaje(msg);
                        await cargarMensajes();
                    } catch (error) {
                        mostrarAlerta('Error', error.message, 'error');
                    }
                });

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const visible = menu.classList.contains('visible');
                    cerrarMenusMensaje();
                    if (!visible) menu.classList.add('visible');
                });

                menu.appendChild(editBtn);
                menu.appendChild(deleteBtn);
                menuWrap.appendChild(trigger);
                menuWrap.appendChild(menu);
                row.appendChild(menuWrap);
            }

            messagesWrap.appendChild(row);
        });

        messagesWrap.scrollTop = messagesWrap.scrollHeight;
    } catch (error) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
}

sendForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (!destinatarioSeleccionado) {
        mostrarAlerta('Atencion', 'Primero selecciona una cuenta existente', 'warning');
        return;
    }

    const texto = contenido.value.trim();
    if (!texto) {
        mostrarAlerta('Atencion', 'Escribe un mensaje antes de enviar', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('destinatario_id', String(destinatarioSeleccionado));
    formData.append('contenido', texto);

    try {
        const { res, data } = await requestData('api/send_message.php', { method: 'POST', body: formData });
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error', data.mensaje || 'No se pudo enviar el mensaje', 'error');
            return;
        }

        contenido.value = '';
        chatMessage.textContent = '';
        if (emojiPanel) emojiPanel.classList.remove('visible');
        await cargarMensajes();
    } catch (error) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
});

profileFile.addEventListener('change', async () => {
    const archivo = profileFile.files && profileFile.files[0] ? profileFile.files[0] : null;
    if (!archivo) return;

    try {
        await subirFotoPerfil(archivo);
        mostrarAlerta('Listo', 'Foto de perfil actualizada', 'success');
        await cargarUsuarios();
    } catch (error) {
        mostrarAlerta('Error', error.message, 'error');
    } finally {
        profileFile.value = '';
    }
});

cargarPerfil();
cargarUsuarios();
