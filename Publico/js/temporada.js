const rutabase = 'index.php?modulo=temporada';
const clavebase = 'floristica.borrador.temporada.';
const camposformulario = ['tbtemporadanombre', 'tbtemporadadescripcion'];

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
const textovigente = document.getElementById('textovigente');
const notavigente = document.getElementById('notavigente');
const botondesactivar = document.getElementById('botondesactivar');
const bannertemporada = document.getElementById('bannertemporada');

let temporizadorbusqueda;
let temporizadoraviso;
let datosoriginales = {};

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function plural(cantidad, singular, varios) {
    return `${cantidad} ${cantidad === 1 ? singular : varios}`;
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
    const id = formulario.elements.tbtemporadaid.value;
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
    formulario.elements.tbtemporadaid.value = datos.tbtemporadaid ?? '';
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

// Arriba de la tabla: qué precios se están usando ahora
function mostrarvigente(temporadas) {
    const vigente = temporadas.find((temporada) => temporada.tbtemporadavigente === 1);

    bannertemporada.classList.toggle('banner-vigente', Boolean(vigente));
    botondesactivar.hidden = !vigente;
    if (vigente) {
        textovigente.textContent = `Se están usando los precios de ${vigente.tbtemporadanombre}`;
        notavigente.textContent = 'Los arreglos se calculan solos con lo que cuestan sus flores. Las flores sin precio del proveedor para esta temporada se cobran al precio normal.';
    } else {
        textovigente.textContent = 'Se están usando los precios normales (temporada baja)';
        notavigente.textContent = 'Use "Usar sus precios" en una temporada para empezar a cobrar con sus precios.';
    }
}

async function cargartemporadas() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="5" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    // El aviso de arriba se calcula con todas las temporadas, no solo con las filtradas
    const todas = busqueda.value.trim() === '' && filtroestado.value === '' ? respuesta : await consultar('listar');
    mostrarvigente(todas.exito ? todas.datos : []);

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay temporadas. Use "Nueva temporada" para agregar la primera.'
            : 'No hay temporadas que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="5" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((temporada) => {
        const activa = temporada.tbtemporadaestado === 1;
        const vigente = temporada.tbtemporadavigente === 1;
        const id = temporada.tbtemporadaid;
        const botonestado = activa
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Reactivar</button>`;
        const botonprecios = vigente
            ? '<button type="button" class="enlace" data-accion="desactivar">Volver a precios normales</button>'
            : activa ? `<button type="button" class="enlace" data-accion="activar" data-id="${id}">Usar sus precios</button>` : '';
        const estado = vigente
            ? '<span class="estado estado-vigente">En uso</span>'
            : `<span class="estado ${activa ? 'estado-activo' : 'estado-inactivo'}">${activa ? 'Activa' : 'Inactiva'}</span>`;

        return `
            <tr class="${activa ? '' : 'fila-inactiva'}">
                <td>${escapar(temporada.tbtemporadanombre)}</td>
                <td>${temporada.tbtemporadadescripcion ? escapar(temporada.tbtemporadadescripcion) : '<span class="texto-suave">Sin descripción</span>'}</td>
                <td>${plural(temporada.productosconprecio, 'producto', 'productos')}<br><small class="texto-suave">Los arreglos se calculan solos</small></td>
                <td>${estado}</td>
                <td class="columna-acciones">
                    ${botonprecios}
                    <button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>
                    <button type="button" class="enlace" data-accion="historial" data-id="${id}">Historial</button>
                    ${vigente ? '' : botonestado}
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
    tituloformulario.textContent = 'Nueva temporada';
    botonguardar.textContent = 'Guardar temporada';
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
    tituloformulario.textContent = 'Editar temporada';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

async function cambiarestado(accion, id, nombre) {
    const configuraciones = {
        inactivar: {
            titulo: '¿Inactivar temporada?',
            mensaje: `${nombre} ya no aparecerá al poner precios. Sus precios se conservan por si la reactiva.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        },
        reactivar: {
            titulo: '¿Reactivar temporada?',
            mensaje: `${nombre} volverá a aparecer para ponerle precios y usarlos.`,
            textoaceptar: 'Reactivar',
        },
        activar: {
            titulo: `¿Usar los precios de ${nombre}?`,
            mensaje: 'Desde ahora el catálogo y las solicitudes nuevas se cobran con los precios de esta temporada, hasta que vuelva a los precios normales. Las solicitudes ya registradas no cambian.',
            textoaceptar: 'Usar sus precios',
        },
        desactivar: {
            titulo: '¿Volver a los precios normales?',
            mensaje: 'Desde ahora se cobra con los precios normales (temporada baja). Las solicitudes ya registradas no cambian.',
            textoaceptar: 'Volver a precios normales',
        },
    };

    if (!(await confirmar(configuraciones[accion]))) {
        return;
    }

    const datos = new FormData();
    datos.append('id', id ?? '');

    const respuesta = await enviar(accion, datos);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        cargartemporadas();
    }
}

/* ---------- Eventos ---------- */

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
    guardarborrador();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbtemporadaid.value !== '';

    if (esedicion) {
        const nombre = formulario.elements.tbtemporadanombre.value.trim() || 'esta temporada';
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
        cargartemporadas();
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
            ventanahistorial.mostrar('temporada', id, nombre);
            break;
        case 'inactivar':
        case 'reactivar':
        case 'activar':
        case 'desactivar':
            cambiarestado(boton.dataset.accion, id, nombre);
            break;
    }
});

busqueda.addEventListener('input', () => {
    clearTimeout(temporizadorbusqueda);
    temporizadorbusqueda = setTimeout(cargartemporadas, 300);
});

filtroestado.addEventListener('change', cargartemporadas);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);
botondesactivar.addEventListener('click', () => cambiarestado('desactivar', null, ''));

filtrosdesdeurl({ estado: filtroestado });
cargartemporadas();

// Si se entró desde "Agregar nueva temporada" del menú, abre el formulario
abrirformulariosipide(abrirnuevo);
