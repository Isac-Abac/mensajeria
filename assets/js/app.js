// ============================================================
// Script principal del chat
// ============================================================

// ------------------------------------------------------------
// Referencias del DOM
// ------------------------------------------------------------
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
const attachToggle = document.getElementById('attach-toggle');
const attachMenu = document.getElementById('attach-menu');
const micToggle = document.getElementById('mic-toggle');
const topbarNavButtons = document.querySelectorAll('[data-view-target]');
const appViews = {
    inicio: document.getElementById('inicio-view'),
    amigos: document.getElementById('amigos-view'),
    chat: document.getElementById('chat-view')
};
const publicationForm = document.getElementById('publication-form');
const publicationText = document.getElementById('publication-text');
const publicationFile = document.getElementById('publication-file');
const feedList = document.getElementById('feed-list');
const refreshFeed = document.getElementById('refresh-feed');
const friendForm = document.getElementById('friend-form');
const friendUsername = document.getElementById('friend-username');
const friendsList = document.getElementById('friends-list');
const refreshFriends = document.getElementById('refresh-friends');
const requestsList = document.getElementById('requests-list');
const refreshRequests = document.getElementById('refresh-requests');

// Inputs ocultos por tipo de adjunto
const fileInputs = {
    imagenes: document.getElementById('file-imagenes'),
    videos: document.getElementById('file-videos'),
    documentos: document.getElementById('file-documentos'),
    audios: document.getElementById('file-audios')
};

// ------------------------------------------------------------
// Estado global de la vista
// ------------------------------------------------------------
const AVATAR_POR_DEFECTO = 'assets/img/default-avatar.png';
let destinatarioSeleccionado = null;
let usuarioChatActual = null;
let autoRefresh = null;
let mediaRecorder = null;
let audioChunks = [];
let isRecordingAudio = false;
let audioStopTimer = null;
let audioAlertaProximidad = false;
const MAX_MEDIA_SECONDS = 300;
const SEGUNDOS_ALERTA_ANTICIPADA = 30;
const MAX_SIZE_BYTES = {
    imagenes: 8 * 1024 * 1024,
    videos: 120 * 1024 * 1024,
    documentos: 12 * 1024 * 1024,
    audios: 40 * 1024 * 1024
};

function hayMediaReproduciendo() {
    if (!messagesWrap) return false;
    const elementos = messagesWrap.querySelectorAll('audio, video');
    return Array.from(elementos).some((media) => !media.paused && !media.ended);
}

// ------------------------------------------------------------
// Utilidades base
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// Navegacion entre secciones
// ------------------------------------------------------------
function activarVista(nombreVista) {
    Object.entries(appViews).forEach(([nombre, vista]) => {
        if (!vista) return;
        vista.classList.toggle('active', nombre === nombreVista);
    });

    topbarNavButtons.forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.viewTarget === nombreVista);
    });

    if (nombreVista === 'chat' && destinatarioSeleccionado) {
        cargarMensajes(true);
    }

    if (nombreVista === 'inicio') {
        cargarPublicaciones();
    }

    if (nombreVista === 'amigos') {
        cargarSolicitudes();
        cargarAmigos();
    }
}

function obtenerUsuarioEnlacePorNombre(nombre) {
    return Array.from(document.querySelectorAll('#users-list .user-item-btn'))
        .find((button) => button.textContent.includes(nombre));
}

// ------------------------------------------------------------
// UI auxiliar (tooltip, menus, modal)
// ------------------------------------------------------------
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
    document.querySelectorAll('.message-menu').forEach((menu) => {
        menu.classList.remove('visible', 'above', 'below');
        menu.style.left = ''; // Limpiar posicion horizontal
    });
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

// ------------------------------------------------------------
// Emojis
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// Adjuntos (parser/formatter)
// ------------------------------------------------------------
function formatAttachmentMessage(tipo, nombre, ruta) {
    return `[adjunto|${tipo}|${encodeURIComponent(nombre)}]${ruta}`;
}

