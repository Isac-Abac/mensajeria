const tabs = document.querySelectorAll('.tab-button');
const formLogin = document.getElementById('form-login');
const formRegister = document.getElementById('form-register');
const messageEl = document.getElementById('auth-message');

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

function limpiarFormularios() {
    formLogin.reset();
    formRegister.reset();
    document.querySelectorAll('#form-login input, #form-register input').forEach((input) => {
        input.value = '';
    });
    if (messageEl) messageEl.textContent = '';
}

window.addEventListener('load', limpiarFormularios);
window.addEventListener('pageshow', limpiarFormularios);

tabs.forEach((btn) => {
    btn.addEventListener('click', () => {
        tabs.forEach((b) => b.classList.remove('active'));
        btn.classList.add('active');
        const tab = btn.dataset.tab;
        formLogin.classList.toggle('active', tab === 'login');
        formRegister.classList.toggle('active', tab === 'register');
        messageEl.textContent = '';
    });
});

formLogin.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(formLogin);

    try {
        const { res, data } = await requestData('api/login.php', { method: 'POST', body: formData });
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error de inicio de sesion', data.mensaje || 'No se pudo iniciar sesion', 'error');
            return;
        }
        window.location.href = 'app.php';
    } catch (error) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
});

formRegister.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(formRegister);

    try {
        const { res, data } = await requestData('api/register.php', { method: 'POST', body: formData });
        if (!res.ok || !data.ok) {
            mostrarAlerta('Error de registro', data.mensaje || 'No se pudo registrar la cuenta', 'error');
            return;
        }
        window.location.href = 'app.php';
    } catch (error) {
        mostrarAlerta('Error de conexion', 'No se pudo conectar con el servidor', 'error');
    }
});
