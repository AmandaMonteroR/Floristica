const rutabase = 'index.php?modulo=proveedor';
const clavebase = 'floristica.borrador.proveedor.';
const camposformulario = [
    'tbproveedornombreempresa',
    'tbproveedornombrecontacto',
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

function normalizar(campo, valor) {
    return campo === 'tbproveedortelefono' ? formateartelefono(valor) : String(valor ?? '');
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
    camposformulario
        .filter((campo) => !camposubicacion.includes(campo))
        .forEach((campo) => {
            formulario.elements[campo].value = normalizar(campo, datos[campo]);
        });

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
        (campo) => normalizar(campo, borrador[campo]) !== normalizar(campo, datosoriginales[campo])
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
                <td>${escapar(formateartelefono(proveedor.tbproveedortelefono))}</td>
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
        if (formulario.elements[campo]) {
            formulario.elements[campo].setAttribute('aria-invalid', 'true');
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

    datosoriginales = respuesta.datos;
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
    const ubicacion = [proveedor.tbproveedordistrito, proveedor.tbproveedorcanton, proveedor.tbproveedorprovincia]
        .filter((parte) => parte)
        .join(', ');
    const filas = [
        ['Contacto', proveedor.tbproveedornombrecontacto],
        ['Teléfono', formateartelefono(proveedor.tbproveedortelefono)],
        ['Correo', proveedor.tbproveedorcorreo],
        ['Ubicación', ubicacion],
        ['Dirección exacta', proveedor.tbproveedordireccion],
        ['Productos que ofrece', proveedor.tbproveedordescripcion],
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
        evento.target.value = formateartelefono(evento.target.value);
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

function formatearfechahora(fechahora) {
    if (!fechahora) {
        return '';
    }
    const [fecha, hora] = fechahora.split(' ');
    return `${formatearfecha(fecha)} ${hora.slice(0, 5)}`;
}

function mostrarvalorhistorial(campo, valor) {
    if (valor === null) {
        return '<span class="texto-suave">Registro inicial</span>';
    }
    if (valor === '') {
        return '<span class="texto-suave">Sin registrar</span>';
    }
    return escapar(campo === 'telefono' ? formateartelefono(valor) : valor);
}

async function verhistorial() {
    const respuesta = await consultar('historial', { id: iddetalle });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    titulohistorial.textContent = `Historial de ${titulodetalle.textContent}`;

    if (respuesta.datos.length === 0) {
        cuerpohistorial.innerHTML = '<tr><td colspan="4" class="vacio">Este proveedor todavía no tiene cambios registrados.</td></tr>';
    } else {
        cuerpohistorial.innerHTML = respuesta.datos.map((movimiento) => `
            <tr>
                <td>${formatearfechahora(movimiento.fecha)}</td>
                <td>${escapar(movimiento.etiqueta)}</td>
                <td>${mostrarvalorhistorial(movimiento.campo, movimiento.anterior)}</td>
                <td>${mostrarvalorhistorial(movimiento.campo, movimiento.nuevo)}</td>
            </tr>`).join('');
    }

    dialogohistorial.showModal();
}

document.getElementById('botonverhistorial').addEventListener('click', verhistorial);
document.getElementById('botoncerrarhistorial').addEventListener('click', () => dialogohistorial.close());

const ubicacioneslistas = cargarubicaciones();
cargarproveedores();