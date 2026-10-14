const rutabase = 'index.php?modulo=proveedor';
const clavebase = 'floristica.borrador.proveedor.';
const camposformulario = [
    'tbproveedornombreempresa',
    'tbproveedornombrecontacto',
    'tbproveedortelefonointernacional',
    'tbproveedorcodigopais',
    'tbproveedortelefono',
    'tbproveedorcorreo',
    'tbproveedorprovincia',
    'tbproveedorcanton',
    'tbproveedordistrito',
    'tbproveedordireccion',
    'tbproveedordescripcion',
];
const camposubicacion = ['tbproveedorprovincia', 'tbproveedorcanton', 'tbproveedordistrito'];

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
const dialogodetalle = document.getElementById('dialogodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const dialogohistorial = document.getElementById('dialogohistorial');
const titulohistorial = document.getElementById('titulohistorial');
const cuerpohistorial = document.getElementById('cuerpohistorial');
const selectprovincia = formulario.elements.tbproveedorprovincia;
const selectcanton = formulario.elements.tbproveedorcanton;
const selectdistrito = formulario.elements.tbproveedordistrito;
const casillainternacional = formulario.elements.tbproveedortelefonointernacional;
const campocodigopais = formulario.elements.tbproveedorcodigopais;
const campotelefono = formulario.elements.tbproveedortelefono;
const selectorpais = document.getElementById('selectorpais');
const buscarpais = document.getElementById('buscarpais');
const listapaises = document.getElementById('listapaises');
const opcionespais = Array.from(listapaises.querySelectorAll('[data-codigo]'));
const sinresultadospais = listapaises.querySelector('.sin-resultados');
const bloqueubicacion = document.getElementById('bloqueubicacion');
const etiquetadireccion = document.getElementById('etiquetadireccion');
const campodireccion = formulario.elements.tbproveedordireccion;

let temporizadorbusqueda;
let temporizadoraviso;
let datosoriginales = {};
let iddetalle = null;
let ubicaciones = [];

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearfecha(fecha) {
    if (!fecha) {
        return '';
    }
    const [anio, mes, dia] = fecha.split('-');
    return `${dia}/${mes}/${anio}`;
}

function formateartelefono(valor) {
    const digitos = String(valor ?? '').replace(/\D/g, '').slice(0, 8);
    return digitos.length > 4 ? `${digitos.slice(0, 4)}-${digitos.slice(4)}` : digitos;
}

function formatearnumero(valor, internacional) {
    return internacional
        ? String(valor ?? '').replace(/\D/g, '').slice(0, 12)
        : formateartelefono(valor);
}

// Para mostrar en la tabla, el detalle y el historial
function mostrartelefono(valor) {
    const texto = String(valor ?? '');
    return texto.startsWith('+') ? texto : formateartelefono(texto);
}

// Convierte "+57 3001234567" en los tres campos del formulario
function separartelefono(datos) {
    const coincidencia = String(datos.tbproveedortelefono ?? '').match(/^\+(\d{1,3}) (\d+)$/);

    if (!coincidencia) {
        return { ...datos, tbproveedortelefonointernacional: '', tbproveedorcodigopais: '' };
    }

    return {
        ...datos,
        tbproveedortelefonointernacional: '1',
        tbproveedorcodigopais: coincidencia[1],
        tbproveedortelefono: coincidencia[2],
    };
}

function normalizar(campo, valor, datos = {}) {
    if (campo === 'tbproveedortelefono') {
        return formatearnumero(valor, datos.tbproveedortelefonointernacional === '1');
    }
    return String(valor ?? '');
}

function valorcampo(campo) {
    const elemento = formulario.elements[campo];
    if (elemento.type === 'checkbox') {
        return elemento.checked ? '1' : '';
    }
    return elemento.value;
}

// Un proveedor con número internacional es de otro país: no lleva provincia,
// cantón ni distrito, y la dirección se escribe libre (ciudad, país…)
function actualizartipotelefono() {
    const internacional = casillainternacional.checked;

    selectorpais.hidden = !internacional;
    bloqueubicacion.hidden = internacional;
    etiquetadireccion.textContent = internacional ? 'Dirección (ciudad y país)' : 'Dirección exacta';
    campodireccion.placeholder = internacional ? 'Medellín, Colombia…' : '200 m norte de la iglesia…';
    campotelefono.placeholder = internacional ? '3001234567' : '8888-8888';
    campotelefono.maxLength = internacional ? 12 : 9;
    campotelefono.value = formatearnumero(campotelefono.value, internacional);

    if (!internacional) {
        const error = formulario.querySelector('[data-error="tbproveedorcodigopais"]');
        error.textContent = '';
        buscarpais.removeAttribute('aria-invalid');
        cerrarlistapaises();
    }
}

/* ---------- Buscador de país ---------- */

// Quita tildes y mayúsculas para comparar: "mexi" encuentra "México"
function sintildes(texto) {
    return String(texto).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}

function textopais(codigo) {
    const opcion = opcionespais.find((elemento) => elemento.dataset.codigo === String(codigo));
    return opcion ? opcion.textContent : '';
}

function establecerpais(codigo) {
    campocodigopais.value = textopais(codigo) ? String(codigo) : '';
    buscarpais.value = textopais(codigo);
}

function opcionesvisibles() {
    return opcionespais.filter((opcion) => !opcion.hidden);
}

function marcaropcion(opcion) {
    opcionespais.forEach((elemento) => elemento.classList.remove('activa'));
    if (opcion) {
        opcion.classList.add('activa');
        opcion.scrollIntoView({ block: 'nearest' });
        buscarpais.setAttribute('aria-activedescendant', opcion.id);
    } else {
        buscarpais.removeAttribute('aria-activedescendant');
    }
}

// Si escribe letras busca por nombre ("mexi"); si escribe números, por código ("57" o "+57")
function filtrarpaises() {
    const texto = sintildes(buscarpais.value);
    const escodigo = /^\+?\d+$/.test(texto);
    const digitos = texto.replace('+', '');

    opcionespais.forEach((opcion) => {
        const nombre = sintildes(opcion.textContent.replace(/\s*\(\+\d+\)$/, ''));
        const coincide = escodigo ? opcion.dataset.codigo.startsWith(digitos) : nombre.includes(texto);
        opcion.hidden = texto !== '' && !coincide;
    });

    const visibles = opcionesvisibles();
    sinresultadospais.hidden = visibles.length > 0;
    listapaises.hidden = false;
    buscarpais.setAttribute('aria-expanded', 'true');
    marcaropcion(visibles[0]);
}

function cerrarlistapaises() {
    listapaises.hidden = true;
    buscarpais.setAttribute('aria-expanded', 'false');
    marcaropcion(null);
}

function elegirpais(opcion) {
    establecerpais(opcion.dataset.codigo);
    cerrarlistapaises();
    formulario.querySelector('[data-error="tbproveedorcodigopais"]').textContent = '';
    buscarpais.removeAttribute('aria-invalid');
    guardarborrador();
}

opcionespais.forEach((opcion, indice) => {
    opcion.id = `pais-${indice}`;
});

buscarpais.addEventListener('input', () => {
    // Mientras escribe, el país todavía no está elegido
    campocodigopais.value = '';
    filtrarpaises();
});

buscarpais.addEventListener('focus', filtrarpaises);

buscarpais.addEventListener('keydown', (evento) => {
    const visibles = opcionesvisibles();
    const actual = visibles.indexOf(listapaises.querySelector('.activa'));

    if (evento.key === 'ArrowDown' || evento.key === 'ArrowUp') {
        evento.preventDefault();
        if (listapaises.hidden) {
            filtrarpaises();
            return;
        }
        const paso = evento.key === 'ArrowDown' ? 1 : -1;
        const siguiente = Math.min(visibles.length - 1, Math.max(0, actual + paso));
        marcaropcion(visibles[siguiente]);
    } else if (evento.key === 'Enter' && !listapaises.hidden) {
        // Enter elige el país marcado y no envía el formulario
        evento.preventDefault();
        if (visibles[actual]) {
            elegirpais(visibles[actual]);
        }
    } else if (evento.key === 'Escape' && !listapaises.hidden) {
        evento.preventDefault();
        evento.stopPropagation();
        cerrarlistapaises();
    }
});

// mousedown en lugar de click, para que el campo no pierda el foco antes de elegir
listapaises.addEventListener('mousedown', (evento) => {
    evento.preventDefault();
    const opcion = evento.target.closest('[data-codigo]');
    if (opcion) {
        elegirpais(opcion);
    }
});

// Al salir del campo: si escribió el nombre completo de un país, se toma; si no, queda sin elegir
buscarpais.addEventListener('blur', () => {
    cerrarlistapaises();
    if (campocodigopais.value === '') {
        const exacta = opcionespais.find((opcion) => sintildes(opcion.textContent) === sintildes(buscarpais.value));
        if (exacta) {
            establecerpais(exacta.dataset.codigo);
            guardarborrador();
        }
    }
});

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
    }, 4000);
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

