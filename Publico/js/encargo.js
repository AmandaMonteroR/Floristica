const rutabase = 'index.php?modulo=encargo';
const clavebase = 'floristica.borrador.encargo.';
const camposformulario = ['tbclienteid', 'tbsolicitudencargotipo', 'tbarreglofloralid', 'tbsolicitudencargocantidad',
    'tbsolicitudencargomodalidad', 'tbsolicitudencargopresupuesto', 'tbzonaentregaid', 'tbsolicitudencargodireccionentrega',
    'tbsolicitudencargofechaentrega', 'tbempleadoid', 'tbsolicitudencargonota',
    'tbsolicitudencargosprayid', 'tbsolicitudencargoflorespintadas', 'tbsolicitudencargoporcentaje'];
const categoriasproducto = [
    ['Flor', 'Flores y follaje'],
    ['Envoltura', 'Papel y cintas'],
    ['Base', 'Bases'],
    ['Chocolate', 'Chocolates'],
    ['Peluche', 'Peluches'],
    ['Tarjeta', 'Tarjetas'],
    ['Accesorio', 'Accesorios'],
];

const cuerpotabla = document.getElementById('cuerpotabla');
const filtroestado = document.getElementById('filtroestado');
const filtrotipo = document.getElementById('filtrotipo');
const filtroempleado = document.getElementById('filtroempleado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const seccionpredefinido = document.getElementById('seccionpredefinido');
const seccionpersonalizado = document.getElementById('seccionpersonalizado');
const seccionlineas = document.getElementById('seccionlineas');
const titulolineas = document.getElementById('titulolineas');
const notalineas = document.getElementById('notalineas');
const cuerpolineas = document.getElementById('cuerpolineas');
const restopresupuesto = document.getElementById('restopresupuesto');
const campopresupuesto = document.getElementById('campopresupuesto');
const campozona = document.getElementById('campozona');
const campodireccion = document.getElementById('campodireccion');
const notaexpress = document.getElementById('notaexpress');
const campoasignar = document.getElementById('campoasignar');
const resumentotal = document.getElementById('resumentotal');
const seccionpintura = document.getElementById('seccionpintura');
const notapintura = document.getElementById('notapintura');
const dialogoasignar = document.getElementById('dialogoasignar');
const formularioasignar = document.getElementById('formularioasignar');
const resumenasignar = document.getElementById('resumenasignar');
const avisoasignar = document.getElementById('avisoasignar');
const botonguardarasignar = document.getElementById('botonguardarasignar');
const dialogodetalle = document.getElementById('dialogodetalle');
const fotodetalle = document.getElementById('fotodetalle');
const columnafoto = document.getElementById('columnafoto');
const fotovacia = document.getElementById('fotovacia');
const campofoto = document.getElementById('campofoto');
const botonfoto = document.getElementById('botonfoto');
const notafoto = document.getElementById('notafoto');
const errorfoto = document.getElementById('errorfoto');
const columnaprecio = document.getElementById('columnaprecio');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpocobro = document.getElementById('cuerpocobro');
const cuerpodetalle = document.getElementById('cuerpodetalle');
const cuerpohistorial = document.getElementById('cuerpohistorial');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const casillaexpress = formulario.elements.tbsolicitudencargoexpress;

// usuarioactual viene de menu.php
const espropietario = usuarioactual.rol === 'Propietario';

let temporizadoraviso;
let datosoriginales = {};
let datosservidor = {};
let opciones = { arreglos: [], empleados: [], clientes: [], zonas: [], productos: [], pinturas: [] };
let encargos = [];
let idfoto = null;
let lineas = [];

/* ---------- Ayudas ---------- */

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearmonto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Monto corto para la cotización de WhatsApp: ₡15 000
function montocorto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { maximumFractionDigits: 2 });
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

function filtrardecimal(valor) {
    const limpio = valor.replace(/,/g, '.').replace(/[^\d.]/g, '');
    const [entera, ...resto] = limpio.split('.');
    return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
}

function buscar(lista, campo, id) {
    return lista.find((item) => String(item[campo]) === String(id));
}

