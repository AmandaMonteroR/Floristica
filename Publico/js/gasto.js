// Gastos del negocio: lista por período y categoría, formulario para registrar o editar, anular y volver a contar
const rutabase = 'index.php?modulo=gasto';
const clavebase = 'floristica.borrador.gasto.';
const camposformulario = ['tbgastocategoria', 'tbgastomonto', 'tbgastodescripcion', 'tbgastofecha'];

const cuerpotabla = document.getElementById('cuerpotabla');
const campodesde = document.getElementById('desde');
const campohasta = document.getElementById('hasta');
const chipsperiodo = document.getElementById('chipsperiodo');
const filtrocategoria = document.getElementById('filtrocategoria');
const filtroestado = document.getElementById('filtroestado');
const listacategorias = document.getElementById('listacategorias');
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

let temporizadoraviso;
let datosoriginales = {};
let desdeactual = '';
let hastaactual = '';

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearmonto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function montocorto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { maximumFractionDigits: 0 });
}

// "2026-10-11" -> "11/10/2026"
function formatearfecha(fecha) {
    const [anio, mes, dia] = String(fecha).slice(0, 10).split('-');
    return `${dia}/${mes}/${anio}`;
}

function fechatexto(fecha) {
    return fecha.toLocaleDateString('en-CA');
}

// Solo números y un punto con máximo 2 decimales
function filtrardecimal(valor) {
    const limpio = valor.replace(/,/g, '.').replace(/[^\d.]/g, '');
    const [entera, ...resto] = limpio.split('.');
    return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
}

// Fechas de cada botón de período
function periodo(nombre) {
    const hoy = new Date();
    switch (nombre) {
        case 'mespasado':
            return [fechatexto(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1)), fechatexto(new Date(hoy.getFullYear(), hoy.getMonth(), 0))];
        case 'anio':
            return [fechatexto(new Date(hoy.getFullYear(), 0, 1)), fechatexto(hoy)];
        case 'todo':
            return ['todo', ''];
        default:
            return [fechatexto(new Date(hoy.getFullYear(), hoy.getMonth(), 1)), fechatexto(hoy)];
    }
}