function claveborrador() {
    const id = formulario.elements.tbproveedorid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function guardarborrador() {
    const datos = {};
    camposformulario.forEach((campo) => {
        datos[campo] = valorcampo(campo);
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

function llenarselect(select, opciones, textovacio) {
    select.innerHTML = '';
    select.add(new Option(textovacio, ''));
    opciones.forEach((opcion) => {
        select.add(new Option(opcion, opcion));
    });
    select.disabled = opciones.length === 0;
}

function buscarprovincia(nombreprovincia) {
    return ubicaciones.find((provincia) => provincia.nombre === nombreprovincia);
}

function buscarcanton(nombreprovincia, nombrecanton) {
    const provincia = buscarprovincia(nombreprovincia);
    return provincia ? provincia.cantones.find((canton) => canton.nombre === nombrecanton) : null;
}

function actualizarcantones(nombreprovincia, nombrecanton = '') {
    const provincia = buscarprovincia(nombreprovincia);
    const cantones = provincia ? provincia.cantones.map((canton) => canton.nombre) : [];

    llenarselect(selectcanton, cantones, provincia ? 'Seleccione…' : 'Seleccione la provincia');
    selectcanton.value = nombrecanton;
}

function actualizardistritos(nombreprovincia, nombrecanton, nombredistrito = '') {
    const canton = buscarcanton(nombreprovincia, nombrecanton);
    const distritos = canton ? canton.distritos : [];

    llenarselect(selectdistrito, distritos, canton ? 'Seleccione…' : 'Seleccione el cantón');
    selectdistrito.value = nombredistrito;
}

function establecerubicacion(provincia = '', canton = '', distrito = '') {
    selectprovincia.value = provincia;
    actualizarcantones(selectprovincia.value, canton);
    actualizardistritos(selectprovincia.value, selectcanton.value, distrito);
}

async function cargarubicaciones() {
    const respuesta = await consultar('ubicaciones');

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje || 'No se pudieron cargar las provincias.', 'error');
        return;
    }

    ubicaciones = respuesta.datos;
    llenarselect(selectprovincia, ubicaciones.map((provincia) => provincia.nombre), 'Seleccione…');
}

function asignarcampos(datos) {
    casillainternacional.checked = datos.tbproveedortelefonointernacional === '1';

    camposformulario
        .filter((campo) => !camposubicacion.includes(campo) && campo !== 'tbproveedortelefonointernacional')
        .forEach((campo) => {
            formulario.elements[campo].value = normalizar(campo, datos[campo], datos);
        });

    establecerpais(datos.tbproveedorcodigopais ?? '');
    actualizartipotelefono();

    establecerubicacion(
        datos.tbproveedorprovincia ?? '',
        datos.tbproveedorcanton ?? '',
        datos.tbproveedordistrito ?? ''
    );
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbproveedorid.value = datos.tbproveedorid ?? '';
    asignarcampos(datos);
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    const haycambios = camposformulario.some(
        (campo) => normalizar(campo, borrador[campo], borrador) !== normalizar(campo, datosoriginales[campo], datosoriginales)
    );

    if (!haycambios) {
        borrarborrador();
        return;
    }

    asignarcampos(borrador);
    avisoborrador.hidden = false;
}

async function cargarproveedores() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="5" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay proveedores registrados. Use "Nuevo proveedor" para agregar el primero.'
            : 'No hay proveedores que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="5" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((proveedor) => {
        const activo = proveedor.tbproveedorestado === 1;
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${proveedor.tbproveedorid}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${proveedor.tbproveedorid}">Reactivar</button>`;

        return `
            <tr class="${activo ? '' : 'fila-inactiva'}">
                <td>${escapar(proveedor.tbproveedornombreempresa)}</td>
                <td>${escapar(proveedor.tbproveedornombrecontacto)}</td>
                <td>${escapar(mostrartelefono(proveedor.tbproveedortelefono))}</td>
                <td><span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="columna-acciones">
                    <button type="button" class="enlace" data-accion="ver" data-id="${proveedor.tbproveedorid}">Ver</button>
                    <button type="button" class="enlace" data-accion="editar" data-id="${proveedor.tbproveedorid}">Editar</button>
                    ${botonestado}
                </td>
            </tr>`;
    }).join('');
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
        // El error del país se marca en el campo donde se escribe
        const elemento = campo === 'tbproveedorcodigopais' ? buscarpais : formulario.elements[campo];
        if (elemento) {
            elemento.setAttribute('aria-invalid', 'true');
        }
    });

    const primero = formulario.querySelector('[aria-invalid="true"]');
    if (primero) {
        primero.focus();
    }
}

