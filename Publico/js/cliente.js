const rutabase = 'index.php?modulo=cliente';
const clavebase = 'floristica.borrador.cliente.';
const camposformulario = ['tbclientenombre', 'tbclientetelefono', 'tbclientecorreo', 'tbclientedireccion'];

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

// "88887777" -> "8888-7777"
function formateartelefono(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '').slice(0, 8);
    return digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
}

// Enlace para escribirle por WhatsApp (Costa Rica: 506)
function enlacewhatsapp(telefono) {
    const digitos = String(telefono ?? '').replace(/\D/g, '');
    return `https://wa.me/506${digitos}`;
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
    const id = formulario.elements.tbclienteid.value;
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
        formulario.elements[campo].value = datos[campo] ?? '';
    });
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbclienteid.value = datos.tbclienteid ?? '';
    asignarcampos(datos);
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    const haycambios = camposformulario.some(
        (campo) => String(borrador[campo] ?? '') !== String(datosoriginales[campo] ?? '')
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

async function cargarclientes() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="6" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay clientes registrados. Use "Nuevo cliente" para agregar el primero.'
            : 'No hay clientes que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="6" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((cliente) => {
        const activo = cliente.tbclienteestado === 1;
        const id = cliente.tbclienteid;
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Reactivar</button>`;
        const solicitudes = cliente.solicitudesabiertas > 0
            ? `${cliente.solicitudes} <span class="texto-suave">(${cliente.solicitudesabiertas} ${cliente.solicitudesabiertas === 1 ? 'abierta' : 'abiertas'})</span>`
            : String(cliente.solicitudes);

        return `
            <tr class="${activo ? '' : 'fila-inactiva'}">
                <td>${escapar(cliente.tbclientenombre)}</td>
                <td><a class="enlace-telefono" href="${enlacewhatsapp(cliente.tbclientetelefono)}" target="_blank" rel="noopener" title="Escribirle por WhatsApp">${escapar(cliente.tbclientetelefono)}</a></td>
                <td>${cliente.tbclientedireccion ? escapar(cliente.tbclientedireccion) : '<span class="texto-suave">Sin dirección</span>'}</td>
                <td>${solicitudes}</td>
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
    tituloformulario.textContent = 'Nuevo cliente';
    botonguardar.textContent = 'Guardar cliente';
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
    tituloformulario.textContent = 'Editar cliente';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

async function cambiarestado(accion, id, nombre) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Inactivar cliente?',
            mensaje: `${nombre} ya no aparecerá para registrarle solicitudes de encargo. Podrá reactivarlo cuando lo necesite.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        }
        : {
            titulo: '¿Reactivar cliente?',
            mensaje: `${nombre} volverá a aparecer para registrarle solicitudes de encargo.`,
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
        cargarclientes();
    }
}

/* ---------- Eventos ---------- */

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbclientetelefono') {
        campo.value = formateartelefono(campo.value);
    }
    if (campo.name === 'tbclientenombre') {
        campo.value = campo.value.replace(/[^\p{L}\s'.-]/gu, '');
    }

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
    guardarborrador();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbclienteid.value !== '';

    if (esedicion) {
        const nombre = formulario.elements.tbclientenombre.value.trim() || 'este cliente';
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
        cargarclientes();
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
            ventanahistorial.mostrar('cliente', id, nombre);
            break;
        case 'inactivar':
        case 'reactivar':
            cambiarestado(boton.dataset.accion, id, nombre);
            break;
    }
});

busqueda.addEventListener('input', () => {
    clearTimeout(temporizadorbusqueda);
    temporizadorbusqueda = setTimeout(cargarclientes, 300);
});

filtroestado.addEventListener('change', cargarclientes);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

filtrosdesdeurl({ estado: filtroestado });
cargarclientes();

// Si se entró desde "Agregar nuevo cliente" del menú, abre el formulario
abrirformulariosipide(abrirnuevo);