function parseAttachmentMessage(contenidoMensaje) {
    const match = contenidoMensaje.match(/^\[adjunto\|([^|]+)\|([^\]]+)\](.+)$/);
    if (!match) return null;

    let nombre = match[2];
    try {
        nombre = decodeURIComponent(nombre);
    } catch (_) {}

    return { tipo: match[1], nombre, ruta: match[3] };
}

// ------------------------------------------------------------
// Validaciones multimedia (duracion maxima)
// ------------------------------------------------------------
function obtenerDuracionMultimedia(archivo, tipoElemento) {
    return new Promise((resolve, reject) => {
        const url = URL.createObjectURL(archivo);
        const elemento = document.createElement(tipoElemento);
        elemento.preload = 'metadata';
        elemento.src = url;

        const limpiar = () => {
            URL.revokeObjectURL(url);
            elemento.removeAttribute('src');
            elemento.load();
        };

        elemento.onloadedmetadata = () => {
            const duracion = Number(elemento.duration || 0);
            limpiar();
            if (!Number.isFinite(duracion) || duracion <= 0) {
                reject(new Error('No se pudo leer la duracion del archivo'));
                return;
            }
            resolve(duracion);
        };

        elemento.onerror = () => {
            limpiar();
            reject(new Error('No se pudo procesar metadata del archivo'));
        };
    });
}