async function peticion(accion, opcionespeticion = {}, parametros = {}) {
    const url = `${rutabase}&accion=${accion}&${new URLSearchParams(parametros)}`;
    try {
        const respuesta = await fetch(url, opcionespeticion);
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

/* ---------- Listas del formulario ---------- */

function nombreempleado(empleado) {
    return empleado.tbempleadorol === 'Propietario'
        ? `${empleado.tbempleadonombre} (Propietario)`
        : empleado.tbempleadonombre;
}

function llenarselect(select, textovacio, lista, valor, texto) {
    const actual = select.value;
    select.innerHTML = '';
    select.add(new Option(textovacio, ''));
    lista.forEach((item) => select.add(new Option(texto(item), item[valor])));
    select.value = actual;
}

async function cargaropciones() {
    const respuesta = await consultar('opciones');
    if (respuesta.exito) {
        opciones = respuesta.datos;
    }

    llenarselect(formulario.elements.tbclienteid, 'Seleccione…', opciones.clientes, 'tbclienteid',
        (cliente) => `${cliente.tbclientenombre} · ${cliente.tbclientetelefono}`);
    llenarselect(formulario.elements.tbarreglofloralid, 'Seleccione…', opciones.arreglos, 'tbarreglofloralid',
        (arreglo) => `${arreglo.tbarreglofloralnombre} · ${montocorto(arreglo.tbarreglofloralprecio)}`);
    llenarselect(formulario.elements.tbzonaentregaid, 'Seleccione…', opciones.zonas, 'tbzonaentregaid',
        (zona) => `${zona.tbzonaentreganombre} · ${montocorto(zona.tbzonaentregaprecio)}`);
    llenarselect(formulario.elements.tbsolicitudencargosprayid, 'Sin flores pintadas', opciones.pinturas ?? [], 'tbinventarioid',
        (pintura) => `${pintura.tbinventarionombre} · ${montocorto(pintura.cobroflor)} por flor`);
    llenarselect(formulario.elements.tbempleadoid, 'Sin asignar por ahora', opciones.empleados, 'tbempleadoid', nombreempleado);
    llenarselect(formularioasignar.elements.tbempleadoid, 'Seleccione…', opciones.empleados, 'tbempleadoid', nombreempleado);

    // Un empleado solo puede dejar la solicitud libre o asignársela a sí mismo
    if (!espropietario) {
        Array.from(formulario.elements.tbempleadoid.options).forEach((opcion) => {
            if (opcion.value !== '' && opcion.value !== String(usuarioactual.id)) {
                opcion.remove();
            }
        });
    }

    filtroempleado.innerHTML = '<option value="">Todos</option><option value="sinasignar">Sin asignar</option>';
    opciones.empleados.forEach((empleado) => {
        filtroempleado.add(new Option(nombreempleado(empleado), empleado.tbempleadoid));
    });
}

// Si la solicitud tiene algo que ya no está activo (cliente, arreglo o zona), se agrega a la lista
function agregarinactivo(select, id, texto) {
    if (id && !select.querySelector(`option[value="${id}"]`)) {
        select.add(new Option(`${texto} (inactivo)`, id));
    }
}

/* ---------- Productos (adicionales o los del arreglo personalizado) ---------- */

function lineavacia() {
    return { tbinventarioid: '', cantidad: '1', nombre: '', precioguardado: null };
}

// % de ganancia de los personalizados (100 si el campo está vacío o no es válido)
function porcentaje() {
    const valor = parseInt(formulario.elements.tbsolicitudencargoporcentaje.value, 10);
    return Number.isNaN(valor) ? 100 : Math.min(500, Math.max(0, valor));
}

// Igual que en el servidor: costo + %, hacia arriba a ₡10
function precioporcentaje(costo, por = porcentaje()) {
    return Math.ceil(Math.round(costo * (1 + por / 100) * 100) / 100 / 10) * 10;
}

// Precio por unidad de un producto con los precios normales ('normal'), de temporada alta ('alta')
// o los que se usan ahora (sin modo)
function preciounidad(producto, modo = opciones.temporada ? 'alta' : 'normal') {
    if (esPersonalizado()) {
        return precioporcentaje(modo === 'alta' ? producto.costoalto : producto.costonormal);
    }
    return modo === 'alta' ? producto.ventaalta : producto.ventanormal;
}

// Al editar: si no cambió el %, los productos conservan el precio con que se cotizaron
function usaprecioguardado(linea) {
    if (linea.precioguardado === null || linea.precioguardado === undefined) {
        return false;
    }
    return !esPersonalizado() || (datosservidor.tbsolicitudencargotipo === 'Personalizado'
        && Number(datosservidor.tbsolicitudencargoporcentaje) === porcentaje());
}

// El precio que se cobra: el que quedó guardado en la solicitud o el de ahora
function preciolinea(linea) {
    if (usaprecioguardado(linea)) {
        return Number(linea.precioguardado);
    }
    const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
    return producto ? preciounidad(producto) : 0;
}

function subtotallinea(linea) {
    return (parseInt(linea.cantidad, 10) || 0) * preciolinea(linea);
}

function sumaproductos() {
    return lineas.reduce((suma, linea) => suma + (linea.tbinventarioid ? subtotallinea(linea) : 0), 0);
}

// Lo que entra en el presupuesto del cliente: los productos y las flores pintadas
function gastopresupuesto() {
    return sumaproductos() + calcular().pintura;
}

function presupuesto() {
    return formulario.elements.tbsolicitudencargomodalidad.value === 'Presupuesto' && esPersonalizado()
        ? parseFloat(formulario.elements.tbsolicitudencargopresupuesto.value) || 0
        : null;
}

function esPersonalizado() {
    return formulario.elements.tbsolicitudencargotipo.value === 'Personalizado';
}

function opcionesproducto(linea) {
    const usados = lineas
        .filter((otra) => otra !== linea && otra.tbinventarioid !== '')
        .map((otra) => String(otra.tbinventarioid));
    // Con presupuesto: lo que queda sin contar esta línea
    const tope = presupuesto();
    const disponible = tope !== null ? tope - (gastopresupuesto() - (linea.tbinventarioid ? subtotallinea(linea) : 0)) : null;

    let html = '<option value="">Seleccione…</option>';

    categoriasproducto.forEach(([categoria, etiqueta]) => {
        const grupo = opciones.productos.filter((producto) => producto.tbinventariocategoria === categoria);
        if (grupo.length === 0) {
            return;
        }

        html += `<optgroup label="${etiqueta}">`;
        grupo.forEach((producto) => {
            const id = String(producto.tbinventarioid);
            const seleccionado = id === String(linea.tbinventarioid);
            const nocabe = disponible !== null && !seleccionado && preciounidad(producto) > disponible;
            const desactivado = !seleccionado && (usados.includes(id) || nocabe);
            const extra = producto.tbinventariostock <= 0 ? ' (sin stock)' : nocabe ? ' (no alcanza)' : '';
            html += `<option value="${id}"${seleccionado ? ' selected' : ''}${desactivado ? ' disabled' : ''}>`
                + `${escapar(producto.tbinventarionombre)} · ${montocorto(preciounidad(producto))}${extra}</option>`;
        });
        html += '</optgroup>';
    });

    // Producto guardado que ya no está activo
    if (linea.tbinventarioid !== '' && !buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid)) {
        html += `<option value="${linea.tbinventarioid}" selected>${escapar(linea.nombre || 'Producto')} (inactivo)</option>`;
    }

    return html;
}

// Debajo del precio de un producto del personalizado: su costo + el % (solo el propietario ve el costo)
function detalleprecio(linea) {
    const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
    if (!esPersonalizado() || !espropietario || !producto || usaprecioguardado(linea)) {
        return '';
    }
    const costo = opciones.temporada ? producto.costoalto : producto.costonormal;
    return `<small class="detalle-precio">costo ${montocorto(costo)} + ${porcentaje()}%</small>`;
}

function dibujarlineas() {
    columnaprecio.textContent = esPersonalizado() ? `Precio (costo + ${porcentaje()}%)` : 'Precio';
    if (lineas.length === 0) {
        const texto = esPersonalizado()
            ? 'Use "Agregar producto" para elegir las flores, el papel, la base y lo demás que quiere el cliente.'
            : 'Sin adicionales. Use "Agregar producto" si el cliente quiere chocolates, un peluche, una tarjeta…';
        cuerpolineas.innerHTML = `<tr><td colspan="5" class="vacio">${texto}</td></tr>`;
    } else {
        cuerpolineas.innerHTML = lineas.map((linea, indice) => `
            <tr data-indice="${indice}">
                <td><select data-campo="tbinventarioid" aria-label="Producto ${indice + 1}">${opcionesproducto(linea)}</select></td>
                <td>
                    <div class="contador">
                        <button type="button" class="boton-contador" data-pasolinea="-1" aria-label="Quitar una unidad del producto ${indice + 1}">−</button>
                        <input type="text" data-campo="cantidad" inputmode="numeric" maxlength="3" autocomplete="off" value="${escapar(linea.cantidad)}" aria-label="Cantidad del producto ${indice + 1}">
                        <button type="button" class="boton-contador" data-pasolinea="1" aria-label="Agregar una unidad del producto ${indice + 1}">+</button>
                    </div>
                </td>
                <td class="precio-linea">${linea.tbinventarioid ? formatearmonto(preciolinea(linea)) + detalleprecio(linea) : '—'}</td>
                <td class="subtotal-linea">${linea.tbinventarioid ? formatearmonto(subtotallinea(linea)) : '—'}</td>
                <td><button type="button" class="enlace enlace-peligro" data-quitar="${indice}" aria-label="Quitar producto ${indice + 1}">Quitar</button></td>
            </tr>`).join('');
    }

    actualizarresumen();
}

// Al cambiar una cantidad se actualiza solo esa fila y los totales (así no se pierde el foco)
function actualizarfila(fila, linea) {
    fila.querySelector('[data-campo="cantidad"]').value = linea.cantidad;
    fila.querySelector('.subtotal-linea').textContent = linea.tbinventarioid ? formatearmonto(subtotallinea(linea)) : '—';
    formulario.querySelector('[data-error="insumos"]').textContent = '';
    // Con presupuesto, las opciones que alcanzan cambian
    if (presupuesto() !== null) {
        cuerpolineas.querySelectorAll('tr[data-indice]').forEach((otrafila) => {
            const otra = lineas[Number(otrafila.dataset.indice)];
            otrafila.querySelector('select').innerHTML = opcionesproducto(otra);
        });
    }
    actualizarresumen();
    guardarborrador();
}

/* ---------- Secciones que dependen del tipo y del express ---------- */

function actualizarsecciones() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    const personalizado = tipo === 'Personalizado';
    const conpresupuesto = personalizado && formulario.elements.tbsolicitudencargomodalidad.value === 'Presupuesto';

    seccionpredefinido.hidden = tipo !== 'Predefinido';
    seccionpersonalizado.hidden = !personalizado;
    seccionlineas.hidden = tipo === '';
    seccionpintura.hidden = tipo === '' || ((opciones.pinturas ?? []).length === 0 && !formulario.elements.tbsolicitudencargosprayid.value);
    campopresupuesto.hidden = !conpresupuesto;

    titulolineas.textContent = personalizado ? 'Productos del arreglo *' : 'Adicionales';
    notalineas.textContent = personalizado
        ? (conpresupuesto
            ? 'Elija productos hasta completar el presupuesto. Los que ya no alcanzan aparecen desactivados.'
            : 'Elija los productos y el sistema calcula el total para cotizárselo al cliente.')
        : 'Opcional: chocolates, cintas, peluches, tarjetas u otros productos que el cliente quiera agregar al arreglo.';

    const express = casillaexpress.checked;
    campozona.hidden = !express;
    campodireccion.hidden = !express;
    notaexpress.textContent = express ? 'Se cobra el precio de la zona.' : 'Si no, el cliente lo recoge en el local.';

    dibujarlineas();
}