function marcarchip(nombre) {
    chipsperiodo.querySelectorAll('[data-periodo]').forEach((chip) => {
        const activo = chip.dataset.periodo === nombre;
        chip.classList.toggle('chip-activo', activo);
        chip.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
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
    const id = formulario.elements.tbgastoid.value;
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
    formulario.elements.tbgastoid.value = datos.tbgastoid ?? '';
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

/* ---------- Lista ---------- */

async function cargargastos(desde = desdeactual, hasta = hastaactual) {
    const respuesta = await consultar('listar', {
        desde,
        hasta,
        categoria: filtrocategoria.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const datos = respuesta.datos;
    desdeactual = datos.desde;
    hastaactual = datos.hasta;
    campodesde.value = datos.desde;
    campohasta.value = datos.hasta;

    const cuentan = datos.gastos.length - datos.anulados;
    document.getElementById('datototal').textContent = montocorto(datos.total);
    document.getElementById('notatotal').textContent = cuentan === 0
        ? 'Sin gastos en este período'
        : `${cuentan} ${cuentan === 1 ? 'gasto' : 'gastos'}` + (datos.anulados > 0 ? ` · ${datos.anulados} anulado${datos.anulados === 1 ? '' : 's'}` : '');

    // Barras por categoría (las más grandes primero)
    const categorias = Object.entries(datos.porcategoria);
    const mayor = categorias.length ? categorias[0][1] : 0;
    listacategorias.innerHTML = categorias.length
        ? categorias.map(([categoria, total]) => `
            <li>
                <span class="nombre-barra">${escapar(categoria)}</span>
                <span class="barra"><span style="width: ${Math.max(4, Math.round(total * 100 / mayor))}%"></span></span>
                <span class="monto-barra">${montocorto(total)}</span>
            </li>`).join('')
        : '<li class="texto-suave">Nada en este período.</li>';

    if (datos.gastos.length === 0) {
        cuerpotabla.innerHTML = '<tr><td colspan="7" class="vacio">No hay gastos en este período. Use "Registrar gasto" para agregar uno.</td></tr>';
        return;
    }

    cuerpotabla.innerHTML = datos.gastos.map((gasto) => {
        const cuenta = gasto.tbgastoestado === 1;
        const id = gasto.tbgastoid;
        return `
            <tr class="${cuenta ? '' : 'fila-inactiva'}">
                <td>${formatearfecha(gasto.tbgastofecha)}</td>
                <td>${escapar(gasto.tbgastocategoria)}</td>
                <td>${escapar(gasto.tbgastodescripcion)}</td>
                <td class="numero">${formatearmonto(gasto.tbgastomonto)}</td>
                <td>${gasto.registradopor ? escapar(gasto.registradopor) : '<span class="texto-suave">—</span>'}</td>
                <td><span class="estado ${cuenta ? 'estado-activo' : 'estado-inactivo'}">${cuenta ? 'Cuenta' : 'Anulado'}</span></td>
                <td class="columna-acciones">
                    ${cuenta ? `<button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>` : ''}
                    <button type="button" class="enlace" data-accion="historial" data-id="${id}">Historial</button>
                    ${cuenta
                        ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Anular</button>`
                        : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Volver a contar</button>`}
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
    datosoriginales = { tbgastofecha: fechatexto(new Date()) };
    llenarformulario(datosoriginales);
    limpiarerrores();
    aplicarborrador();
    formulario.elements.tbgastofecha.max = fechatexto(new Date());
    tituloformulario.textContent = 'Registrar gasto';
    botonguardar.textContent = 'Guardar gasto';
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
    formulario.elements.tbgastofecha.max = fechatexto(new Date());
    tituloformulario.textContent = 'Editar gasto';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

async function cambiarestado(accion, id, descripcion) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Anular el gasto?',
            mensaje: `"${descripcion}" ya no se sumará en los totales ni en la ganancia neta. No se borra: queda en el historial y puede volver a contarlo.`,
            textoaceptar: 'Anular',
            peligro: true,
        }
        : {
            titulo: '¿Volver a contar el gasto?',
            mensaje: `"${descripcion}" se sumará otra vez en los totales y en la ganancia neta.`,
            textoaceptar: 'Volver a contar',
        };

    if (!(await confirmar(configuracion))) {
        return;
    }

    const datos = new FormData();
    datos.append('id', id);

    const respuesta = await enviar(accion, datos);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        cargargastos();
    }
}

/* ---------- Eventos ---------- */

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbgastomonto') {
        campo.value = filtrardecimal(campo.value);
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

    const esedicion = formulario.elements.tbgastoid.value !== '';

    if (esedicion) {
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: 'Se actualizará el gasto y el cambio quedará en su historial.',
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
        cargargastos();
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
    const descripcion = boton.closest('tr').cells[2].textContent;

    switch (boton.dataset.accion) {
        case 'editar':
            abrireditar(id);
            break;
        case 'historial':
            ventanahistorial.mostrar('gasto', id, descripcion);
            break;
        case 'inactivar':
        case 'reactivar':
            cambiarestado(boton.dataset.accion, id, descripcion);
            break;
    }
});

chipsperiodo.addEventListener('click', (evento) => {
    const chip = evento.target.closest('[data-periodo]');
    if (!chip) {
        return;
    }
    marcarchip(chip.dataset.periodo);
    cargargastos(...periodo(chip.dataset.periodo));
});

[campodesde, campohasta].forEach((campo) => campo.addEventListener('change', () => {
    if (campodesde.value && campohasta.value) {
        marcarchip('');
        cargargastos(campodesde.value, campohasta.value);
    }
}));

filtrocategoria.addEventListener('change', () => cargargastos());
filtroestado.addEventListener('change', () => cargargastos());
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

marcarchip('mes');
cargargastos(...periodo('mes'));

// Si se entró desde "Registrar un gasto" del menú, abre el formulario
abrirformulariosipide(abrirnuevo);
