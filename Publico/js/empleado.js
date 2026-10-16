const rutabase = 'index.php?modulo=empleado';
const clavebase = 'floristica.borrador.empleado.';
const camposformulario = ['tbempleadonombre', 'tbempleadotelefono', 'tbempleadorol', 'tbempleadousuario'];

const cuerpotabla = document.getElementById('cuerpotabla');
const busqueda = document.getElementById('busqueda');
const filtroestado = document.getElementById('filtroestado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const etiquetaclave = document.getElementById('etiquetaclave');
const notaclave = document.getElementById('notaclave');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');

let temporizadorbusqueda;
let temporizadoraviso;
let datosoriginales = {};

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formateartelefono(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '').slice(0, 8);
    return digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
}

function normalizar(campo, valor) {
    return campo === 'tbempleadotelefono' ? formateartelefono(valor) : String(valor ?? '');
}

async function peticion(accion, opciones = {}, parametros = {}) {
    const url = `${rutabase}&accion=${accion}&${new URLSearchParams(parametros)}`;
    try {
        const respuesta = await fetch(url, opciones);
        return await respuesta.json();
    } catch (error) {
        return { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
    }
}

function consultar(accion, parametros) {
    return peticion(accion, {}, parametros);
}

function enviar(accion, datos) {
    return peticion(accion, { method: 'POST', body: datos });
}

function mostraraviso(mensaje, tipo) {
    aviso.textContent = mensaje;
    aviso.className = `aviso aviso-${tipo}`;
    aviso.hidden = false;
    clearTimeout(temporizadoraviso);
    temporizadoraviso = setTimeout(() => {
        aviso.hidden = true;
    }, 5000);
}

function confirmar({ titulo, mensaje, textoaceptar, peligro = false }) {
    return new Promise((resolver) => {
        tituloconfirmacion.textContent = titulo;
        mensajeconfirmacion.textContent = mensaje;
        botonaceptarconfirmacion.textContent = textoaceptar;
        iconoconfirmacion.textContent = peligro ? '!' : '✓';
        dialogoconfirmacion.classList.toggle('peligro', peligro);
        botonaceptarconfirmacion.className = peligro ? 'boton boton-peligro' : 'boton boton-principal';

        const terminar = (respuesta) => {
            dialogoconfirmacion.close();
            resolver(respuesta);
        };

        botonaceptarconfirmacion.onclick = () => terminar(true);
        botoncancelarconfirmacion.onclick = () => terminar(false);
        dialogoconfirmacion.oncancel = (evento) => {
            evento.preventDefault();
            terminar(false);
        };

        dialogoconfirmacion.showModal();
        botoncancelarconfirmacion.focus();
    });
}

/* ---------- Borrador ---------- */

function claveborrador() {
    const id = formulario.elements.tbempleadoid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function guardarborrador() {
    const datos = {};
    camposformulario.forEach((campo) => {
        datos[campo] = formulario.elements[campo].value;
    });
    try {
        localStorage.setItem(claveborrador(), JSON.stringify(datos));
    } catch (error) {
        return;
    }
}

function leerborrador() {
    try {
        const texto = localStorage.getItem(claveborrador());
        return texto ? JSON.parse(texto) : null;
    } catch (error) {
        return null;
    }
}

function borrarborrador(clave = claveborrador()) {
    try {
        localStorage.removeItem(clave);
    } catch (error) {
        return;
    }
}

function asignarcampos(datos) {
    camposformulario.forEach((campo) => {
        formulario.elements[campo].value = normalizar(campo, datos[campo]);
    });
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbempleadoid.value = datos.tbempleadoid ?? '';
    asignarcampos(datos);

    // Al editar a alguien que ya tiene cuenta, la contraseña es opcional
    const tienecuenta = Boolean(datos.tienecuenta);
    etiquetaclave.textContent = tienecuenta ? 'Nueva contraseña' : 'Contraseña *';
    notaclave.textContent = tienecuenta
        ? 'Déjela vacía para conservar la actual.'
        : 'Mínimo 8 caracteres, con al menos una letra y un número.';
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    const haycambios = camposformulario.some(
        (campo) => normalizar(campo, borrador[campo]) !== normalizar(campo, datosoriginales[campo])
    );

    if (!haycambios) {
        borrarborrador();
        return;
    }

    asignarcampos(borrador);
    avisoborrador.hidden = false;
}

function descartarborrador() {
    borrarborrador();
    llenarformulario(datosoriginales);
    limpiarerrores();
    avisoborrador.hidden = true;
}

/* ---------- Tabla ---------- */

async function cargarempleados() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay empleados registrados. Empiece registrando al propietario con "Nuevo empleado".'
            : 'No hay empleados que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((empleado) => {
        const activo = empleado.tbempleadoestado === 1;
        const id = empleado.tbempleadoid;
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Reactivar</button>`;
        const rol = empleado.tbempleadorol === 'Propietario'
            ? '<span class="estado estado-propietario">Propietario</span>'
            : '<span class="estado estado-empleado">Empleado</span>';

        return `
            <tr class="${activo ? '' : 'fila-inactiva'}">
                <td>${escapar(empleado.tbempleadonombre)}</td>
                <td>${escapar(formateartelefono(empleado.tbempleadotelefono))}</td>
                <td>${rol}</td>
                <td>${empleado.tienecuenta ? escapar(empleado.tbempleadousuario) : '<span class="texto-peligro">Sin acceso</span>'}</td>
                <td>${empleado.encargosabiertos}</td>
                <td><span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="columna-acciones">
                    <button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>
                    <button type="button" class="enlace" data-accion="historial" data-id="${id}">Historial</button>
                    ${botonestado}
                </td>
            </tr>`;
    }).join('');
}

/* ---------- Formulario ---------- */

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

function abrirnuevo() {
    datosoriginales = {};
    llenarformulario(datosoriginales);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Nuevo empleado';
    botonguardar.textContent = 'Guardar empleado';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosoriginales = respuesta.datos;
    llenarformulario(datosoriginales);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Editar empleado';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

async function cambiarestado(accion, id, nombre) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Inactivar empleado?',
            mensaje: `${nombre} ya no aparecerá para asignarle solicitudes. Podrá reactivarlo cuando lo necesite.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        }
        : {
            titulo: '¿Reactivar empleado?',
            mensaje: `${nombre} volverá a estar disponible para asignarle solicitudes.`,
            textoaceptar: 'Reactivar',
        };

    if (!(await confirmar(configuracion))) {
        return;
    }

    const datos = new FormData();
    datos.append('id', id);

    const respuesta = await enviar(accion, datos);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        cargarempleados();
    }
}