// Precio del arreglo: el guardado en la solicitud (si es el mismo arreglo) o el del catálogo
function precioarreglo() {
    const id = formulario.elements.tbarreglofloralid.value;
    if (datosservidor.tbarreglofloralid && String(datosservidor.tbarreglofloralid) === id) {
        return Number(datosservidor.tbsolicitudencargoprecioarreglo);
    }
    const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', id);
    return arreglo ? arreglo.tbarreglofloralprecio : 0;
}

function costoexpress() {
    if (!casillaexpress.checked) {
        return 0;
    }
    const id = formulario.elements.tbzonaentregaid.value;
    if (datosservidor.tbsolicitudencargoexpress === 1 && String(datosservidor.tbzonaentregaid) === id) {
        return Number(datosservidor.tbsolicitudencargocostoexpress);
    }
    const zona = buscar(opciones.zonas, 'tbzonaentregaid', id);
    return zona ? zona.tbzonaentregaprecio : 0;
}

// Cobro por flor pintada: el guardado en la solicitud (si es la misma pintura) o el de la pintura elegida
function preciopintura() {
    const id = formulario.elements.tbsolicitudencargosprayid.value;
    if (datosservidor.tbsolicitudencargosprayid && String(datosservidor.tbsolicitudencargosprayid) === id) {
        return Number(datosservidor.tbsolicitudencargopreciopintura);
    }
    const pintura = buscar(opciones.pinturas ?? [], 'tbinventarioid', id);
    return pintura ? pintura.cobroflor : 0;
}

// Flores y follajes que lleva lo que se está cotizando (las que se pueden pintar)
function florescotizadas() {
    let flores = 0;
    if (formulario.elements.tbsolicitudencargotipo.value === 'Predefinido') {
        const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
        flores += arreglo ? (arreglo.flores ?? 0) * (parseInt(formulario.elements.tbsolicitudencargocantidad.value, 10) || 1) : 0;
    }
    lineas.forEach((linea) => {
        const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
        if (producto && producto.tbinventariocategoria === 'Flor') {
            flores += parseInt(linea.cantidad, 10) || 0;
        }
    });
    return flores;
}

// Cuentas de la solicitud para el resumen y la cotización
function calcular() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    const cantidad = parseInt(formulario.elements.tbsolicitudencargocantidad.value, 10) || 0;
    const arreglo = tipo === 'Predefinido' ? precioarreglo() * cantidad : 0;
    const productos = sumaproductos();
    const express = costoexpress();
    const pintadas = formulario.elements.tbsolicitudencargosprayid.value
        ? parseInt(formulario.elements.tbsolicitudencargoflorespintadas.value, 10) || 0 : 0;
    const pintura = pintadas * preciopintura();

    return {
        tipo, cantidad, arreglo, productos, express, pintadas, pintura,
        subtotal: arreglo + productos, total: arreglo + productos + pintura + express,
    };
}

// Total de lo que se está cotizando con los precios normales o con los de temporada alta
function totalen(modo) {
    const cuentas = calcular();
    let total = cuentas.pintura + cuentas.express;

    if (cuentas.tipo === 'Predefinido') {
        const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
        total += arreglo ? (modo === 'alta' ? arreglo.precioalto : arreglo.precionormal) * cuentas.cantidad : 0;
    }
    lineas.forEach((linea) => {
        const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
        if (producto) {
            total += preciounidad(producto, modo) * (parseInt(linea.cantidad, 10) || 0);
        }
    });

    return total;
}

// Lo que cuesta lo que se está cotizando (para ver la ganancia)
function costocotizado() {
    let costo = 0;
    const campo = opciones.temporada ? 'costoalto' : 'costonormal';
    lineas.forEach((linea) => {
        const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
        if (producto) {
            costo += producto[campo] * (parseInt(linea.cantidad, 10) || 0);
        }
    });
    return costo;
}

// Cuántas flores se pueden pintar como máximo (null si todavía no se sabe)
function topeflores() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    if (!(tipo === 'Personalizado' || (tipo === 'Predefinido' && formulario.elements.tbarreglofloralid.value))) {
        return null;
    }
    // Con presupuesto, también hasta donde alcance después de los productos
    const tope = presupuesto();
    const cobro = preciopintura();
    if (tope !== null && tope > 0 && cobro > 0) {
        return Math.min(florescotizadas(), Math.max(0, Math.floor((tope - sumaproductos()) / cobro)));
    }
    return florescotizadas();
}