// ------------------------------------------------------------
// Cabecera de chat
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// Perfil de usuario
// ------------------------------------------------------------
async function cargarPerfil() {
    try {
        const { res, data } = await requestData('api/get_profile.php');
        if (res.ok && data.ok && data.perfil && data.perfil.foto_perfil) {
            profileImage.src = resolverAvatar(data.perfil.foto_perfil, true);
        }
    } catch (_) {
        // No interrumpir chat si falla perfil
    }
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

// ------------------------------------------------------------
// Publicaciones tipo Instagram
// ------------------------------------------------------------
async function cargarPublicaciones() {
    if (!feedList) return;

    try {
        const { res, data } = await requestData('api/get_publications.php');
        if (!res.ok || !data.ok) {
            feedList.innerHTML = '<p class="message">No se pudieron cargar las publicaciones.</p>';
            return;
        }

        feedList.innerHTML = '';
        if (!data.publicaciones.length) {
            feedList.innerHTML = '<p class="message">Todavia no hay publicaciones.</p>';
            return;
        }

        data.publicaciones.forEach((pub) => {
            const item = document.createElement('article');
            item.className = 'publication-item';

            const meta = document.createElement('div');
            meta.className = 'publication-meta';

            const author = document.createElement('span');
            author.className = 'publication-author';
            author.textContent = '@' + pub.nombre_usuario;

            const date = document.createElement('span');
            date.textContent = formatearFechaHora(pub.creado_en);

            meta.appendChild(author);
            meta.appendChild(date);
            item.appendChild(meta);

            if (pub.texto) {
                const text = document.createElement('div');
                text.className = 'publication-text';
                text.textContent = pub.texto;
                item.appendChild(text);
            }

            if (pub.medio_ruta) {
                if (pub.medio_tipo === 'imagen') {
                    const img = document.createElement('img');
                    img.className = 'publication-media';
                    img.src = pub.medio_ruta;
                    img.alt = 'Publicacion de ' + pub.nombre_usuario;
                    item.appendChild(img);
                } else if (pub.medio_tipo === 'video') {
                    const video = document.createElement('video');
                    video.className = 'publication-media';
                    video.src = pub.medio_ruta;
                    video.controls = true;
                    video.preload = 'metadata';
                    item.appendChild(video);
                }
            }

            feedList.appendChild(item);
        });
    } catch (_) {
        feedList.innerHTML = '<p class="message">Error al cargar publicaciones.</p>';
    }
}

async function cargarSolicitudes() {
    if (!requestsList) return;

    try {
        const { res, data } = await requestData('api/get_friend_requests.php');
        if (!res.ok || !data.ok) {
            requestsList.innerHTML = '<p class="message">No se pudieron cargar las solicitudes.</p>';
            return;
        }

        requestsList.innerHTML = '';
        if (!data.solicitudes.length) {
            requestsList.innerHTML = '<p class="message">No tienes solicitudes pendientes.</p>';
            return;
        }

        data.solicitudes.forEach((solicitud) => {
            const item = document.createElement('article');
            item.className = 'friend-item';

            const meta = document.createElement('div');
            meta.className = 'friend-meta';

            const name = document.createElement('span');
            name.className = 'friend-name';
            name.textContent = '@' + solicitud.nombre_usuario;

            const fecha = document.createElement('span');
            fecha.textContent = formatearFechaHora(solicitud.creado_en);

            meta.appendChild(name);
            meta.appendChild(fecha);
            item.appendChild(meta);

            const actions = document.createElement('div');
            actions.className = 'friend-actions';

            const acceptBtn = document.createElement('button');
            acceptBtn.type = 'button';
            acceptBtn.textContent = 'Aceptar';
            acceptBtn.addEventListener('click', async () => {
                try {
                    const formData = new FormData();
                    formData.append('solicitud_id', String(solicitud.id));
                    formData.append('accion', 'aceptar');
                    const { res: resAccept, data: dataAccept } = await requestData('api/respond_friend_request.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (!resAccept.ok || !dataAccept.ok) throw new Error(dataAccept.mensaje || 'No se pudo aceptar');
                    mostrarAlerta('Listo', 'Solicitud aceptada', 'success');
                    await cargarSolicitudes();
                    await cargarAmigos();
                    await cargarUsuarios();
                } catch (error) {
                    mostrarAlerta('Error', error.message, 'error');
                }
            });

            const rejectBtn = document.createElement('button');
            rejectBtn.type = 'button';
            rejectBtn.className = 'message-menu-item delete';
            rejectBtn.textContent = 'Eliminar';
            rejectBtn.addEventListener('click', async () => {
                try {
                    const formData = new FormData();
                    formData.append('solicitud_id', String(solicitud.id));
                    formData.append('accion', 'rechazar');
                    const { res: resReject, data: dataReject } = await requestData('api/respond_friend_request.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (!resReject.ok || !dataReject.ok) throw new Error(dataReject.mensaje || 'No se pudo eliminar');
                    mostrarAlerta('Listo', 'Solicitud eliminada', 'success');
                    await cargarSolicitudes();
                } catch (error) {
                    mostrarAlerta('Error', error.message, 'error');
                }
            });

            actions.appendChild(acceptBtn);
            actions.appendChild(rejectBtn);
            item.appendChild(actions);
            requestsList.appendChild(item);
        });
    } catch (_) {
        requestsList.innerHTML = '<p class="message">Error al cargar solicitudes.</p>';
    }
}

async function crearPublicacion() {
    if (!publicationForm) return;

    const texto = publicationText ? publicationText.value.trim() : '';
    const archivo = publicationFile && publicationFile.files && publicationFile.files[0] ? publicationFile.files[0] : null;

    if (!texto && !archivo) {
        mostrarAlerta('Atencion', 'Escribe texto o adjunta una imagen/video', 'warning');
        return;
    }

    if (archivo) {
        if (archivo.size > MAX_SIZE_BYTES.imagenes && archivo.type.startsWith('image/')) {
            mostrarAlerta('Archivo no permitido', 'La imagen supera el limite permitido', 'warning');
            return;
        }

        if (archivo.size > MAX_SIZE_BYTES.videos && archivo.type.startsWith('video/')) {
            mostrarAlerta('Archivo no permitido', 'El video supera el limite permitido', 'warning');
            return;
        }

        if (archivo.type.startsWith('video/')) {
            try {
                const duracion = await obtenerDuracionMultimedia(archivo, 'video');
                if (duracion > MAX_MEDIA_SECONDS) {
                    mostrarAlerta('Archivo no permitido', 'El video supera 5 minutos', 'warning');
                    return;
                }
            } catch (_) {
                mostrarAlerta('Error', 'No se pudo leer la duracion del video', 'error');
                return;
            }
        }
    }

    const formData = new FormData();
    formData.append('texto', texto);
    if (archivo) {
        formData.append('medio', archivo);
    }

    const { res, data } = await requestData('api/create_publication.php', {
        method: 'POST',
        body: formData
    });

    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo publicar');

    if (publicationText) publicationText.value = '';
    if (publicationFile) publicationFile.value = '';
    await cargarPublicaciones();
}

// ------------------------------------------------------------
// Amigos
// ------------------------------------------------------------
async function cargarAmigos() {
    if (!friendsList) return;

    try {
        const { res, data } = await requestData('api/get_friends.php');
        if (!res.ok || !data.ok) {
            friendsList.innerHTML = '<p class="message">No se pudieron cargar tus amigos.</p>';
            return;
        }

        friendsList.innerHTML = '';
        if (!data.amigos.length) {
            friendsList.innerHTML = '<p class="message">Aun no tienes amigos agregados.</p>';
            return;
        }

        data.amigos.forEach((amigo) => {
            const item = document.createElement('article');
            item.className = 'friend-item';

            const meta = document.createElement('div');
            meta.className = 'friend-meta';

            const name = document.createElement('span');
            name.className = 'friend-name';
            name.textContent = '@' + amigo.nombre_usuario;

            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'theme-toggle';
            btn.textContent = 'Abrir chat';
            btn.addEventListener('click', async () => {
                const target = obtenerUsuarioEnlacePorNombre(amigo.nombre_usuario);
                if (target) {
                    target.click();
                    activarVista('chat');
                }
            });

            meta.appendChild(name);
            meta.appendChild(btn);
            item.appendChild(meta);
            friendsList.appendChild(item);
        });
    } catch (_) {
        friendsList.innerHTML = '<p class="message">Error al cargar amigos.</p>';
    }
}

async function agregarAmigo() {
    const nombre = friendUsername ? friendUsername.value.trim() : '';
    if (!nombre) {
        mostrarAlerta('Atencion', 'Escribe un nombre de usuario', 'warning');
        return;
    }

    const formData = new FormData();
    formData.append('nombre_usuario', nombre);

    const { res, data } = await requestData('api/add_friend.php', {
        method: 'POST',
        body: formData
    });

    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo enviar la solicitud');

    friendUsername.value = '';
    await cargarSolicitudes();
}

// ------------------------------------------------------------
// Mensajeria: envio de texto y adjuntos
// ------------------------------------------------------------
async function enviarTexto(texto) {
    const formData = new FormData();
    formData.append('destinatario_id', String(destinatarioSeleccionado));
    formData.append('contenido', texto);

    const { res, data } = await requestData('api/send_message.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo enviar el mensaje');
}

async function subirAdjunto(tipo, archivo) {
    const formData = new FormData();
    formData.append('tipo', tipo);
    formData.append('archivo', archivo);

    const { res, data } = await requestData('api/upload_attachment.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo subir el archivo');
    return data;
}

async function procesarAdjunto(tipo, archivo) {
    if (!destinatarioSeleccionado) {
        mostrarAlerta('Atencion', 'Primero selecciona una cuenta existente', 'warning');
        return;
    }

    try {
        const info = await subirAdjunto(tipo, archivo);
        const mensaje = formatAttachmentMessage(info.tipo, info.nombre, info.ruta);
        await enviarTexto(mensaje);
        await cargarMensajes(true);
        if (attachMenu) attachMenu.classList.remove('visible');
    } catch (error) {
        mostrarAlerta('Error', error.message, 'error');
    }
}

// ------------------------------------------------------------
// Audio por microfono (grabar y enviar)
// ------------------------------------------------------------
async function toggleGrabacionAudio() {
    if (!destinatarioSeleccionado) {
        mostrarAlerta('Atencion', 'Primero selecciona una cuenta existente', 'warning');
        return;
    }

    if (!navigator.mediaDevices || typeof MediaRecorder === 'undefined') {
        mostrarAlerta('No compatible', 'Tu navegador no soporta grabacion de audio', 'warning');
        return;
    }

    // Si ya esta grabando, detener y enviar
    if (isRecordingAudio && mediaRecorder) {
        mediaRecorder.stop();
        isRecordingAudio = false;
        audioAlertaProximidad = false;
        micToggle.classList.remove('recording');
        micToggle.textContent = String.fromCodePoint(0x1F3A4);
        return;
    }

    // Iniciar nueva grabacion
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        const tiposPreferidos = [
            'audio/webm;codecs=opus',
            'audio/webm',
            'audio/ogg;codecs=opus',
            'audio/ogg',
            'audio/mp4'
        ];
        const mimeElegido = tiposPreferidos.find((tipo) => {
            if (typeof MediaRecorder.isTypeSupported !== 'function') return false;
            return MediaRecorder.isTypeSupported(tipo);
        });
        mediaRecorder = mimeElegido ? new MediaRecorder(stream, { mimeType: mimeElegido }) : new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.addEventListener('dataavailable', (event) => {
            if (event.data && event.data.size > 0) audioChunks.push(event.data);
        });

        mediaRecorder.addEventListener('stop', async () => {
            if (audioStopTimer) {
                clearTimeout(audioStopTimer);
                audioStopTimer = null;
            }

            try {
                const mime = (mediaRecorder.mimeType || 'audio/webm').split(';')[0].trim().toLowerCase();
                const extension = mime.includes('ogg') ? 'ogg' : mime.includes('mp4') ? 'm4a' : mime.includes('wav') ? 'wav' : 'webm';
                const blob = new Blob(audioChunks, { type: mime || 'audio/webm' });
                if (!blob.size) {
                    mostrarAlerta('Error', 'No se pudo capturar audio valido', 'error');
                    return;
                }
                const file = new File([blob], `audio_${Date.now()}.${extension}`, { type: mime || 'audio/webm' });
                await procesarAdjunto('audios', file);
            } catch (_) {
                mostrarAlerta('Error', 'No se pudo procesar el audio grabado', 'error');
            } finally {
                stream.getTracks().forEach((track) => track.stop());
            }
        });

        mediaRecorder.start(1000);
        isRecordingAudio = true;
        audioAlertaProximidad = false;
        micToggle.classList.add('recording');
        micToggle.textContent = 'Stop';

        // Alerta cuando falten 30 segundos para llegar al limite
        setTimeout(() => {
            if (isRecordingAudio && !audioAlertaProximidad && mediaRecorder && mediaRecorder.state === 'recording') {
                audioAlertaProximidad = true;
                mostrarAlerta('Limite alcanzado', 'El audio se detendra en 30 segundos', 'warning');
            }
        }, (MAX_MEDIA_SECONDS - SEGUNDOS_ALERTA_ANTICIPADA) * 1000);

        // Detener al alcanzar el limite maximo
        audioStopTimer = setTimeout(() => {
            if (isRecordingAudio && mediaRecorder && mediaRecorder.state === 'recording') {
                mediaRecorder.stop();
                isRecordingAudio = false;
                audioAlertaProximidad = false;
                micToggle.classList.remove('recording');
                micToggle.textContent = String.fromCodePoint(0x1F3A4);
                mostrarAlerta('Limite alcanzado', 'El audio se detuvo al llegar a 5 minutos', 'info');
            }
        }, MAX_MEDIA_SECONDS * 1000);
    } catch (error) {
        mostrarAlerta('Error', error.message || 'No se pudo iniciar la grabacion de audio', 'error');
    }
}

// ------------------------------------------------------------
// Edicion y eliminacion de mensajes
// ------------------------------------------------------------
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
    } else if (!confirm('Esta accion no se puede deshacer. Eliminar mensaje?')) {
        return;
    }

    const formData = new FormData();
    formData.append('mensaje_id', String(msg.id));

    const { res, data } = await requestData('api/delete_message.php', { method: 'POST', body: formData });
    if (!res.ok || !data.ok) throw new Error(data.mensaje || 'No se pudo eliminar el mensaje');
}