/* ---------- Eventos ---------- */

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbempleadotelefono') {
        campo.value = formateartelefono(campo.value);
    }
    if (campo.name === 'tbempleadonombre') {
        campo.value = campo.value.replace(/[^\p{L}\s'.-]/gu, '');
    }
    if (campo.name === 'tbempleadousuario') {
        campo.value = campo.value.toLowerCase().replace(/[^a-z0-9._]/g, '');
    }

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');

    if (campo.name !== 'tbempleadoclave') {
        guardarborrador();
    }
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbempleadoid.value !== '';

    if (esedicion) {
        const nombre = formulario.elements.tbempleadonombre.value.trim() || 'este empleado';
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizarán los datos de ${nombre}.`,
            textoaceptar: 'Guardar cambios',
        });

        if (!aceptado) {
            return;
        }
    }

    const clave = claveborrador();

    limpiarerrores();
    botonguardar.disabled = true;

    const respuesta = await enviar(esedicion ? 'actualizar' : 'crear', new FormData(formulario));

    botonguardar.disabled = false;

    if (respuesta.exito) {
        borrarborrador(clave);
        avisoborrador.hidden = true;
        dialogoformulario.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarempleados();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});

cuerpotabla.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) {
        return;
    }

    const id = boton.dataset.id;
    const nombre = boton.closest('tr').cells[0].textContent;

    switch (boton.dataset.accion) {
        case 'editar':
            abrireditar(id);
            break;
        case 'historial':
            ventanahistorial.mostrar('empleado', id, nombre);
            break;
        case 'inactivar':
        case 'reactivar':
            cambiarestado(boton.dataset.accion, id, nombre);
            break;
    }
});

busqueda.addEventListener('input', () => {
    clearTimeout(temporizadorbusqueda);
    temporizadorbusqueda = setTimeout(cargarempleados, 300);
});

filtroestado.addEventListener('change', cargarempleados);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

cargarempleados();


// Si se entró desde "Agregar nuevo ..." del menú, abre el formulario
abrirformulariosipide(abrirnuevo);