async function abrirnuevo() {
    await ubicacioneslistas;
    datosoriginales = {};
    llenarformulario(datosoriginales);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Nuevo proveedor';
    botonguardar.textContent = 'Guardar proveedor';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    await ubicacioneslistas;
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosoriginales = separartelefono(respuesta.datos);
    llenarformulario(datosoriginales);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Editar proveedor';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

function descartarborrador() {
    borrarborrador();
    llenarformulario(datosoriginales);
    limpiarerrores();
    avisoborrador.hidden = true;
}

async function verdetalle(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const proveedor = respuesta.datos;
    iddetalle = id;
    const extranjero = String(proveedor.tbproveedortelefono ?? '').startsWith('+');
    const ubicacion = extranjero
        ? 'Fuera de Costa Rica'
        : [proveedor.tbproveedordistrito, proveedor.tbproveedorcanton, proveedor.tbproveedorprovincia]
            .filter((parte) => parte)
            .join(', ');
    const filas = [
        ['Contacto', proveedor.tbproveedornombrecontacto],
        ['Teléfono', mostrartelefono(proveedor.tbproveedortelefono)],
        ['Correo', proveedor.tbproveedorcorreo],
        ['Ubicación', ubicacion],
        [extranjero ? 'Dirección' : 'Dirección exacta', proveedor.tbproveedordireccion],        ['Productos que ofrece', proveedor.tbproveedordescripcion],
        ['Estado', proveedor.tbproveedorestado === 1 ? 'Activo' : 'Inactivo'],
        ['Fecha de registro', formatearfecha(proveedor.tbproveedorfecharegistro)],
    ];

    titulodetalle.textContent = proveedor.tbproveedornombreempresa;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');
    dialogodetalle.showModal();
}

async function cambiarestado(accion, id, nombre) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Inactivar proveedor?',
            mensaje: `${nombre} quedará inactivo y no se usará en nuevas órdenes de compra. Podrá reactivarlo cuando lo necesite.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        }
        : {
            titulo: '¿Reactivar proveedor?',
            mensaje: `${nombre} volverá a estar disponible para nuevas órdenes de compra.`,
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
        cargarproveedores();
    }
}

formulario.addEventListener('input', (evento) => {
    if (evento.target.name === 'tbproveedortelefono') {
        evento.target.value = formatearnumero(evento.target.value, casillainternacional.checked);
    }
    if (evento.target.name === 'tbproveedortelefonointernacional') {
        actualizartipotelefono();
    }
    if (evento.target.name === 'tbproveedornombrecontacto') {
        evento.target.value = evento.target.value.replace(/[^\p{L}\s'.-]/gu, '');
    }
    guardarborrador();
});

selectprovincia.addEventListener('change', () => {
    actualizarcantones(selectprovincia.value);
    actualizardistritos(selectprovincia.value, '');
    guardarborrador();
});


selectcanton.addEventListener('change', () => {
    actualizardistritos(selectprovincia.value, selectcanton.value);
    guardarborrador();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbproveedorid.value !== '';

    if (esedicion) {
        const nombre = formulario.elements.tbproveedornombreempresa.value.trim() || 'este proveedor';
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
        cargarproveedores();
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
        case 'ver':
            verdetalle(id);
            break;
        case 'editar':
            abrireditar(id);
            break;
        case 'inactivar':
        case 'reactivar':
            cambiarestado(boton.dataset.accion, id, nombre);
            break;
    }
});

busqueda.addEventListener('input', () => {
    clearTimeout(temporizadorbusqueda);
    temporizadorbusqueda = setTimeout(cargarproveedores, 300);
});

filtroestado.addEventListener('change', cargarproveedores);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

// "2026-10-11 15:25:00" -> "11/10/2026 3:25 p. m."
function formatearfechahora(fechahora) {
    if (!fechahora) {
        return '';
    }
    const [fecha, hora = '00:00'] = fechahora.split(' ');
    let [horas, minutos] = hora.split(':').map(Number);
    const sufijo = horas < 12 ? 'a. m.' : 'p. m.';
    horas = horas % 12 || 12;
    return `${formatearfecha(fecha)} ${horas}:${String(minutos).padStart(2, '0')} ${sufijo}`;
}

function mostrarvalorhistorial(campo, valor) {
    if (valor === null) {
        return '<span class="texto-suave">Registro inicial</span>';
    }
    if (valor === '') {
        return '<span class="texto-suave">Sin registrar</span>';
    }
    return escapar(campo === 'telefono' ? mostrartelefono(valor) : valor);
}

async function verhistorial() {
    const respuesta = await consultar('historial', { id: iddetalle });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    titulohistorial.textContent = `Historial de ${titulodetalle.textContent}`;

    if (respuesta.datos.length === 0) {
        cuerpohistorial.innerHTML = '<tr><td colspan="5" class="vacio">Este proveedor todavía no tiene cambios registrados.</td></tr>';
    } else {
        cuerpohistorial.innerHTML = respuesta.datos.map((movimiento) => `
            <tr>
                <td>${formatearfechahora(movimiento.fecha)}</td>
                <td>${escapar(movimiento.etiqueta)}</td>
                <td>${mostrarvalorhistorial(movimiento.campo, movimiento.anterior)}</td>
                <td>${mostrarvalorhistorial(movimiento.campo, movimiento.nuevo)}</td>
                <td>${movimiento.hechopor ? escapar(movimiento.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
            </tr>`).join('');
    }

    dialogohistorial.showModal();
}

document.getElementById('botonverhistorial').addEventListener('click', verhistorial);
document.getElementById('botoncerrarhistorial').addEventListener('click', () => dialogohistorial.close());

const ubicacioneslistas = cargarubicaciones();
cargarproveedores();


// Si se entró desde "Agregar nuevo ..." del menú, abre el formulario
abrirformulariosipide(abrirnuevo);