// ------------------------------------------------------------
// Eventos globales de pagina
// ------------------------------------------------------------
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
    if (emojiPanel && !e.target.closest('#emoji-panel') && !e.target.closest('#emoji-toggle')) emojiPanel.classList.remove('visible');
    if (attachMenu && !e.target.closest('#attach-menu') && !e.target.closest('#attach-toggle')) attachMenu.classList.remove('visible');
});

if (avatarModalClose) avatarModalClose.addEventListener('click', cerrarAvatarFlotante);
if (profileImage) profileImage.addEventListener('click', abrirMiAvatarFlotante);

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

if (attachToggle && attachMenu) {
    attachToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const rect = attachToggle.getBoundingClientRect();
        const menuHeight = attachMenu.offsetHeight || 180;
        const topPosition = Math.max(12, rect.top - menuHeight - 8);
        attachMenu.style.left = `${Math.max(12, rect.left)}px`;
        attachMenu.style.top = `${topPosition}px`;
        attachMenu.classList.toggle('visible');
    });
}

// Asociar clicks del menu de adjuntos
Array.from(document.querySelectorAll('.attach-option')).forEach((btn) => {
    btn.addEventListener('click', () => {
        const tipo = btn.dataset.attachType;
        const input = fileInputs[tipo];
        if (input) input.click();
    });
});

