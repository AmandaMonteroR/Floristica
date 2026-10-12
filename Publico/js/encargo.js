const rutabase = 'index.php?modulo=encargo';
const clavebase = 'floristica.borrador.encargo.';
const camposformulario = ['tbarreglofloralid', 'tbencargocantidad', 'tbencargofechaentrega', 'tbempleadoid', 'tbencargonota'];

const cuerpotabla = document.getElementById('cuerpotabla');
const filtroestado = document.getElementById('filtroestado');
const filtroempleado = document.getElementById('filtroempleado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const campoasignar = document.getElementById('campoasignar');
const dialogoasignar = document.getElementById('dialogoasignar');
const formularioasignar = document.getElementById('formularioasignar');
const resumenasignar = document.getElementById('resumenasignar');
const avisoasignar = document.getElementById('avisoasignar');
const botonguardarasignar = document.getElementById('botonguardarasignar');
const dialogodetalle = document.getElementById('dialogodetalle');
const fotodetalle = document.getElementById('fotodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpodetalle = document.getElementById('cuerpodetalle');
const cuerpohistorial = document.getElementById('cuerpohistorial');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');

// usuarioactual viene de menu.php
const esduena = usuarioactual.rol === 'Dueña';

let temporizadoraviso;
let datosoriginales = {};
let datosservidor = {};
let empleados = [];
let encargos = [];

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

// "2026-10-11 15:25:00" -> "11/10/2026 3:25 p. m."
function formatearfechahora(fechahora) {
    if (!fechahora) {
        return '';
    }
    const [fecha, hora = '00:00'] = fechahora.split(' ');
    const [anio, mes, dia] = fecha.split('-');
    let [horas, minutos] = hora.split(':').map(Number);
    const sufijo = horas < 12 ? 'a. m.' : 'p. m.';
    horas = horas % 12 || 12;
    return `${dia}/${mes}/${anio} ${horas}:${String(minutos).padStart(2, '0')} ${sufijo}`;
}

function hoy() {
    return new Date().toLocaleDateString('en-CA');
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

function enviarid(accion, id) {
    const datos = new FormData();
    datos.append('id', id);
    return enviar(accion, datos);
}

function mostraraviso(mensaje, tipo) {
    aviso.textContent = mensaje;
    aviso.className = `aviso aviso-${tipo}`;
    aviso.hidden = false;
    clearTimeout(temporizadoraviso);
    temporizadoraviso = setTimeout(() => {
        aviso.hidden = true;
    }, 6000);
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

/* ---------- Listas de arreglos y empleados ---------- */

function nombreempleado(empleado) {
    return empleado.tbempleadorol === 'Dueña'
        ? `${empleado.tbempleadonombre} (Dueña)`
        : empleado.tbempleadonombre;
}

function llenarempleados(select, textovacio) {
    const valor = select.value;
    select.innerHTML = '';
    select.add(new Option(textovacio, ''));
    empleados.forEach((empleado) => {
        select.add(new Option(nombreempleado(empleado), empleado.tbempleadoid));
    });
    select.value = valor;
}

async function cargarlistas() {
    const [respuestaarreglos, respuestaempleados] = await Promise.all([consultar('arreglos'), consultar('empleados')]);

    const selectarreglo = formulario.elements.tbarreglofloralid;
    selectarreglo.innerHTML = '<option value="">Seleccione…</option>';
    (respuestaarreglos.exito ? respuestaarreglos.datos : []).forEach((arreglo) => {
        selectarreglo.add(new Option(arreglo.tbarreglofloralnombre, arreglo.tbarreglofloralid));
    });

    empleados = respuestaempleados.exito ? respuestaempleados.datos : [];
    llenarempleados(formulario.elements.tbempleadoid, 'Sin asignar por ahora');
    llenarempleados(formularioasignar.elements.tbempleadoid, 'Seleccione…');

    // Un empleado solo puede dejar el encargo libre o asignárselo a sí mismo
    if (!esduena) {
        Array.from(formulario.elements.tbempleadoid.options).forEach((opcion) => {
            if (opcion.value !== '' && opcion.value !== String(usuarioactual.id)) {
                opcion.remove();
            }
        });
    }

    filtroempleado.innerHTML = '<option value="">Todos</option><option value="sinasignar">Sin asignar</option>';
    empleados.forEach((empleado) => {
        filtroempleado.add(new Option(nombreempleado(empleado), empleado.tbempleadoid));
    });
}

/* ---------- Borrador ---------- */

function claveborrador() {
    const id = formulario.elements.tbencargoid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function leerformulario() {
    const datos = {};
    camposformulario.forEach((campo) => {
        datos[campo] = formulario.elements[campo].value;
    });
    return datos;
}

function guardarborrador() {
    try {
        localStorage.setItem(claveborrador(), JSON.stringify(leerformulario()));
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
    formulario.elements.tbencargoid.value = datos.tbencargoid ?? '';

    const selectarreglo = formulario.elements.tbarreglofloralid;
    if (datos.tbarreglofloralid && !selectarreglo.querySelector(`option[value="${datos.tbarreglofloralid}"]`)) {
        selectarreglo.add(new Option(`${datos.tbarreglofloralnombre} (inactivo)`, datos.tbarreglofloralid));
    }

    asignarcampos({
        tbarreglofloralid: datos.tbarreglofloralid ? String(datos.tbarreglofloralid) : '',
        tbencargocantidad: String(datos.tbencargocantidad ?? 1),
        tbencargofechaentrega: datos.tbencargofechaentrega ?? '',
        tbempleadoid: datos.tbempleadoid ? String(datos.tbempleadoid) : '',
        tbencargonota: datos.tbencargonota ?? '',
    });

    datosoriginales = leerformulario();
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    const haycambios = camposformulario.some((campo) => (borrador[campo] ?? '') !== (datosoriginales[campo] ?? ''));

    if (!haycambios) {
        borrarborrador();
        return;
    }

    asignarcampos(borrador);
    avisoborrador.hidden = false;
}

/* ---------- Tabla ---------- */

function claseestado(estado) {
    return {
        Pendiente: 'estado-pendiente',
        'En proceso': 'estado-confirmado',
        Terminado: 'estado-recibido',
        Cancelado: 'estado-inactivo',
    }[estado] ?? 'estado-inactivo';
}

function boton(accion, id, texto, peligro = false) {
    return `<button type="button" class="enlace${peligro ? ' enlace-peligro' : ''}" data-accion="${accion}" data-id="${id}">${texto}</button>`;
}

function accionesencargo(encargo) {
    const id = encargo.tbencargoid;
    const esmio = encargo.tbempleadoid === usuarioactual.id;
    const acciones = [boton('ver', id, 'Ver')];

    // Empleado: tomar encargos libres y mover solo los suyos
    if (!esduena) {
        if (encargo.tbencargoestado === 'Pendiente' && !encargo.tbempleadoid) {
            acciones.push(boton('asignarme', id, 'Asignármelo'));
        } else if (encargo.tbencargoestado === 'Pendiente' && esmio) {
            acciones.push(boton('iniciar', id, 'Iniciar'));
        } else if (encargo.tbencargoestado === 'En proceso' && esmio) {
            acciones.push(boton('terminar', id, 'Terminar'));
        }
        return acciones.join(' ');
    }

    if (encargo.tbencargoestado === 'Pendiente') {
        acciones.push(boton('editar', id, 'Editar'));
        acciones.push(boton('asignar', id, encargo.tbempleadoid ? 'Reasignar' : 'Asignar'));
        if (!esmio) {
            acciones.push(boton('asignarme', id, 'Asignármelo'));
        }
        if (encargo.tbempleadoid) {
            acciones.push(boton('iniciar', id, 'Iniciar'));
        }
        acciones.push(boton('cancelar', id, 'Cancelar', true));
    } else if (encargo.tbencargoestado === 'En proceso') {
        acciones.push(boton('asignar', id, 'Reasignar'));
        acciones.push(boton('terminar', id, 'Terminar'));
        acciones.push(boton('cancelar', id, 'Cancelar', true));
    }

    return acciones.join(' ');
}

async function cargarencargos() {
    const respuesta = await consultar('listar', {
        estado: filtroestado.value,
        empleado: filtroempleado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    encargos = respuesta.datos;

    if (encargos.length === 0) {
        const sinfiltros = filtroestado.value === '' && filtroempleado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay encargos. Use "Nuevo encargo" para registrar el primero.'
            : 'No hay encargos que coincidan con el filtro.';
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = encargos.map((encargo) => {
        const abierto = ['Pendiente', 'En proceso'].includes(encargo.tbencargoestado);
        const atrasado = abierto && encargo.tbencargofechaentrega < hoy();
        const sinstock = abierto && !encargo.alcanzastock
            ? '<span class="aviso-stock" title="No alcanza el inventario para prepararlo">Falta stock</span>'
            : '';
        const asignado = encargo.tbempleadoid
            ? escapar(encargo.tbempleadonombre) + (encargo.tbempleadorol === 'Dueña' ? ' <span class="estado estado-duena">Dueña</span>' : '')
            : '<span class="texto-suave">Sin asignar</span>';

        return `
            <tr class="${abierto ? '' : 'fila-inactiva'}">
                <td>${encargo.tbencargoid}</td>
                <td>
                    <div class="celda-arreglo">
                        <img src="${escapar(encargo.tbarreglofloralfoto)}" alt="">
                        <span>${escapar(encargo.tbarreglofloralnombre)}${sinstock}</span>
                    </div>
                </td>
                <td>${encargo.tbencargocantidad}</td>
                <td class="${atrasado ? 'texto-peligro' : ''}">${formatearfecha(encargo.tbencargofechaentrega)}${atrasado ? '<br><small>Atrasado</small>' : ''}</td>
                <td>${asignado}</td>
                <td><span class="estado ${claseestado(encargo.tbencargoestado)}">${escapar(encargo.tbencargoestado)}</span></td>
                <td class="columna-acciones">${accionesencargo(encargo)}</td>
            </tr>`;
    }).join('');
}

/* ---------- Errores ---------- */

function limpiarerrores(form = formulario) {
    form.querySelectorAll('[data-error]').forEach((error) => {
        error.textContent = '';
    });
    form.querySelectorAll('[aria-invalid]').forEach((campo) => {
        campo.removeAttribute('aria-invalid');
    });
    (form === formulario ? avisoformulario : avisoasignar).hidden = true;
}

function mostrarerrores(errores, form = formulario) {
    Object.entries(errores).forEach(([campo, mensaje]) => {
        const error = form.querySelector(`[data-error="${campo}"]`);
        if (error) {
            error.textContent = mensaje;
        }
        if (form.elements[campo]) {
            form.elements[campo].setAttribute('aria-invalid', 'true');
        }
    });

    const primero = form.querySelector('[aria-invalid="true"]');
    if (primero) {
        primero.focus();
    }
}

/* ---------- Abrir formularios ---------- */

async function abrirnuevo() {
    await listaslistas;
    datosservidor = { tbencargofechaentrega: hoy() };
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    campoasignar.hidden = false;
    tituloformulario.textContent = 'Nuevo encargo';
    botonguardar.textContent = 'Guardar encargo';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    await listaslistas;
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosservidor = respuesta.datos;
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    // Al editar, la asignación se cambia con el botón "Reasignar"
    campoasignar.hidden = true;
    tituloformulario.textContent = `Editar encargo N.º ${id}`;
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

function abrirasignar(id) {
    const encargo = encargos.find((item) => String(item.tbencargoid) === String(id));

    formularioasignar.reset();
    formularioasignar.elements.tbencargoid.value = id;
    formularioasignar.elements.tbempleadoid.value = encargo && encargo.tbempleadoid ? String(encargo.tbempleadoid) : '';
    resumenasignar.textContent = encargo
        ? `Encargo N.º ${id}: ${encargo.tbencargocantidad} × ${encargo.tbarreglofloralnombre}, entrega ${formatearfecha(encargo.tbencargofechaentrega)}.`
        : '';
    limpiarerrores(formularioasignar);
    dialogoasignar.showModal();
}

async function verdetalle(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const encargo = respuesta.datos;
    const filas = [
        ['Cantidad', `${encargo.tbencargocantidad} arreglo(s)`],
        ['Fecha de entrega', formatearfecha(encargo.tbencargofechaentrega)],
        ['Asignado a', encargo.tbempleadoid ? `${encargo.tbempleadonombre}${encargo.tbempleadorol === 'Dueña' ? ' (Dueña)' : ''}` : 'Sin asignar'],
        ['Estado', encargo.tbencargoestado],
        ['Registrado el', formatearfecha(encargo.tbencargofecharegistro)],
        ['Nota', encargo.tbencargonota],
    ];

    fotodetalle.src = encargo.tbarreglofloralfoto;
    fotodetalle.alt = encargo.tbarreglofloralnombre;
    titulodetalle.textContent = `Encargo N.º ${encargo.tbencargoid}: ${encargo.tbarreglofloralnombre}`;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

    cuerpodetalle.innerHTML = encargo.materiales.map((material) => `
        <tr>
            <td>${escapar(material.tbinventarionombre)}</td>
            <td>${material.necesarias}</td>
            <td>${material.stock}</td>
            <td>${material.alcanza ? '<span class="estado estado-activo">Sí</span>' : `<span class="estado estado-bajo">Faltan ${material.necesarias - material.stock}</span>`}</td>
        </tr>`).join('');


    cuerpohistorial.innerHTML = encargo.historial.length === 0
        ? '<tr><td colspan="3" class="vacio">Este encargo no tiene historial registrado.</td></tr>'
        : encargo.historial.map((cambio) => `
            <tr>
                <td>${formatearfechahora(cambio.fecha)}</td>
                <td>${escapar(cambio.texto)}</td>
                <td>${cambio.hechopor ? escapar(cambio.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
            </tr>`).join('');

    dialogodetalle.showModal();
}

/* ---------- Acciones de estado ---------- */

async function ejecutar(accion, id) {
    const encargo = encargos.find((item) => String(item.tbencargoid) === String(id));
    const nombre = encargo ? `${encargo.tbencargocantidad} × ${encargo.tbarreglofloralnombre}` : `el encargo N.º ${id}`;

    const configuraciones = {
        asignarme: {
            titulo: '¿Asignártelo a ti?',
            mensaje: `Te vas a encargar de preparar ${nombre}.`,
            textoaceptar: 'Asignármelo',
        },
        iniciar: {
            titulo: '¿Empezar a prepararlo?',
            mensaje: `${nombre} pasará a "En proceso".`,
            textoaceptar: 'Iniciar',
        },
        terminar: {
            titulo: '¿Marcar como terminado?',
            mensaje: `Se descontarán del inventario los productos que usa ${nombre}.`,
            textoaceptar: 'Terminar',
        },
        cancelar: {
            titulo: '¿Cancelar el encargo?',
            mensaje: `${nombre} quedará cancelado y ya no se podrá preparar.`,
            textoaceptar: 'Cancelar encargo',
            peligro: true,
        },
    };

    if (!(await confirmar(configuraciones[accion]))) {
        return;
    }

    const respuesta = await enviarid(accion, id);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        cargarencargos();
    }
}

/* ---------- Eventos ---------- */

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbencargocantidad') {
        campo.value = campo.value.replace(/\D/g, '');
    }

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
    guardarborrador();
});

formulario.addEventListener('click', (evento) => {
    const paso = evento.target.closest('button[data-paso]');
    if (!paso) {
        return;
    }

    const campo = formulario.elements.tbencargocantidad;
    const actual = parseInt(campo.value, 10) || 0;
    campo.value = String(Math.min(99, Math.max(1, actual + Number(paso.dataset.paso))));
    formulario.querySelector('[data-error="tbencargocantidad"]').textContent = '';
    guardarborrador();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbencargoid.value !== '';

    if (esedicion) {
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizará el encargo N.º ${formulario.elements.tbencargoid.value}.`,
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
        cargarencargos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});

formularioasignar.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    limpiarerrores(formularioasignar);
    botonguardarasignar.disabled = true;

    const respuesta = await enviar('asignar', new FormData(formularioasignar));

    botonguardarasignar.disabled = false;

    if (respuesta.exito) {
        dialogoasignar.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarencargos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores, formularioasignar);
    }

    avisoasignar.textContent = respuesta.mensaje;
    avisoasignar.hidden = false;
});

document.getElementById('botonasignarme').addEventListener('click', async () => {
    const respuesta = await enviarid('asignarme', formularioasignar.elements.tbencargoid.value);

    if (respuesta.exito) {
        dialogoasignar.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarencargos();
        return;
    }

    avisoasignar.textContent = respuesta.mensaje;
    avisoasignar.hidden = false;
});

cuerpotabla.addEventListener('click', (evento) => {
    const elemento = evento.target.closest('button[data-accion]');
    if (!elemento) {
        return;
    }

    const id = elemento.dataset.id;

    switch (elemento.dataset.accion) {
        case 'ver':
            verdetalle(id);
            break;
        case 'editar':
            abrireditar(id);
            break;
        case 'asignar':
            abrirasignar(id);
            break;
        case 'asignarme':
        case 'iniciar':
        case 'terminar':
        case 'cancelar':
            ejecutar(elemento.dataset.accion, id);
            break;
    }
});

filtroestado.addEventListener('change', cargarencargos);
filtroempleado.addEventListener('change', cargarencargos);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncancelarasignar').addEventListener('click', () => dialogoasignar.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
document.getElementById('botondescartarborrador').addEventListener('click', () => {
    borrarborrador();
    llenarformulario(datosservidor);
    limpiarerrores();
    avisoborrador.hidden = true;
});

formulario.elements.tbencargofechaentrega.min = hoy();

const listaslistas = cargarlistas();
cargarencargos();
abrirformulariosipide(abrirnuevo);