// Debajo de las flores pintadas: cuántas lleva el arreglo. No deja pintar más de las que hay.
function revisarpintura(cuentas) {
    const flores = florescotizadas();
    const tope = topeflores();
    const campo = formulario.elements.tbsolicitudencargoflorespintadas;
    const pintadas = parseInt(campo.value, 10) || 0;

    if (tope !== null && pintadas > tope) {
        campo.value = tope > 0 ? String(tope) : '';
    }

    notapintura.textContent = formulario.elements.tbsolicitudencargotipo.value === ''
        ? '' : flores === 0
            ? 'No lleva flores para pintar.'
            : tope !== null && tope < flores
                ? `Lleva ${flores} ${flores === 1 ? 'flor' : 'flores y follajes'}; con el presupuesto alcanza para pintar ${tope}.`
                : `Se pueden pintar hasta ${flores} ${flores === 1 ? 'flor' : 'flores y follajes'}.`;

    const mas = formulario.querySelector('[data-pasoflores="1"]');
    mas.disabled = tope !== null && (parseInt(campo.value, 10) || 0) >= tope;

    return cuentas;
}

function actualizarresumen() {
    // Primero las flores pintadas se ajustan al tope, para que las cuentas salgan bien
    const topepintura = topeflores();
    const campopintadas = formulario.elements.tbsolicitudencargoflorespintadas;
    if (topepintura !== null && (parseInt(campopintadas.value, 10) || 0) > topepintura) {
        campopintadas.value = topepintura > 0 ? String(topepintura) : '';
    }

    const cuentas = calcular();
    const filas = [];

    if (cuentas.tipo === 'Predefinido') {
        const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
        const nombre = arreglo ? arreglo.tbarreglofloralnombre : (datosservidor.tbarreglofloralnombre || 'Arreglo');
        filas.push([formulario.elements.tbarreglofloralid.value ? `${cuentas.cantidad} × ${nombre}` : 'Arreglo', formatearmonto(cuentas.arreglo)]);
        filas.push(['Adicionales', formatearmonto(cuentas.productos)]);
    } else if (cuentas.tipo === 'Personalizado') {
        filas.push([`Productos (costo + ${porcentaje()}%)`, formatearmonto(cuentas.productos)]);
    }

    if (cuentas.pintadas > 0) {
        filas.push([`Flores pintadas (${cuentas.pintadas} × ${montocorto(preciopintura())})`, formatearmonto(cuentas.pintura)]);
    }

    if (casillaexpress.checked) {
        const zona = buscar(opciones.zonas, 'tbzonaentregaid', formulario.elements.tbzonaentregaid.value);
        filas.push([`Express${zona ? ' a ' + zona.tbzonaentreganombre : ''}`, formatearmonto(cuentas.express)]);
    }
    revisarpintura(cuentas);

    // Con una temporada en uso, lo nuevo se cotiza con sus precios
    if (opciones.temporada) {
        filas.unshift(['Precios de temporada', escapar(opciones.temporada.tbtemporadanombre)]);
    }

    // Los dos precios: normal y temporada alta (el que se cobra ahora va marcado)
    let comparar = '';
    if (cuentas.tipo !== '') {
        const alta = Boolean(opciones.temporada);
        comparar = `
            <div class="resumen-comparar">
                <span class="${alta ? '' : 'precio-activo'}">Precio normal <strong>${montocorto(totalen('normal'))}</strong></span>
                <span class="${alta ? 'precio-activo' : ''}">Temporada alta <strong>${montocorto(totalen('alta'))}</strong></span>
            </div>`;
        // Ganancia del personalizado (solo el propietario ve el costo)
        if (cuentas.tipo === 'Personalizado' && espropietario && cuentas.productos > 0) {
            const costo = costocotizado();
            comparar += `<div class="resumen-ganancia">Costo de los productos ${formatearmonto(costo)} · Ganancia ${formatearmonto(cuentas.productos - costo)}</div>`;
        }
    }

    resumentotal.innerHTML = filas.map(([etiqueta, valor]) => `<div><dt>${escapar(etiqueta)}</dt><dd>${valor}</dd></div>`).join('')
        + `<div class="resumen-total"><dt>Total a cobrar</dt><dd>${formatearmonto(cuentas.total)}</dd></div>` + comparar;

    // Con presupuesto: cuánto queda o cuánto se pasa
    const tope = presupuesto();
    restopresupuesto.hidden = tope === null || tope <= 0;
    if (tope !== null && tope > 0) {
        const resto = tope - cuentas.productos - cuentas.pintura;
        const gasto = cuentas.pintura > 0
            ? `Productos ${montocorto(cuentas.productos)} + flores pintadas ${montocorto(cuentas.pintura)}. ` : '';
        restopresupuesto.textContent = resto >= 0
            ? `${gasto}Quedan ${formatearmonto(resto)} del presupuesto de ${formatearmonto(tope)}`
            : `${gasto}Se pasa del presupuesto por ${formatearmonto(-resto)}`;
        restopresupuesto.classList.toggle('texto-peligro', resto < 0);
    }
}

// Texto para pegar en WhatsApp con el detalle y el total
function textocotizacion() {
    const cuentas = calcular();
    const cliente = buscar(opciones.clientes, 'tbclienteid', formulario.elements.tbclienteid.value);
    const lineastexto = [];

    lineastexto.push(`Hola${cliente ? ' ' + cliente.tbclientenombre.split(' ')[0] : ''}, esta es la cotización de su arreglo:`);

    if (cuentas.tipo === 'Predefinido') {
        const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
        if (arreglo) {
            lineastexto.push(`- ${cuentas.cantidad} × ${arreglo.tbarreglofloralnombre}: ${montocorto(cuentas.arreglo)}`);
        }
    }

    lineas.filter((linea) => linea.tbinventarioid).forEach((linea) => {
        const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
        lineastexto.push(`- ${linea.cantidad} × ${producto ? producto.tbinventarionombre : linea.nombre}: ${montocorto(subtotallinea(linea))}`);
    });

    if (cuentas.pintadas > 0) {
        lineastexto.push(`- ${cuentas.pintadas} ${cuentas.pintadas === 1 ? 'flor pintada' : 'flores pintadas'}: ${montocorto(cuentas.pintura)}`);
    }

    if (casillaexpress.checked) {
        const zona = buscar(opciones.zonas, 'tbzonaentregaid', formulario.elements.tbzonaentregaid.value);
        lineastexto.push(`- Servicio express${zona ? ' a ' + zona.tbzonaentreganombre : ''}: ${montocorto(cuentas.express)}`);
    }

    lineastexto.push(`Total: ${montocorto(cuentas.total)}`);

    const fecha = formulario.elements.tbsolicitudencargofechaentrega.value;
    if (fecha) {
        lineastexto.push(`Entrega: ${formatearfecha(fecha)}`);
    }

    return lineastexto.join('\n');
}

/* ---------- Borrador ---------- */