// Asociar seleccion de archivos por categoria
Object.keys(fileInputs).forEach((tipo) => {
    const input = fileInputs[tipo];
    if (!input) return;

    input.addEventListener('change', async () => {
        const archivo = input.files && input.files[0] ? input.files[0] : null;
        if (!archivo) return;

        if (archivo.size > MAX_SIZE_BYTES[tipo]) {
            const mb = Math.round(MAX_SIZE_BYTES[tipo] / (1024 * 1024));
            mostrarAlerta('Archivo no permitido', `El archivo excede ${mb}MB para ${tipo}`, 'warning');
            input.value = '';
            return;
        }

        if (tipo === 'videos' || tipo === 'audios') {
            try {
                const duracion = await obtenerDuracionMultimedia(archivo, tipo === 'videos' ? 'video' : 'audio');
                if (duracion > MAX_MEDIA_SECONDS) {
                    mostrarAlerta('Archivo no permitido', `El ${tipo === 'videos' ? 'video' : 'audio'} supera 5 minutos`, 'warning');
                    input.value = '';
                    return;
                }
            } catch (_) {
                mostrarAlerta('Error', 'No se pudo leer la duracion del archivo multimedia', 'error');
                input.value = '';
                return;
            }
        }

        await procesarAdjunto(tipo, archivo);
        input.value = '';
    });
});

