const formulario = document.getElementById('formulario');
const avisoformulario = document.getElementById('avisoformulario');
const botonguardar = document.getElementById('botonguardar');

function formateartelefono(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '').slice(0, 8);
    return digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
}

function limpiarerrores() {
    formulario.querySelectorAll('[data-error]').forEach((error) => {
        error.textContent = '';
    });
    formulario.querySelectorAll('[aria-invalid]').forEach((campo) => {
        campo.removeAttribute('aria-invalid');
    });
    avisoformulario.hidden = true;
}

function mostrarerrores(errores) {
    Object.entries(errores).forEach(([campo, mensaje]) => {
        const error = formulario.querySelector(`[data-error="${campo}"]`);
        if (error) {
            error.textContent = mensaje;
        }
        if (formulario.elements[campo]) {
            formulario.elements[campo].setAttribute('aria-invalid', 'true');
        }
    });

    const primero = formulario.querySelector('[aria-invalid="true"]');
    if (primero) {
        primero.focus();
    }
}

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbempleadotelefono') {
        campo.value = formateartelefono(campo.value);
    }
    if (campo.name === 'tbempleadonombre') {
        campo.value = campo.value.replace(/[^\p{L}\s'.-]/gu, '');
    }
    if (campo.name === 'tbempleadousuario' || campo.name === 'usuario') {
        campo.value = campo.value.toLowerCase().replace(/[^a-z0-9._]/g, '');
    }

    // Las contraseñas de la cuenta nueva se revisan aparte, mientras se escriben
    if (campo.name === 'tbempleadoclave' || campo.name === 'tbempleadoclaveconfirmar') {
        return;
    }

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
});

/* ---------- Revisión de la contraseña mientras se escribe (solo al crear la cuenta) ---------- */

const campoclave = formulario.elements.tbempleadoclave;
const campoconfirmar = formulario.elements.tbempleadoclaveconfirmar;
const mensajecoincide = formulario.querySelector('[data-correcto="tbempleadoclaveconfirmar"]');

function errorclave(clave) {
    if (clave === '') {
        return 'La contraseña es obligatoria.';
    }
    if (clave.length < 8 || clave.length > 72) {
        return 'La contraseña debe tener entre 8 y 72 caracteres.';
    }
    if (!/\p{L}/u.test(clave) || !/\d/.test(clave)) {
        return 'La contraseña debe tener al menos una letra y un número.';
    }
    return '';
}

function ponererror(campo, mensaje) {
    formulario.querySelector(`[data-error="${campo.name}"]`).textContent = mensaje;
    if (mensaje) {
        campo.setAttribute('aria-invalid', 'true');
    } else {
        campo.removeAttribute('aria-invalid');
    }
}

// Compara las dos contraseñas. Mientras la segunda va siendo igual al inicio
// de la primera no se marca error, para no regañar a medio escribir.
function revisarcoincidencia(final = false) {
    const clave = campoclave.value;
    const confirmar = campoconfirmar.value;
    mensajecoincide.textContent = '';

    if (confirmar === '') {
        ponererror(campoconfirmar, final ? 'Repita la contraseña.' : '');
        return final ? false : true;
    }

    if (confirmar === clave) {
        ponererror(campoconfirmar, '');
        mensajecoincide.textContent = 'Las contraseñas coinciden.';
        return true;
    }

    const escribiendo = !final && clave.startsWith(confirmar);
    ponererror(campoconfirmar, escribiendo ? '' : 'Las contraseñas no coinciden.');
    return false;
}

if (campoclave && campoconfirmar) {
    campoclave.addEventListener('input', () => {
        // Si ya escribió algo y lo corrige, el aviso desaparece en cuanto cumple
        if (formulario.querySelector('[data-error="tbempleadoclave"]').textContent !== '') {
            ponererror(campoclave, errorclave(campoclave.value));
        }
        if (campoconfirmar.value !== '') {
            revisarcoincidencia();
        }
    });

    campoclave.addEventListener('blur', () => {
        if (campoclave.value !== '') {
            ponererror(campoclave, errorclave(campoclave.value));
        }
    });

    campoconfirmar.addEventListener('input', () => revisarcoincidencia());
    campoconfirmar.addEventListener('blur', () => {
        if (campoconfirmar.value !== '') {
            revisarcoincidencia(true);
        }
    });
}

// Revisa la contraseña antes de enviar; devuelve false si hay que corregir algo
function revisarantesdeenviar() {
    if (!campoclave || !campoconfirmar) {
        return true;
    }

    const mensaje = errorclave(campoclave.value);
    ponererror(campoclave, mensaje);
    const coinciden = revisarcoincidencia(true);

    if (mensaje || !coinciden) {
        (mensaje ? campoclave : campoconfirmar).focus();
        return false;
    }

    return true;
}

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    limpiarerrores();

    if (!revisarantesdeenviar()) {
        return;
    }

    botonguardar.disabled = true;

    let respuesta;
    try {
        const resultado = await fetch(`index.php?modulo=sesion&accion=${formulario.dataset.accion}`, {
            method: 'POST',
            body: new FormData(formulario),
        });
        respuesta = await resultado.json();
    } catch (error) {
        respuesta = { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
    }

    botonguardar.disabled = false;

    if (respuesta.exito) {
        window.location.href = 'index.php';
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    // Por seguridad se borra la contraseña escrita cuando falla el ingreso
    if (formulario.elements.clave) {
        formulario.elements.clave.value = '';
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});