function claveborrador() {
    const id = formulario.elements.tbsolicitudencargoid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function leerformulario() {
    const datos = {};
    camposformulario.forEach((campo) => {
        datos[campo] = formulario.elements[campo].value;
    });
    datos.tbsolicitudencargoexpress = casillaexpress.checked ? '1' : '';
    datos.lineas = lineas.map((linea) => ({ ...linea }));
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

// Resalta el porcentaje rápido que coincide con el que está escrito
function marcarporcentaje() {
    formulario.querySelectorAll('[data-porcentaje]').forEach((chip) => {
        chip.classList.toggle('chip-activo', Number(chip.dataset.porcentaje) === porcentaje()
            && formulario.elements.tbsolicitudencargoporcentaje.value !== '');
    });
}

function asignarcampos(datos) {
    camposformulario.forEach((campo) => {
        formulario.elements[campo].value = datos[campo] ?? '';
    });
    if (formulario.elements.tbsolicitudencargoporcentaje.value === '') {
        formulario.elements.tbsolicitudencargoporcentaje.value = String(opciones.porcentaje ?? 100);
    }
    marcarporcentaje();
    casillaexpress.checked = datos.tbsolicitudencargoexpress === '1';
    lineas = (datos.lineas ?? []).map((linea) => ({ ...linea }));
    actualizarsecciones();
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbsolicitudencargoid.value = datos.tbsolicitudencargoid ?? '';

    agregarinactivo(formulario.elements.tbclienteid, datos.tbclienteid, datos.tbclientenombre);
    agregarinactivo(formulario.elements.tbarreglofloralid, datos.tbarreglofloralid, datos.tbarreglofloralnombre);
    agregarinactivo(formulario.elements.tbzonaentregaid, datos.tbzonaentregaid, datos.tbzonaentreganombre);
    agregarinactivo(formulario.elements.tbsolicitudencargosprayid, datos.tbsolicitudencargosprayid, datos.tbsolicitudencargospraynombre);

    asignarcampos({
        tbclienteid: datos.tbclienteid ? String(datos.tbclienteid) : '',
        tbsolicitudencargotipo: datos.tbsolicitudencargotipo ?? '',
        tbarreglofloralid: datos.tbarreglofloralid ? String(datos.tbarreglofloralid) : '',
        tbsolicitudencargocantidad: String(datos.tbsolicitudencargocantidad ?? 1),
        tbsolicitudencargomodalidad: datos.tbsolicitudencargomodalidad ?? '',
        tbsolicitudencargopresupuesto: datos.tbsolicitudencargopresupuesto ? String(datos.tbsolicitudencargopresupuesto) : '',
        tbsolicitudencargoexpress: datos.tbsolicitudencargoexpress === 1 ? '1' : '',
        tbzonaentregaid: datos.tbzonaentregaid ? String(datos.tbzonaentregaid) : '',
        tbsolicitudencargodireccionentrega: datos.tbsolicitudencargodireccionentrega ?? '',
        tbsolicitudencargofechaentrega: datos.tbsolicitudencargofechaentrega ?? '',
        tbempleadoid: datos.tbempleadoid ? String(datos.tbempleadoid) : '',
        tbsolicitudencargonota: datos.tbsolicitudencargonota ?? '',
        tbsolicitudencargosprayid: datos.tbsolicitudencargosprayid ? String(datos.tbsolicitudencargosprayid) : '',
        tbsolicitudencargoflorespintadas: datos.tbsolicitudencargoflorespintadas ? String(datos.tbsolicitudencargoflorespintadas) : '',
        tbsolicitudencargoporcentaje: datos.tbsolicitudencargoporcentaje !== null && datos.tbsolicitudencargoporcentaje !== undefined
            ? String(Math.round(datos.tbsolicitudencargoporcentaje)) : String(opciones.porcentaje ?? 100),
        lineas: (datos.insumos ?? []).map((insumo) => ({
            tbinventarioid: String(insumo.tbinventarioid),
            cantidad: String(insumo.cantidad),
            nombre: insumo.tbinventarionombre,
            precioguardado: insumo.preciounitario,
        })),
    });

    datosoriginales = leerformulario();
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    if (JSON.stringify(borrador) === JSON.stringify(datosoriginales)) {
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
    const id = encargo.tbsolicitudencargoid;
    const esmio = encargo.tbempleadoid === usuarioactual.id;
    const acciones = [boton('ver', id, 'Ver')];

    // Empleado: tomar solicitudes libres y mover solo las suyas
    if (!espropietario) {
        if (encargo.tbsolicitudencargoestado === 'Pendiente' && !encargo.tbempleadoid) {
            acciones.push(boton('asignarme', id, 'Asignármela'));
        } else if (encargo.tbsolicitudencargoestado === 'Pendiente' && esmio) {
            acciones.push(boton('iniciar', id, 'Iniciar'));
        } else if (encargo.tbsolicitudencargoestado === 'En proceso' && esmio) {
            acciones.push(boton('terminar', id, 'Terminar'));
        }
        return acciones.join(' ');
    }

    if (encargo.tbsolicitudencargoestado === 'Pendiente') {
        acciones.push(boton('editar', id, 'Editar'));
        acciones.push(boton('asignar', id, encargo.tbempleadoid ? 'Reasignar' : 'Asignar'));
        if (!esmio) {
            acciones.push(boton('asignarme', id, 'Asignármela'));
        }
        if (encargo.tbempleadoid) {
            acciones.push(boton('iniciar', id, 'Iniciar'));
        }
        acciones.push(boton('cancelar', id, 'Cancelar', true));
    } else if (encargo.tbsolicitudencargoestado === 'En proceso') {
        acciones.push(boton('asignar', id, 'Reasignar'));
        acciones.push(boton('terminar', id, 'Terminar'));
        acciones.push(boton('cancelar', id, 'Cancelar', true));
    }

    return acciones.join(' ');
}

function textoentrega(encargo) {
    return encargo.tbsolicitudencargoexpress === 1
        ? `Express · ${encargo.tbzonaentreganombre || 'zona eliminada'}`
        : 'Recoge en el local';
}

async function cargarencargos() {
    const respuesta = await consultar('listar', {
        estado: filtroestado.value,
        empleado: filtroempleado.value,
        tipo: filtrotipo.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    encargos = respuesta.datos;

    if (encargos.length === 0) {
        const sinfiltros = filtroestado.value === '' && filtroempleado.value === '' && filtrotipo.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay solicitudes. Use "Nueva solicitud" para registrar la primera.'
            : 'No hay solicitudes que coincidan con el filtro.';
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = encargos.map((encargo) => {
        const abierto = ['Pendiente', 'En proceso'].includes(encargo.tbsolicitudencargoestado);
        const atrasado = abierto && encargo.tbsolicitudencargofechaentrega < hoy();
        const sinstock = abierto && !encargo.alcanzastock
            ? '<span class="aviso-stock" title="No alcanza el inventario para prepararla">Falta stock</span>'
            : '';
        const foto = encargo.tbsolicitudencargotipo === 'Personalizado' ? encargo.tbsolicitudencargofoto : encargo.tbarreglofloralfoto;
        const imagen = foto
            ? `<img src="${escapar(foto)}" alt="">`
            : '<span class="icono-personalizado" aria-hidden="true">✿</span>';
        const asignado = encargo.tbempleadoid ? escapar(encargo.tbempleadonombre) : 'Sin asignar';

        return `
            <tr class="${abierto ? '' : 'fila-inactiva'}">
                <td>${encargo.tbsolicitudencargoid}</td>
                <td>${escapar(encargo.tbclientenombre)}</td>
                <td>
                    <div class="celda-arreglo">
                        ${imagen}
                        <span>${escapar(encargo.descripcion)}${sinstock}</span>
                    </div>
                </td>
                <td class="${atrasado ? 'texto-peligro' : ''}">${formatearfecha(encargo.tbsolicitudencargofechaentrega)}${atrasado ? ' · Atrasada' : ''}<br><small class="texto-suave">${escapar(textoentrega(encargo))}</small></td>
                <td>${formatearmonto(encargo.tbsolicitudencargototal)}</td>
                <td><span class="estado ${claseestado(encargo.tbsolicitudencargoestado)}">${escapar(encargo.tbsolicitudencargoestado)}</span><br><small class="texto-suave">${asignado}</small></td>
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
        if (form.elements[campo] instanceof Element) {
            form.elements[campo].setAttribute('aria-invalid', 'true');
        }
    });

    const primero = form.querySelector('[aria-invalid="true"]') || form.querySelector('[data-error="insumos"]:not(:empty)');
    if (primero) {
        primero.scrollIntoView({ block: 'center' });
        if (primero.focus) {
            primero.focus();
        }
    }
}

/* ---------- Abrir formularios ---------- */

// Arriba del formulario: con qué precios se cotiza
function mostrartemporadaform(texto) {
    const avisotemporadaform = document.getElementById('avisotemporadaform');
    avisotemporadaform.textContent = texto;
    avisotemporadaform.hidden = texto === '';
}

async function abrirnuevo() {
    await opcioneslistas;
    datosservidor = { tbsolicitudencargofechaentrega: hoy() };
    llenarformulario(datosservidor);
    mostrartemporadaform(opciones.temporada
        ? `✿ Se cotiza con los precios de ${opciones.temporada.tbtemporadanombre}. Lo que no tiene precio para esta temporada va al precio normal.`
        : '');
    limpiarerrores();
    aplicarborrador();
    campoasignar.hidden = false;
    tituloformulario.textContent = 'Nueva solicitud de pedido de arreglo';
    botonguardar.textContent = 'Guardar solicitud';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    await opcioneslistas;
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosservidor = respuesta.datos;
    llenarformulario(datosservidor);
    mostrartemporadaform(datosservidor.tbtemporadanombre
        ? `✿ Esta solicitud se cotizó con los precios de ${datosservidor.tbtemporadanombre}.` : '');
    limpiarerrores();
    aplicarborrador();
    // Al editar, la asignación se cambia con el botón "Reasignar"
    campoasignar.hidden = true;
    tituloformulario.textContent = `Editar solicitud N.º ${id}`;
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

function abrirasignar(id) {
    const encargo = encargos.find((item) => String(item.tbsolicitudencargoid) === String(id));

    formularioasignar.reset();
    formularioasignar.elements.tbsolicitudencargoid.value = id;
    formularioasignar.elements.tbempleadoid.value = encargo && encargo.tbempleadoid ? String(encargo.tbempleadoid) : '';
    resumenasignar.textContent = encargo
        ? `Solicitud N.º ${id} de ${encargo.tbclientenombre}: ${encargo.descripcion}, entrega ${formatearfecha(encargo.tbsolicitudencargofechaentrega)}.`
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
        ['Cliente', `${encargo.tbclientenombre} · ${encargo.tbclientetelefono}`],
        ['Tipo', encargo.tbsolicitudencargotipo === 'Personalizado'
            ? `Personalizado (${encargo.tbsolicitudencargomodalidad === 'Presupuesto' ? 'presupuesto de ' + formatearmonto(encargo.tbsolicitudencargopresupuesto) : 'libre'}, `
                + `${encargo.tbsolicitudencargoporcentaje ?? 100}% de ganancia)`
            : 'Predefinido (del catálogo)'],
        ['Fecha de entrega', formatearfecha(encargo.tbsolicitudencargofechaentrega)],
        ['Entrega', encargo.tbsolicitudencargoexpress === 1
            ? `Express a ${encargo.tbzonaentreganombre}: ${encargo.tbsolicitudencargodireccionentrega}`
            : 'El cliente lo recoge en el local'],
        ['Total', formatearmonto(encargo.tbsolicitudencargototal)],
        ...(espropietario ? [
            ['Costo de flores y productos', formatearmonto(encargo.tbsolicitudencargocosto)],
            ['Ganancia', formatearmonto(encargo.tbsolicitudencargototal - encargo.tbsolicitudencargocosto)],
        ] : []),
        ['Cotizada con', encargo.tbtemporadanombre ? `Precios de ${encargo.tbtemporadanombre}` : 'Precios normales'],
        ['Asignado a', encargo.tbempleadoid ? `${encargo.tbempleadonombre}${encargo.tbempleadorol === 'Propietario' ? ' (Propietario)' : ''}` : 'Sin asignar'],
        ['Estado', encargo.tbsolicitudencargoestado],
        ['Registrada el', formatearfecha(encargo.tbsolicitudencargofecharegistro)],
        ['Nota', encargo.tbsolicitudencargonota],
    ];

    // Foto: la del arreglo del catálogo, o la que se le agregó al personalizado
    const personalizado = encargo.tbsolicitudencargotipo === 'Personalizado';
    const foto = personalizado ? encargo.tbsolicitudencargofoto : encargo.tbarreglofloralfoto;
    const puedefoto = personalizado && encargo.tbsolicitudencargoestado !== 'Cancelado'
        && (espropietario || encargo.tbempleadoid === usuarioactual.id);
    idfoto = encargo.tbsolicitudencargoid;
    fotodetalle.hidden = !foto;
    fotodetalle.src = foto || '';
    fotodetalle.alt = personalizado ? 'Foto del arreglo personalizado' : encargo.tbarreglofloralnombre;
    fotovacia.hidden = Boolean(foto) || !puedefoto;
    botonfoto.hidden = !puedefoto;
    notafoto.hidden = !puedefoto;
    botonfoto.textContent = foto ? 'Cambiar foto' : 'Agregar foto';
    errorfoto.textContent = '';
    errorfoto.className = 'error';
    columnafoto.hidden = !foto && !puedefoto;
    titulodetalle.textContent = `Solicitud N.º ${encargo.tbsolicitudencargoid}: ${encargo.descripcion}`;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

    // Lo que se le cobra al cliente
    const cobro = [];
    if (encargo.tbsolicitudencargotipo === 'Predefinido') {
        cobro.push([encargo.tbarreglofloralnombre, encargo.tbsolicitudencargocantidad, encargo.tbsolicitudencargoprecioarreglo,
            encargo.tbsolicitudencargocantidad * encargo.tbsolicitudencargoprecioarreglo]);
    }
    encargo.insumos.forEach((insumo) => {
        const etiqueta = encargo.tbsolicitudencargotipo === 'Predefinido' ? ' (adicional)' : '';
        cobro.push([insumo.tbinventarionombre + etiqueta, insumo.cantidad, insumo.preciounitario, insumo.subtotal]);
    });
    if (encargo.tbsolicitudencargoflorespintadas > 0) {
        cobro.push([`Flores pintadas (${encargo.tbsolicitudencargospraynombre})`, encargo.tbsolicitudencargoflorespintadas,
            encargo.tbsolicitudencargopreciopintura, encargo.tbsolicitudencargocostopintura]);
    }
    if (encargo.tbsolicitudencargoexpress === 1) {
        cobro.push([`Servicio express a ${encargo.tbzonaentreganombre}`, 1, encargo.tbsolicitudencargocostoexpress, encargo.tbsolicitudencargocostoexpress]);
    }

    cuerpocobro.innerHTML = cobro.map(([concepto, cantidad, precio, subtotal]) => `
        <tr>
            <td>${escapar(concepto)}</td>
            <td>${cantidad}</td>
            <td>${formatearmonto(precio)}</td>
            <td>${formatearmonto(subtotal)}</td>
        </tr>`).join('')
        + `<tr class="fila-total"><td colspan="3">Total</td><td>${formatearmonto(encargo.tbsolicitudencargototal)}</td></tr>`;

    cuerpodetalle.innerHTML = encargo.materiales.length === 0
        ? '<tr><td colspan="4" class="vacio">No hay productos registrados.</td></tr>'
        : encargo.materiales.map((material) => `
        <tr>
            <td>${escapar(material.tbinventarionombre)}</td>
            <td>${material.necesarias}</td>
            <td>${material.stock}</td>
            <td>${material.alcanza ? '<span class="estado estado-activo">Sí</span>' : `<span class="estado estado-bajo">Faltan ${material.necesarias - material.stock}</span>`}</td>
        </tr>`).join('');

    cuerpohistorial.innerHTML = encargo.historial.length === 0
        ? '<tr><td colspan="3" class="vacio">Esta solicitud no tiene historial registrado.</td></tr>'
        : encargo.historial.map((cambio) => `
            <tr>
                <td>${formatearfechahora(cambio.fecha)}</td>
                <td>${escapar(cambio.texto)}</td>
                <td>${cambio.hechopor ? escapar(cambio.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
            </tr>`).join('');

    if (!dialogodetalle.open) {
        dialogodetalle.showModal();
    }
}

// Sube la foto del personalizado y vuelve a mostrar el detalle con la foto nueva
async function subirfoto() {
    const archivo = campofoto.files[0];
    if (!archivo) {
        return;
    }

    if (archivo.size > 2 * 1024 * 1024) {
        errorfoto.textContent = 'La foto no puede pesar más de 2 MB.';
        campofoto.value = '';
        return;
    }

    const datos = new FormData();
    datos.append('id', idfoto);
    datos.append('tbsolicitudencargofoto', archivo);
    botonfoto.disabled = true;
    botonfoto.textContent = 'Subiendo…';

    const respuesta = await enviar('subirfoto', datos);
    botonfoto.disabled = false;
    campofoto.value = '';

    if (!respuesta.exito) {
        errorfoto.textContent = respuesta.mensaje;
        botonfoto.textContent = fotodetalle.hidden ? 'Agregar foto' : 'Cambiar foto';
        return;
    }

    await verdetalle(idfoto);
    errorfoto.textContent = respuesta.mensaje;
    errorfoto.className = 'texto-exito';
    cargarencargos();
}

/* ---------- Acciones de estado ---------- */

async function ejecutar(accion, id) {
    const encargo = encargos.find((item) => String(item.tbsolicitudencargoid) === String(id));
    const nombre = encargo ? `la solicitud N.º ${id} (${encargo.descripcion})` : `la solicitud N.º ${id}`;

    const configuraciones = {
        asignarme: {
            titulo: '¿Asignártela a ti?',
            mensaje: `Te vas a encargar de preparar ${nombre}.`,
            textoaceptar: 'Asignármela',
        },
        iniciar: {
            titulo: '¿Empezar a prepararla?',
            mensaje: `${nombre} pasará a "En proceso".`,
            textoaceptar: 'Iniciar',
        },
        terminar: {
            titulo: '¿Marcar como terminada?',
            mensaje: `Se descontarán del inventario los productos que usa ${nombre}.`,
            textoaceptar: 'Terminar',
        },
        cancelar: {
            titulo: '¿Cancelar la solicitud?',
            mensaje: `${nombre} quedará cancelada y ya no se podrá preparar.`,
            textoaceptar: 'Cancelar solicitud',
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

/* ---------- Eventos del formulario ---------- */

function limpiarerrorcampo(nombre) {
    const error = formulario.querySelector(`[data-error="${nombre}"]`);
    if (error) {
        error.textContent = '';
    }
    if (formulario.elements[nombre] instanceof Element) {
        formulario.elements[nombre].removeAttribute('aria-invalid');
    }
}

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    // Cambios dentro de la tabla de productos
    const fila = campo.closest('tr[data-indice]');
    if (fila) {
        const linea = lineas[Number(fila.dataset.indice)];
        if (campo.dataset.campo === 'cantidad') {
            linea.cantidad = campo.value.replace(/\D/g, '').slice(0, 3);
            actualizarfila(fila, linea);
        }
        return;
    }

    if (campo.name === 'tbsolicitudencargocantidad') {
        campo.value = campo.value.replace(/\D/g, '');
    }
    if (campo.name === 'tbsolicitudencargoflorespintadas') {
        campo.value = campo.value.replace(/\D/g, '').slice(0, 3);
        const tope = topeflores();
        if (tope !== null && (parseInt(campo.value, 10) || 0) > tope) {
            campo.value = tope > 0 ? String(tope) : '';
        }
    }
    // El % cambia el precio de cada producto del personalizado
    if (campo.name === 'tbsolicitudencargoporcentaje') {
        campo.value = campo.value.replace(/\D/g, '').slice(0, 3);
        if (Number(campo.value) > 500) {
            campo.value = '500';
        }
        marcarporcentaje();
        limpiarerrorcampo(campo.name);
        dibujarlineas();
        guardarborrador();
        return;
    }
    if (campo.name === 'tbsolicitudencargopresupuesto') {
        campo.value = filtrardecimal(campo.value);
        dibujarlineas();
    }

    limpiarerrorcampo(campo.name);
    actualizarresumen();
    guardarborrador();
});

formulario.addEventListener('change', (evento) => {
    const campo = evento.target;

    const fila = campo.closest('tr[data-indice]');
    if (fila && campo.dataset.campo === 'tbinventarioid') {
        const linea = lineas[Number(fila.dataset.indice)];
        linea.tbinventarioid = campo.value;
        linea.precioguardado = null;
        const producto = buscar(opciones.productos, 'tbinventarioid', campo.value);
        linea.nombre = producto ? producto.tbinventarionombre : '';
        formulario.querySelector('[data-error="insumos"]').textContent = '';
        dibujarlineas();
        guardarborrador();
        return;
    }

    if (['tbsolicitudencargotipo', 'tbsolicitudencargomodalidad', 'tbsolicitudencargoexpress'].includes(campo.name)) {
        // Al elegir express, se propone la dirección del cliente
        if (campo.name === 'tbsolicitudencargoexpress' && casillaexpress.checked
            && formulario.elements.tbsolicitudencargodireccionentrega.value.trim() === '') {
            const cliente = buscar(opciones.clientes, 'tbclienteid', formulario.elements.tbclienteid.value);
            formulario.elements.tbsolicitudencargodireccionentrega.value = cliente ? cliente.tbclientedireccion : '';
        }
        limpiarerrorcampo(campo.name);
        actualizarsecciones();
    }

    if (campo.name === 'tbclienteid' && casillaexpress.checked
        && formulario.elements.tbsolicitudencargodireccionentrega.value.trim() === '') {
        const cliente = buscar(opciones.clientes, 'tbclienteid', campo.value);
        formulario.elements.tbsolicitudencargodireccionentrega.value = cliente ? cliente.tbclientedireccion : '';
    }

    actualizarresumen();
    guardarborrador();
});

formulario.addEventListener('click', (evento) => {
    // + y − de las flores pintadas
    const pasoflores = evento.target.closest('button[data-pasoflores]');
    if (pasoflores) {
        const campo = formulario.elements.tbsolicitudencargoflorespintadas;
        const actual = parseInt(campo.value, 10) || 0;
        const tope = topeflores() ?? 999;
        campo.value = String(Math.min(tope, 999, Math.max(0, actual + Number(pasoflores.dataset.pasoflores))));
        limpiarerrorcampo('tbsolicitudencargoflorespintadas');
        actualizarresumen();
        guardarborrador();
        return;
    }

    // % de ganancia: + y − de 10 en 10, o uno de los porcentajes rápidos
    const pasoporcentaje = evento.target.closest('button[data-pasoporcentaje]');
    const chipporcentaje = evento.target.closest('button[data-porcentaje]');
    if (pasoporcentaje || chipporcentaje) {
        const campo = formulario.elements.tbsolicitudencargoporcentaje;
        campo.value = String(chipporcentaje
            ? Number(chipporcentaje.dataset.porcentaje)
            : Math.min(500, Math.max(0, porcentaje() + Number(pasoporcentaje.dataset.pasoporcentaje))));
        marcarporcentaje();
        limpiarerrorcampo('tbsolicitudencargoporcentaje');
        dibujarlineas();
        guardarborrador();
        return;
    }

    // + y − de la cantidad de arreglos
    const paso = evento.target.closest('button[data-paso]');
    if (paso) {
        const campo = formulario.elements.tbsolicitudencargocantidad;
        const actual = parseInt(campo.value, 10) || 0;
        campo.value = String(Math.min(99, Math.max(1, actual + Number(paso.dataset.paso))));
        limpiarerrorcampo('tbsolicitudencargocantidad');
        actualizarresumen();
        guardarborrador();
        return;
    }

    // + y − de un producto
    const pasolinea = evento.target.closest('button[data-pasolinea]');
    if (pasolinea) {
        const fila = pasolinea.closest('tr[data-indice]');
        const linea = lineas[Number(fila.dataset.indice)];
        const actual = parseInt(linea.cantidad, 10) || 0;
        let nueva = Math.min(999, Math.max(1, actual + Number(pasolinea.dataset.pasolinea)));

        // Con presupuesto, no deja subir si ya no alcanza
        const tope = presupuesto();
        if (tope !== null && nueva > actual && linea.tbinventarioid
            && gastopresupuesto() + preciolinea(linea) > tope) {
            nueva = actual;
            restopresupuesto.textContent = 'No alcanza el presupuesto para agregar otra unidad.';
            restopresupuesto.classList.add('texto-peligro');
            return;
        }

        linea.cantidad = String(nueva);
        actualizarfila(fila, linea);
        return;
    }

    const quitar = evento.target.closest('button[data-quitar]');
    if (quitar) {
        lineas.splice(Number(quitar.dataset.quitar), 1);
        dibujarlineas();
        guardarborrador();
    }
});

document.getElementById('botonagregarlinea').addEventListener('click', () => {
    lineas.push(lineavacia());
    formulario.querySelector('[data-error="insumos"]').textContent = '';
    dibujarlineas();
    const selects = cuerpolineas.querySelectorAll('select');
    if (selects.length > 0) {
        selects[selects.length - 1].focus();
    }
    guardarborrador();
});

document.getElementById('botoncopiar').addEventListener('click', async (evento) => {
    const botoncopiar = evento.currentTarget;
    try {
        await navigator.clipboard.writeText(textocotizacion());
        botoncopiar.textContent = '¡Copiada! Péguela en WhatsApp';
    } catch (error) {
        botoncopiar.textContent = 'No se pudo copiar';
    }
    setTimeout(() => {
        botoncopiar.textContent = 'Copiar cotización para WhatsApp';
    }, 2500);
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbsolicitudencargoid.value !== '';

    if (esedicion) {
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizará la solicitud N.º ${formulario.elements.tbsolicitudencargoid.value}. Total: ${formatearmonto(calcular().total)}.`,
            textoaceptar: 'Guardar cambios',
        });

        if (!aceptado) {
            return;
        }
    }

    const clave = claveborrador();
    const datos = new FormData(formulario);
    lineas.forEach((linea, indice) => {
        datos.append(`insumos[${indice}][tbinventarioid]`, linea.tbinventarioid);
        datos.append(`insumos[${indice}][cantidad]`, linea.cantidad);
    });

    limpiarerrores();
    botonguardar.disabled = true;

    const respuesta = await enviar(esedicion ? 'actualizar' : 'crear', datos);

    botonguardar.disabled = false;

    if (respuesta.exito) {
        borrarborrador(clave);
        avisoborrador.hidden = true;
        dialogoformulario.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargaropciones();
        cargarencargos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});

/* ---------- Asignar ---------- */

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
    const respuesta = await enviarid('asignarme', formularioasignar.elements.tbsolicitudencargoid.value);

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
filtrotipo.addEventListener('change', cargarencargos);
filtroempleado.addEventListener('change', cargarencargos);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncancelarasignar').addEventListener('click', () => dialogoasignar.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
botonfoto.addEventListener('click', () => campofoto.click());
campofoto.addEventListener('change', subirfoto);
document.getElementById('botondescartarborrador').addEventListener('click', () => {
    borrarborrador();
    llenarformulario(datosservidor);
    limpiarerrores();
    avisoborrador.hidden = true;
});

formulario.elements.tbsolicitudencargofechaentrega.min = hoy();

const opcioneslistas = cargaropciones();

// El filtro "Asignado a" se llena con los empleados, así que se espera a que estén cargados
opcioneslistas.finally(() => {
    filtrosdesdeurl({ estado: filtroestado, tipo: filtrotipo, empleado: filtroempleado });
    cargarencargos();
});
abrirformulariosipide(abrirnuevo);