if (micToggle) {
    micToggle.addEventListener('click', toggleGrabacionAudio);
}

topbarNavButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        activarVista(btn.dataset.viewTarget);
    });
});

if (publicationForm) {
    publicationForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await crearPublicacion();
            mostrarAlerta('Listo', 'Tu publicacion fue creada', 'success');
        } catch (error) {
            mostrarAlerta('Error', error.message, 'error');
        }
    });
}

if (refreshFeed) refreshFeed.addEventListener('click', cargarPublicaciones);

if (friendForm) {
    friendForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await agregarAmigo();
            mostrarAlerta('Listo', 'Amigo agregado correctamente', 'success');
        } catch (error) {
            mostrarAlerta('Error', error.message, 'error');
        }
    });
}

if (refreshFriends) refreshFriends.addEventListener('click', cargarAmigos);
if (refreshRequests) refreshRequests.addEventListener('click', cargarSolicitudes);

// ------------------------------------------------------------
// Cargar usuarios para sidebar
// ------------------------------------------------------------
async function cargarUsuarios() {
    try {
        const { res, data } = await requestData('api/get_friends.php');
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error', data.mensaje || 'No se pudieron cargar amigos', 'error');
            return;
        }

        usersList.innerHTML = '';
        if (data.amigos.length === 0) {
            usersList.innerHTML = '<li>No hay amigos agregados.</li>';
            return;
        }

        data.amigos.forEach((user) => {
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
    } catch (_) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
}

// ------------------------------------------------------------
// Seleccion de usuario destino
// ------------------------------------------------------------
async function seleccionarUsuario(user, buttonRef) {
    destinatarioSeleccionado = Number(user.id);
    usuarioChatActual = user;
    renderHeaderChat(user);
    chatMessage.textContent = '';

    document.querySelectorAll('#users-list button').forEach((btn) => btn.classList.remove('active'));
    buttonRef.classList.add('active');

    await cargarMensajes(true);

    if (autoRefresh) clearInterval(autoRefresh);
    autoRefresh = setInterval(() => cargarMensajes(false), 3000);
}

// ------------------------------------------------------------
// Renderizado de mensajes del chat
// ------------------------------------------------------------
async function cargarMensajes(force = false) {
    if (!destinatarioSeleccionado) return;
    if (!force && hayMediaReproduciendo()) return;

    try {
        const { res, data } = await requestData(`api/get_messages.php?destinatario_id=${destinatarioSeleccionado}`);
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error', data.mensaje || 'No se pudo cargar el chat', 'error');
            return;
        }

        const wasAtBottom = messagesWrap.scrollHeight - messagesWrap.scrollTop - messagesWrap.clientHeight < 50;
        const previousScrollTop = messagesWrap.scrollTop;
        messagesWrap.innerHTML = '';

        data.mensajes.forEach((msg) => {
            const esMio = Number(msg.remitente_id) === Number(window.AUTH_USER);
            const fueVisto = Number(msg.visto) === 1;
            const row = document.createElement('div');
            row.className = 'message-row ' + (esMio ? 'me' : 'other');

            const bubble = document.createElement('div');
            bubble.className = 'bubble ' + (esMio ? 'me' : 'other');

            const text = document.createElement('div');
            text.className = 'bubble-text';

            // Si es adjunto, mostrar visual segun tipo
            const adjunto = parseAttachmentMessage(msg.contenido);
            if (adjunto) {
                if (adjunto.tipo === 'imagenes') {
                    const img = document.createElement('img');
                    img.className = 'attachment-image';
                    img.src = adjunto.ruta;
                    img.alt = adjunto.nombre;
                    img.loading = 'lazy';
                    img.addEventListener('click', (e) => e.stopPropagation());
                    text.appendChild(img);
                } else if (adjunto.tipo === 'videos') {
                    const video = document.createElement('video');
                    video.className = 'attachment-video';
                    video.src = adjunto.ruta;
                    video.controls = true;
                    video.preload = 'metadata';
                    video.addEventListener('click', (e) => e.stopPropagation());
                    text.appendChild(video);
                } else if (adjunto.tipo === 'audios') {
                    const audio = document.createElement('audio');
                    audio.className = 'attachment-audio';
                    audio.src = adjunto.ruta;
                    audio.controls = true;
                    audio.preload = 'metadata';
                    audio.addEventListener('click', (e) => e.stopPropagation());
                    text.appendChild(audio);
                } else {
                    const link = document.createElement('a');
                    link.className = 'attachment-link';
                    link.href = adjunto.ruta;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.textContent = `[${adjunto.tipo}] ${adjunto.nombre}`;
                    link.addEventListener('click', (e) => e.stopPropagation());
                    text.appendChild(link);
                }
            } else {
                text.textContent = msg.contenido;
            }

            bubble.appendChild(text);

            // Check de visto solo para mensajes propios ya vistos
            if (esMio && fueVisto) {
                const estado = document.createElement('div');
                estado.className = 'message-status seen';
                estado.textContent = '\u2713\u2713';
                estado.title = msg.visto_en ? `Visto: ${formatearFechaHora(msg.visto_en)}` : 'Visto';
                bubble.appendChild(estado);
            }

            // Click en mensaje para ver hora flotante
            bubble.addEventListener('click', (e) => {
                e.stopPropagation();
                mostrarHoraFlotante(bubble, msg.enviado_en);
            });

            row.appendChild(bubble);

            // Menu de opciones:
            // - Propio no visto: solo eliminar
            // - Recibido con adjunto: descargar
            if ((esMio && !fueVisto) || (!esMio && adjunto)) {
                const menuWrap = document.createElement('div');
                menuWrap.className = 'message-menu-wrap';

                const trigger = document.createElement('button');
                trigger.type = 'button';
                trigger.className = 'message-menu-trigger';
                trigger.textContent = '\u22ef';
                trigger.title = 'Opciones';

                const menu = document.createElement('div');
                menu.className = 'message-menu';

                if (esMio && !fueVisto) {
                    // Editar solo para mensajes de texto normal (sin adjunto)
                    if (!adjunto) {
                        const editBtn = document.createElement('button');
                        editBtn.type = 'button';
                        editBtn.className = 'message-menu-item';
                        editBtn.textContent = 'Editar';
                        editBtn.addEventListener('click', async (e) => {
                            e.stopPropagation();
                            try {
                                await editarMensaje(msg);
                                await cargarMensajes(true);
                            } catch (error) {
                                mostrarAlerta('Error', error.message, 'error');
                            }
                        });
                        menu.appendChild(editBtn);
                    }

                    const deleteBtn = document.createElement('button');
                    deleteBtn.type = 'button';
                    deleteBtn.className = 'message-menu-item delete';
                    deleteBtn.textContent = 'Eliminar';
                    deleteBtn.addEventListener('click', async (e) => {
                        e.stopPropagation();
                        try {
                            await eliminarMensaje(msg);
                            await cargarMensajes(true);
                        } catch (error) {
                            mostrarAlerta('Error', error.message, 'error');
                        }
                    });
                    menu.appendChild(deleteBtn);
                }

                if (!esMio && adjunto) {
                    const downloadBtn = document.createElement('button');
                    downloadBtn.type = 'button';
                    downloadBtn.className = 'message-menu-item download';
                    downloadBtn.textContent = 'Descargar';
                    downloadBtn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const a = document.createElement('a');
                        a.href = adjunto.ruta;
                        a.download = adjunto.nombre || 'archivo';
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        cerrarMenusMensaje();
                    });
                    menu.appendChild(downloadBtn);
                }

                trigger.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const visible = menu.classList.contains('visible');
                    cerrarMenusMensaje();
                    if (!visible) {
                        // Calcular posicionamiento dinamico del menu
                        const triggerRect = trigger.getBoundingClientRect();
                        const messagesRect = messagesWrap.getBoundingClientRect();

                        // Calcular altura aproximada del menu basada en elementos
                        const menuItems = menu.querySelectorAll('.message-menu-item');
                        const menuHeight = (menuItems.length * 32) + 12 + 12; // items * altura + padding top/bottom

                        // Espacio disponible arriba y abajo del trigger
                        const spaceAbove = triggerRect.top - messagesRect.top;
                        const spaceBelow = messagesRect.bottom - triggerRect.bottom;

                        // Determinar si mostrar arriba o abajo
                        let showAbove = false;
                        if (spaceBelow >= menuHeight + 10) {
                            // Hay espacio suficiente abajo
                            showAbove = false;
                        } else if (spaceAbove >= menuHeight + 10) {
                            // Hay espacio suficiente arriba
                            showAbove = true;
                        } else {
                            // No hay espacio suficiente en ninguno, preferir abajo si es posible
                            showAbove = spaceAbove < spaceBelow;
                        }

                        // Aplicar clase de posicionamiento
                        menu.classList.remove('above', 'below');
                        menu.classList.add(showAbove ? 'above' : 'below');

                        // Ajustar posicion horizontal usando el anclaje por defecto del CSS
                        menu.style.left = '';
                        menu.style.right = '0px';
                        menu.classList.add('visible');
                    }
                });

                menuWrap.appendChild(trigger);
                menuWrap.appendChild(menu);
                row.appendChild(menuWrap);
            }

            messagesWrap.appendChild(row);
        });

        // Mantener posicion si el usuario no estaba abajo; mover abajo solo si antes ya estaba en el final
        if (!hayMediaReproduciendo()) {
            if (wasAtBottom) {
                messagesWrap.scrollTop = messagesWrap.scrollHeight;
            } else {
                messagesWrap.scrollTop = previousScrollTop;
            }
        }
    } catch (_) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
}

// ------------------------------------------------------------
// Envio del formulario principal de texto
// ------------------------------------------------------------
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

    try {
        await enviarTexto(texto);
        contenido.value = '';
        chatMessage.textContent = '';
        if (emojiPanel) emojiPanel.classList.remove('visible');
        await cargarMensajes(true);
    } catch (error) {
        mostrarAlerta('Error', error.message, 'error');
    }
});

// ------------------------------------------------------------
// Cambio de foto de perfil
// ------------------------------------------------------------
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

// ------------------------------------------------------------
// Inicio de la app del chat
// ------------------------------------------------------------
activarVista('inicio');
cargarPerfil();
cargarUsuarios();
cargarPublicaciones();
cargarAmigos();
cargarSolicitudes();
