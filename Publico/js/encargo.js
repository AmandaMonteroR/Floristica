const rutabase = 'index.php?modulo=encargo';
const clavebase = 'floristica.borrador.encargo.';
const camposformulario = ['tbclienteid', 'tbsolicitudencargotipo', 'tbarreglofloralid', 'tbsolicitudencargocantidad',
    'tbsolicitudencargomodalidad', 'tbsolicitudencargopresupuesto', 'tbzonaentregaid', 'tbsolicitudencargodireccionentrega',
    'tbsolicitudencargofechaentrega', 'tbempleadoid', 'tbsolicitudencargonota',
    'tbsolicitudencargosprayid', 'tbsolicitudencargoflorespintadas', 'tbsolicitudencargoporcentaje',
    'tbsolicitudencargoreferencia'];
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
const filtroperiodo = document.getElementById('filtroperiodo');
const campofecha = document.getElementById('campofecha');
const filtrofecha = document.getElementById('filtrofecha');
const titulolistado = document.getElementById('titulolistado');
const aviso = document.getElementById('aviso');
const dialogoelegirencargo = document.getElementById('dialogoelegirencargo');
const dialogocatalogoencargo = document.getElementById('dialogocatalogoencargo');
const dialogoproductosencargo = document.getElementById('dialogoproductosencargo');
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
const cajareferencia = document.getElementById('cajareferencia');
const fotoreferencia = document.getElementById('fotoreferencia');
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
const figurareferencia = document.getElementById('figurareferencia');
const fotoreferenciadetalle = document.getElementById('fotoreferenciadetalle');
const columnaprecio = document.getElementById('columnaprecio');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpocobro = document.getElementById('cuerpocobro');
const cuerpodetalle = document.getElementById('cuerpodetalle');
// La sección de materiales cambia según la etapa del encargo.
const tablamateriales = cuerpodetalle.closest('.tabla-contenedor');
const titulomateriales = tablamateriales.previousElementSibling;
const encabezadomateriales = tablamateriales.querySelector('thead');
const nombremateriales = document.createElement('span');
const botonmateriales = document.createElement('button');
botonmateriales.type = 'button';
botonmateriales.className = 'enlace';
botonmateriales.hidden = true;
titulomateriales.replaceChildren(nombremateriales, ' ', botonmateriales);

botonmateriales.addEventListener('click', () => {
    tablamateriales.hidden = !tablamateriales.hidden;
    botonmateriales.textContent = tablamateriales.hidden ? 'Ver materiales' : 'Ocultar materiales';
});
const cuerpohistorial = document.getElementById('cuerpohistorial');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const casillaexpress = formulario.elements.tbsolicitudencargoexpress;

const espropietario = usuarioactual.rol === 'Propietario';

let temporizadoraviso;
let datosoriginales = {};
let datosservidor = {};
let opciones = { arreglos: [], empleados: [], clientes: [], zonas: [], productos: [], pinturas: [] };
let encargos = [];
let idfoto = null;
let lineas = [];

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearmonto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

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

/* ---------- Selección por fotos, con los mismos datos del catálogo e inventario ---------- */
function fotoencargo(url, nombre) {
    return url
        ? `<img src="${escapar(url)}" alt="${escapar(nombre)}" loading="lazy">`
        : '<span class="sin-foto-encargo" aria-label="Sin foto">✿</span>';
}

function mostrararregloelegido() {
    const id = formulario.elements.tbarreglofloralid.value;
    const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', id);
    const caja = document.getElementById('arregloelegido');
    caja.hidden = !arreglo;
    caja.innerHTML = arreglo
        ? `${fotoencargo(arreglo.tbarreglofloralfoto, arreglo.tbarreglofloralnombre)}
           <div><strong>${escapar(arreglo.tbarreglofloralnombre)}</strong>
           <small>${montocorto(arreglo.tbarreglofloralprecio)}</small></div>`
        : '';
    document.getElementById('botonelegirarreglo').textContent =
        arreglo ? 'Cambiar arreglo' : 'Buscar arreglo con foto';
}

function dibujarcatalogoencargo() {
    const texto = document.getElementById('buscararregloencargo').value.trim().toLocaleLowerCase('es');
    const elegido = formulario.elements.tbarreglofloralid.value;
    const lista = opciones.arreglos.filter((arreglo) =>
        arreglo.tbarreglofloralnombre.toLocaleLowerCase('es').includes(texto));
    document.getElementById('galeriaarreglosencargo').innerHTML = lista.length
        ? lista.map((arreglo) => `
            <button type="button" class="tarjeta-seleccion-encargo${String(arreglo.tbarreglofloralid) === elegido ? ' seleccionada' : ''}"
                    data-elegirarreglo="${arreglo.tbarreglofloralid}">
                ${fotoencargo(arreglo.tbarreglofloralfoto, arreglo.tbarreglofloralnombre)}
                <strong>${escapar(arreglo.tbarreglofloralnombre)}</strong>
                <span>${montocorto(arreglo.tbarreglofloralprecio)}</span>
            </button>`).join('')
        : '<p class="nota">No hay arreglos con ese nombre.</p>';
}

function dibujarproductosencargo() {
    const texto = document.getElementById('buscarproductoencargo').value.trim().toLocaleLowerCase('es');
    const categoria = document.getElementById('categoriaproductoencargo').value;
    const lista = opciones.productos.filter((producto) =>
        (!categoria || producto.tbinventariocategoria === categoria)
        && producto.tbinventarionombre.toLocaleLowerCase('es').includes(texto));
    document.getElementById('galeriaproductosencargo').innerHTML = lista.length
        ? lista.map((producto) => {
            const disponible = Number(producto.tbinventariodisponible) > 0;
            return `<button type="button" class="tarjeta-seleccion-encargo" data-elegirproducto="${producto.tbinventarioid}"
                            ${disponible ? '' : 'disabled'}>
                ${fotoencargo(producto.tbinventariofoto, producto.tbinventarionombre)}
                <strong>${escapar(producto.tbinventarionombre)}</strong>
                <span>${montocorto(preciounidad(producto))}</span>
                <small>${escapar(producto.tbinventariocategoria)}${disponible ? '' : ' · Sin stock disponible'}</small>
            </button>`;
        }).join('')
        : '<p class="nota">No hay productos que coincidan.</p>';
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
        if (opciones.fechamaxima) {
            formulario.elements.tbsolicitudencargofechaentrega.max = opciones.fechamaxima;
        }
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

    filtroempleado.innerHTML = '<option value="">Todos</option><option value="sinasignar">Sin asignar</option>';
    opciones.empleados.forEach((empleado) => {
        filtroempleado.add(new Option(nombreempleado(empleado), empleado.tbempleadoid));
    });
}

function agregarinactivo(select, id, texto) {
    if (id && !select.querySelector(`option[value="${id}"]`)) {
        select.add(new Option(`${texto} (inactivo)`, id));
    }
}

function lineavacia() {
    return { tbinventarioid: '', cantidad: '1', nombre: '', precioguardado: null };
}

function porcentaje() {
    const valor = parseInt(formulario.elements.tbsolicitudencargoporcentaje.value, 10);
    return Number.isNaN(valor) ? 100 : Math.min(500, Math.max(0, valor));
}

function precioporcentaje(costo, por = porcentaje()) {
    return Math.ceil(Math.round(costo * (1 + por / 100) * 100) / 100 / 10) * 10;
}

function preciounidad(producto, modo = opciones.temporada ? 'alta' : 'normal') {
    if (esPersonalizado() && !categoriasadicionales.includes(producto.tbinventariocategoria)) {
        return precioporcentaje(modo === 'alta' ? producto.costoalto : producto.costonormal);
    }
    return modo === 'alta' ? producto.ventaalta : producto.ventanormal;
}

function usaprecioguardado(linea) {
    if (linea.precioguardado === null || linea.precioguardado === undefined) {
        return false;
    }
    const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
    return (producto && categoriasadicionales.includes(producto.tbinventariocategoria))
        || !esPersonalizado() || (datosservidor.tbsolicitudencargotipo === 'Personalizado'
        && Number(datosservidor.tbsolicitudencargoporcentaje) === porcentaje());
}

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
            const extra = producto.tbinventariodisponible <= 0 ? ' (no hay disponible)' : nocabe ? ' (no alcanza)' : '';
            html += `<option value="${id}"${seleccionado ? ' selected' : ''}${desactivado ? ' disabled' : ''}>`
                + `${escapar(producto.tbinventarionombre)} · ${montocorto(preciounidad(producto))}${extra}</option>`;
        });
        html += '</optgroup>';
    });

    if (linea.tbinventarioid !== '' && !buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid)) {
        html += `<option value="${linea.tbinventarioid}" selected>${escapar(linea.nombre || 'Producto')} (inactivo)</option>`;
    }

    return html;
}

function detalleprecio(linea) {
    const producto = buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid);
    if (!esPersonalizado() || !espropietario || !producto || usaprecioguardado(linea)
        || categoriasadicionales.includes(producto.tbinventariocategoria)) {
        return '';
    }
    const costo = opciones.temporada ? producto.costoalto : producto.costonormal;
    return `<small class="detalle-precio">costo ${montocorto(costo)} + ${porcentaje()}%</small>`;
}

function dibujarlineas() {
    columnaprecio.textContent = 'Precio';
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

function actualizarfila(fila, linea) {
    fila.querySelector('[data-campo="cantidad"]').value = linea.cantidad;
    fila.querySelector('.subtotal-linea').textContent = linea.tbinventarioid ? formatearmonto(subtotallinea(linea)) : '—';
    formulario.querySelector('[data-error="insumos"]').textContent = '';
    if (presupuesto() !== null) {
        cuerpolineas.querySelectorAll('tr[data-indice]').forEach((otrafila) => {
            const otra = lineas[Number(otrafila.dataset.indice)];
            otrafila.querySelector('select').innerHTML = opcionesproducto(otra);
        });
    }
    actualizarresumen();
    guardarborrador();
}

function actualizarsecciones() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    const personalizado = tipo === 'Personalizado';
    const conpresupuesto = personalizado && formulario.elements.tbsolicitudencargomodalidad.value === 'Presupuesto';

    seccionpredefinido.hidden = tipo !== 'Predefinido';
    mostrararregloelegido();
    seccionpersonalizado.hidden = !personalizado;
    seccionlineas.hidden = tipo === '';
    seccionpintura.hidden = tipo === '' || ((opciones.pinturas ?? []).length === 0 && !formulario.elements.tbsolicitudencargosprayid.value);
    campopresupuesto.hidden = !conpresupuesto;

    const referencia = formulario.elements.tbsolicitudencargoreferencia.value;
    cajareferencia.hidden = !personalizado || referencia === '';
    if (referencia !== '' && fotoreferencia.getAttribute('src') !== referencia) {
        fotoreferencia.src = referencia;
    }

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

function preciopintura() {
    const id = formulario.elements.tbsolicitudencargosprayid.value;
    if (datosservidor.tbsolicitudencargosprayid && String(datosservidor.tbsolicitudencargosprayid) === id) {
        return Number(datosservidor.tbsolicitudencargopreciopintura);
    }
    const pintura = buscar(opciones.pinturas ?? [], 'tbinventarioid', id);
    return pintura ? pintura.cobroflor : 0;
}

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

function topeflores() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    if (!(tipo === 'Personalizado' || (tipo === 'Predefinido' && formulario.elements.tbarreglofloralid.value))) {
        return null;
    }
    const tope = presupuesto();
    const cobro = preciopintura();
    if (tope !== null && tope > 0 && cobro > 0) {
        return Math.min(florescotizadas(), Math.max(0, Math.floor((tope - sumaproductos()) / cobro)));
    }
    return florescotizadas();
}

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

function mostrarlleva() {
    const caja = document.getElementById('llevaarreglo');
    const arreglo = formulario.elements.tbsolicitudencargotipo.value === 'Predefinido'
        ? buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value) : null;
    caja.hidden = !arreglo;
    if (!arreglo) {
        return;
    }
    const cantidad = parseInt(formulario.elements.tbsolicitudencargocantidad.value, 10) || 1;
    const foto = document.getElementById('fotollevaarreglo');
    foto.hidden = !arreglo.tbarreglofloralfoto;
    foto.src = arreglo.tbarreglofloralfoto || '';
    foto.alt = arreglo.tbarreglofloralnombre;
    document.getElementById('titulollevaarreglo').textContent = cantidad > 1 ? 'Cada arreglo lleva' : 'Lleva';
    document.getElementById('listallevaarreglo').innerHTML = (arreglo.lleva ?? [])
        .map((item) => `<li>${item.cantidad} × ${escapar(item.nombre)}</li>`).join('');
}

function actualizarresumen() {
    mostrarlleva();
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
        filas.push(['Productos del arreglo y adicionales', formatearmonto(cuentas.productos)]);
    }

    if (cuentas.pintadas > 0) {
        filas.push([`Flores pintadas (${cuentas.pintadas} × ${montocorto(preciopintura())})`, formatearmonto(cuentas.pintura)]);
    }

    if (casillaexpress.checked) {
        const zona = buscar(opciones.zonas, 'tbzonaentregaid', formulario.elements.tbzonaentregaid.value);
        filas.push([`Express${zona ? ' a ' + zona.tbzonaentreganombre : ''}`, formatearmonto(cuentas.express)]);
    }
    revisarpintura(cuentas);

    if (opciones.temporada) {
        filas.unshift(['Precios de temporada', escapar(opciones.temporada.tbtemporadanombre)]);
    }

    let comparar = '';
    if (cuentas.tipo !== '') {
        const alta = Boolean(opciones.temporada);
        comparar = `
            <div class="resumen-comparar">
                <span class="${alta ? '' : 'precio-activo'}">Precio normal <strong>${montocorto(totalen('normal'))}</strong></span>
                <span class="${alta ? 'precio-activo' : ''}">Temporada alta <strong>${montocorto(totalen('alta'))}</strong></span>
            </div>`;
        if (cuentas.tipo === 'Personalizado' && espropietario && cuentas.productos > 0) {
            const costo = costocotizado();
            comparar += `<div class="resumen-ganancia">Costo de los productos ${formatearmonto(costo)} · Ganancia ${formatearmonto(cuentas.productos - costo)}</div>`;
        }
    }

    resumentotal.innerHTML = filas.map(([etiqueta, valor]) => `<div><dt>${escapar(etiqueta)}</dt><dd>${valor}</dd></div>`).join('')
        + `<div class="resumen-total"><dt>Total a cobrar</dt><dd>${formatearmonto(cuentas.total)}</dd></div>` + comparar;

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

const categoriasadicionales = ['Chocolate', 'Peluche', 'Tarjeta', 'Accesorio'];

const negocio = { nombre: 'Floristica' };

function datosproforma() {
    const cuentas = calcular();
    const renglones = [];
    const conproducto = lineas.filter((linea) => linea.tbinventarioid).map((linea) => ({
        linea, producto: buscar(opciones.productos, 'tbinventarioid', linea.tbinventarioid),
    }));
    const nombrede = (item) => (item.producto ? item.producto.tbinventarionombre : item.linea.nombre || 'Producto');
    const agregar = (renglon) => {
        renglon.subtotal = Math.round(renglon.subtotal);
        renglones.push(renglon);
        return renglon;
    };
    let adicionales = conproducto;
    let ramo = null;

    if (cuentas.tipo === 'Predefinido') {
        const arreglo = buscar(opciones.arreglos, 'tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
        if (arreglo) {
            agregar({
                foto: arreglo.tbarreglofloralfoto, nombre: arreglo.tbarreglofloralnombre,
                titulolleva: cuentas.cantidad > 1 ? 'Cada uno lleva' : 'Lleva',
                lleva: (arreglo.lleva ?? []).map((item) => `${item.cantidad} × ${item.nombre}`),
                cantidad: cuentas.cantidad, precio: precioarreglo(), subtotal: cuentas.arreglo, principal: true,
            });
        }
    } else if (cuentas.tipo === 'Personalizado') {
        const esadicional = (item) => Boolean(item.producto) && categoriasadicionales.includes(item.producto.tbinventariocategoria);
        const delramo = conproducto.filter((item) => !esadicional(item));
        adicionales = conproducto.filter(esadicional);
        if (delramo.length > 0) {
            ramo = agregar({
                foto: formulario.elements.tbsolicitudencargoreferencia.value, nombre: 'Ramo personalizado',
                titulolleva: 'Lleva', lleva: delramo.map((item) => `${item.linea.cantidad} × ${nombrede(item)}`),
                cantidad: 1, precio: 0, subtotal: 0, principal: true,
            });
        }
    }

    adicionales.forEach((item) => agregar({
        foto: item.producto ? item.producto.tbinventariofoto : '',
        nombre: nombrede(item), cantidad: parseInt(item.linea.cantidad, 10) || 0,
        precio: preciolinea(item.linea), subtotal: subtotallinea(item.linea), adicional: true,
    }));

    if (cuentas.pintadas > 0) {
        const pintura = buscar(opciones.pinturas ?? [], 'tbinventarioid', formulario.elements.tbsolicitudencargosprayid.value);
        agregar({
            nombre: cuentas.pintadas === 1 ? 'Flor pintada' : 'Flores pintadas',
            detalle: pintura ? pintura.tbinventarionombre : '', cantidad: cuentas.pintadas, precio: preciopintura(), subtotal: cuentas.pintura, servicio: true,
        });
    }

    if (casillaexpress.checked) {
        const zona = buscar(opciones.zonas, 'tbzonaentregaid', formulario.elements.tbzonaentregaid.value);
        agregar({
            nombre: 'Servicio express', detalle: zona ? `Entrega en ${zona.tbzonaentreganombre}` : '',
            cantidad: 1, precio: cuentas.express, subtotal: cuentas.express, servicio: true,
        });
    }

    let total = renglones.reduce((suma, renglon) => suma + renglon.subtotal, 0);
    if (ramo) {
        total = Math.round(cuentas.total);
        ramo.subtotal = total - renglones.filter((renglon) => renglon !== ramo).reduce((suma, renglon) => suma + renglon.subtotal, 0);
        ramo.precio = ramo.subtotal;
    }

    return { renglones, total, cuentas };
}

function faltaparaproforma() {
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    if (tipo === '') {
        return 'Elija el tipo de encargo para ver la proforma.';
    }
    if (tipo === 'Predefinido' && !formulario.elements.tbarreglofloralid.value) {
        return 'Elija el arreglo del catálogo para ver la proforma.';
    }
    if (tipo === 'Personalizado' && !lineas.some((linea) => linea.tbinventarioid)) {
        return 'Agregue los productos del arreglo para ver la proforma.';
    }
    return '';
}

function fotoproforma(renglon) {
    if (renglon.foto) {
        return `<img src="${escapar(renglon.foto)}" alt="${escapar(renglon.nombre)}">`;
    }
    return renglon.servicio ? '' : '<span class="proforma-sinfoto">Sin foto</span>';
}

function dibujarproforma() {
    const { renglones, total, cuentas } = datosproforma();
    const cliente = buscar(opciones.clientes, 'tbclienteid', formulario.elements.tbclienteid.value);
    const numero = formulario.elements.tbsolicitudencargoid.value;
    const fecha = formulario.elements.tbsolicitudencargofechaentrega.value;
    const nota = formulario.elements.tbsolicitudencargonota.value.trim();
    const zona = buscar(opciones.zonas, 'tbzonaentregaid', formulario.elements.tbzonaentregaid.value);
    const direccion = formulario.elements.tbsolicitudencargodireccionentrega.value.trim();
    const entrega = casillaexpress.checked
        ? `A domicilio${zona ? ` (${zona.tbzonaentreganombre})` : ''}${direccion ? `: ${direccion}` : ''}`
        : 'Se retira en el local';
    const datos = [
        ['Cliente', cliente ? cliente.tbclientenombre : 'Sin elegir'],
        ['Teléfono', cliente ? cliente.tbclientetelefono : ''],
        ['Fecha de entrega', fecha ? formatearfecha(fecha) : 'Por definir'],
        ['Entrega', entrega],
    ].filter(([, valor]) => valor);

    const filas = renglones.map((renglon) => `
        <tr class="${renglon.principal ? 'renglon-principal' : ''}">
            <td class="proforma-foto">${fotoproforma(renglon)}</td>
            <td>
                <strong>${escapar(renglon.nombre)}</strong>
                ${renglon.adicional ? '<span class="proforma-etiqueta">Adicional</span>' : ''}
                ${renglon.detalle ? `<small>${escapar(renglon.detalle)}</small>` : ''}
                ${renglon.lleva && renglon.lleva.length
                    ? `<small>${renglon.titulolleva}: ${renglon.lleva.map(escapar).join(', ')}</small>` : ''}
            </td>
            <td class="numero">${renglon.cantidad}</td>
            <td class="numero">${montocorto(renglon.precio)}</td>
            <td class="numero">${montocorto(renglon.subtotal)}</td>
        </tr>`).join('');

    document.getElementById('hojaproforma').innerHTML = `
        <header class="proforma-encabezado">
            <div class="proforma-marca">
                <img src="imagenes/logo.png" alt="">
                <div>
                    <strong>${escapar(negocio.nombre)}</strong>
                </div>
            </div>
            <div class="proforma-titulo">
                <strong>Proforma</strong>
                ${numero ? `<span>Encargo N.º ${escapar(numero)}</span>` : ''}
                <span>Fecha: ${formatearfecha(hoy())}</span>
            </div>
        </header>
        <dl class="proforma-datos">
            ${datos.map(([etiqueta, valor]) => `<div><dt>${etiqueta}</dt><dd>${escapar(valor)}</dd></div>`).join('')}
        </dl>
        <table class="proforma-tabla">
            <thead>
                <tr>
                    <th><span class="solo-lector">Foto</span></th>
                    <th>Descripción</th>
                    <th class="numero">Cant.</th>
                    <th class="numero">Precio</th>
                    <th class="numero">Subtotal</th>
                </tr>
            </thead>
            <tbody>${filas}</tbody>
        </table>
        <p class="proforma-total"><span>Total a pagar</span> <strong>${montocorto(total)}</strong></p>
        ${nota ? `<p class="proforma-nota"><strong>Indicaciones:</strong> ${escapar(nota)}</p>` : ''}
        <footer class="proforma-pie">
            ${opciones.temporada ? `<p>Precios de ${escapar(opciones.temporada.tbtemporadanombre)}.</p>` : ''}
            <p>Montos en colones. Esta proforma no es una factura.${cuentas.tipo === 'Personalizado'
                ? ' Las flores pueden variar un poco según lo que haya ese día.' : ''}</p>
            <p class="proforma-gracias">¡Gracias por preferirnos!</p>
        </footer>`;

    const nombrecliente = cliente ? ` ${cliente.tbclientenombre}` : '';
    dialogoproforma.dataset.titulo = `Proforma${numero ? ' N.º ' + numero : ''}${nombrecliente} ${formatearfecha(hoy()).replace(/\//g, '-')}`;

    const enlace = document.getElementById('enlacewhatsapp');
    const digitos = cliente ? String(cliente.tbclientetelefono).replace(/\D/g, '') : '';
    enlace.hidden = digitos === '';
    enlace.href = digitos ? `https://wa.me/506${digitos.slice(-8)}` : '#';
}

const dialogoproforma = document.getElementById('dialogoproforma');

function abrirproforma() {
    const falta = faltaparaproforma();
    if (falta !== '') {
        avisoformulario.textContent = falta;
        avisoformulario.hidden = false;
        avisoformulario.scrollIntoView({ block: 'nearest' });
        return;
    }
    avisoformulario.hidden = true;
    document.getElementById('avisoproforma').hidden = true;
    dibujarproforma();
    dialogoproforma.showModal();
    dialogoproforma.scrollTop = 0;
}

function esperarfotos(contenedor) {
    return Promise.all([...contenedor.querySelectorAll('img')].map((imagen) => (imagen.complete
        ? Promise.resolve()
        : new Promise((listo) => {
            imagen.addEventListener('load', listo, { once: true });
            imagen.addEventListener('error', listo, { once: true });
        }))));
}

async function descargarproformapdf() {
    const boton = document.getElementById('botonpdfproforma');
    const avisoproforma = document.getElementById('avisoproforma');
    avisoproforma.hidden = true;

    if (typeof window.html2canvas !== 'function' || !window.jspdf) {
        avisoproforma.textContent = 'No se pudo cargar lo necesario para generar el PDF. Recargue la página.';
        avisoproforma.hidden = false;
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Generando PDF…';

    try {
        const hoja = document.getElementById('hojaproforma');
        await esperarfotos(hoja);

        const ancho = 760;
        const escala = 2;
        let cortes = [];
        const lienzo = await window.html2canvas(hoja, {
            scale: escala,
            backgroundColor: '#ffffff',
            windowWidth: 1200,
            logging: false,
            onclone: (copia) => {
                const hojacopia = copia.getElementById('hojaproforma');
                hojacopia.style.width = `${ancho}px`;
                hojacopia.style.padding = '0';
                hojacopia.style.boxShadow = 'none';
                hojacopia.style.borderRadius = '0';
                const arriba = hojacopia.getBoundingClientRect().top;
                cortes = [...hojacopia.querySelectorAll('.proforma-datos, .proforma-tabla thead, .proforma-tabla tbody tr, '
                    + '.proforma-total, .proforma-nota, .proforma-pie')]
                    .map((elemento) => (elemento.getBoundingClientRect().top - arriba) * escala);
            },
        });

        const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'letter' });
        const margen = 36;
        const anchopagina = pdf.internal.pageSize.getWidth() - margen * 2;
        const altopagina = pdf.internal.pageSize.getHeight() - margen * 2;
        const factor = anchopagina / lienzo.width;
        const cabe = Math.floor(altopagina / factor);

        let inicio = 0;
        while (inicio < lienzo.height - 1) {
            let fin = Math.min(lienzo.height, inicio + cabe);
            if (fin < lienzo.height) {
                const corte = cortes.filter((punto) => punto > inicio + 10 && punto <= fin).pop();
                if (corte) {
                    fin = corte;
                }
            }

            const pedazo = document.createElement('canvas');
            pedazo.width = lienzo.width;
            pedazo.height = Math.ceil(fin - inicio);
            const contexto = pedazo.getContext('2d');
            contexto.fillStyle = '#ffffff';
            contexto.fillRect(0, 0, pedazo.width, pedazo.height);
            contexto.drawImage(lienzo, 0, -inicio);

            if (inicio > 0) {
                pdf.addPage();
            }
            pdf.addImage(pedazo.toDataURL('image/jpeg', 0.92), 'JPEG', margen, margen, anchopagina, pedazo.height * factor);
            inicio = fin;
        }

        pdf.setProperties({ title: dialogoproforma.dataset.titulo || 'Proforma', creator: negocio.nombre });
        pdf.save(`${dialogoproforma.dataset.titulo || 'Proforma'}.pdf`);
    } catch (error) {
        avisoproforma.textContent = 'No se pudo generar el PDF. Intente de nuevo o use "Imprimir".';
        avisoproforma.hidden = false;
    } finally {
        boton.disabled = false;
        boton.textContent = 'Descargar PDF';
    }
}

function imprimirproforma() {
    const anterior = document.title;
    document.title = dialogoproforma.dataset.titulo || 'Proforma';
    window.addEventListener('afterprint', () => {
        document.title = anterior;
    }, { once: true });
    window.print();
}

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
        tbsolicitudencargoreferencia: datos.tbsolicitudencargoreferencia ?? '',
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

function claseestado(estado) {
    return {
        Pendiente: 'estado-pendiente',
        'En proceso': 'estado-confirmado',
        Terminado: 'estado-recibido',
        Entregado: 'estado-activo',
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

    if (!espropietario) {
        const abierta = ['Pendiente', 'En proceso'].includes(encargo.tbsolicitudencargoestado);
        if (encargo.tbsolicitudencargoestado === 'Pendiente' && !encargo.tbempleadoid) {
            acciones.push(boton('asignarme', id, 'Asignármela'));
        } else if (encargo.tbsolicitudencargoestado === 'Pendiente' && esmio) {
            acciones.push(boton('iniciar', id, 'Iniciar'));
        } else if (encargo.tbsolicitudencargoestado === 'En proceso' && esmio) {
            acciones.push(boton('terminar', id, 'Terminar'));
        }
        if (abierta && (!encargo.tbempleadoid || esmio)) {
            acciones.push(boton('asignar', id, esmio ? 'Pasar a otra persona' : 'Asignar'));
        }
        if (encargo.tbsolicitudencargoestado === 'Terminado') {
            acciones.push(boton('entregar', id, 'Marcar como entregado'));
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
        acciones.push(boton('cancelar', id, 'Cancelar encargo', true));
    } else if (encargo.tbsolicitudencargoestado === 'En proceso') {
        acciones.push(boton('asignar', id, 'Reasignar'));
        acciones.push(boton('terminar', id, 'Terminar'));
        acciones.push(boton('cancelar', id, 'Cancelar encargo', true));
    } else if (encargo.tbsolicitudencargoestado === 'Terminado') {
        acciones.push(boton('entregar', id, 'Marcar como entregado'));
    }

    return acciones.join(' ');
}

// En la tabla basta con "Ver"; todas las acciones están en el detalle.
function accionesencargotabla(encargo) {
    return boton('ver', encargo.tbsolicitudencargoid, 'Ver');
}

function textoentrega(encargo) {
    return encargo.tbsolicitudencargoexpress === 1
        ? `Express · ${encargo.tbzonaentreganombre || 'zona eliminada'}`
        : 'Recoge en el local';
}

// El resumen siempre corresponde a los encargos programados para hoy,
// independientemente de los filtros usados en la tabla.
function actualizarresumenencargos(lista) {
    const deldia = lista.filter((encargo) =>
        encargo.tbsolicitudencargofechaentrega === hoy()
        && encargo.tbsolicitudencargoestado !== 'Cancelado');

    const porpreparar = deldia.filter((encargo) =>
        ['Pendiente', 'En proceso'].includes(encargo.tbsolicitudencargoestado));
    const listos = deldia.filter((encargo) => encargo.tbsolicitudencargoestado === 'Terminado');
    const entregados = deldia.filter((encargo) => encargo.tbsolicitudencargoestado === 'Entregado');

    document.getElementById('totalencargoshoy').textContent = deldia.length;
    document.getElementById('porprepararhoy').textContent = porpreparar.length;
    document.getElementById('listosporentregarhoy').textContent = listos.length;
    document.getElementById('entregadoshoy').textContent = entregados.length;
}

function actualizarfechaencargos() {
    const periodo = filtroperiodo.value;
    campofecha.hidden = periodo !== 'personalizada';

    if (periodo === 'personalizada' && !filtrofecha.value) {
        filtrofecha.value = hoy();
    }

    titulolistado.textContent = periodo === 'hoy' ? 'Encargos de hoy'
        : periodo === 'todas' ? 'Todos los encargos'
            : `Encargos del ${formatearfecha(filtrofecha.value)}`;
}

function coincidefechaencargo(encargo) {
    if (filtroperiodo.value === 'todas') {
        return true;
    }

    const fecha = filtroperiodo.value === 'hoy' ? hoy() : filtrofecha.value;
    return encargo.tbsolicitudencargofechaentrega === fecha;
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

    // El resumen consulta todos los estados; no depende de las opciones de la tabla.
    const sinfiltros = filtroestado.value === '' && filtroempleado.value === '' && filtrotipo.value === '';
    if (sinfiltros) {
        actualizarresumenencargos(respuesta.datos);
    } else {
        const resumen = await consultar('listar');
        if (resumen.exito) {
            actualizarresumenencargos(resumen.datos);
        }
    }

    actualizarfechaencargos();
    encargos = respuesta.datos.filter(coincidefechaencargo);

    if (encargos.length === 0) {
        const mensaje = sinfiltros && filtroperiodo.value === 'hoy'
            ? 'No hay encargos programados para hoy.'
            : sinfiltros && filtroperiodo.value === 'todas'
                ? 'Todavía no hay encargos. Use "Nuevo encargo" para registrar el primero.'
                : 'No hay encargos que coincidan con los filtros.';
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = encargos.map((encargo) => {
        const abierto = ['Pendiente', 'En proceso'].includes(encargo.tbsolicitudencargoestado);
        const atrasado = abierto && encargo.tbsolicitudencargofechaentrega < hoy();
        const sinstock = abierto && !encargo.alcanzastock
            ? '<span class="aviso-stock" title="No alcanza el inventario para prepararla">Falta stock</span>'
            : '';
        const foto = encargo.tbsolicitudencargotipo === 'Personalizado'
            ? encargo.tbsolicitudencargofoto || encargo.tbsolicitudencargoreferencia : encargo.tbarreglofloralfoto;
        const imagen = foto
            ? `<img src="${escapar(foto)}" alt="">`
            : '<span class="icono-personalizado" aria-hidden="true">✿</span>';
        const asignado = encargo.tbempleadoid ? escapar(encargo.tbempleadonombre) : 'Sin asignar';

        return `
            <tr class="${encargo.tbsolicitudencargoestado === 'Cancelado' ? 'fila-inactiva' : ''}">
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
                <td class="columna-acciones">${accionesencargotabla(encargo)}</td>
            </tr>`;
    }).join('');
}

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

function mostrartemporadaform(texto) {
    const avisotemporadaform = document.getElementById('avisotemporadaform');
    avisotemporadaform.textContent = texto;
    avisotemporadaform.hidden = texto === '';
}

async function abrirnuevo(tipo = '') {
    await opcioneslistas;
    datosservidor = { tbsolicitudencargofechaentrega: hoy() };
    llenarformulario(datosservidor);
    mostrartemporadaform(opciones.temporada
        ? `✿ Se cotiza con los precios de ${opciones.temporada.tbtemporadanombre}. Lo que no tiene precio para esta temporada va al precio normal.`
        : '');
    limpiarerrores();
    aplicarborrador();
    if (tipo !== '' && formulario.elements.tbsolicitudencargotipo.value !== tipo) {
        formulario.elements.tbsolicitudencargotipo.value = tipo;
        actualizarsecciones();
    }
    campoasignar.hidden = false;
    const tipoactual = formulario.elements.tbsolicitudencargotipo.value;
    tituloformulario.textContent = tipoactual === 'Predefinido' ? 'Nuevo encargo del catálogo'
        : tipoactual === 'Personalizado' ? 'Nuevo encargo personalizado' : 'Nuevo encargo';

    // Al volver de registrar un cliente se recupera el borrador y se selecciona al nuevo.
    const parametros = new URLSearchParams(window.location.search);
    const idcliente = parametros.get('clientecreado');
    if (idcliente && /^\d+$/.test(idcliente)) {
        if (buscar(opciones.clientes, 'tbclienteid', idcliente)) {
            formulario.elements.tbclienteid.value = idcliente;
            avisoborrador.hidden = true;
            actualizarresumen();
            guardarborrador();
        } else {
            mostraraviso('El cliente se guardó, pero no aparece en la lista. Actualice la página.', 'error');
        }
        parametros.delete('clientecreado');
        window.history.replaceState(null, '', `${window.location.pathname}?${parametros}`);
    }
    botonguardar.textContent = 'Guardar encargo';
    dialogoformulario.showModal();
    // Al escoger Catálogo, abre directamente las fotos para elegir el ramo.
    if (tipo === 'Predefinido' && !formulario.elements.tbarreglofloralid.value) {
        document.getElementById('buscararregloencargo').value = '';
        dibujarcatalogoencargo();
        dialogocatalogoencargo.showModal();
    }
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
        ? `✿ Este encargo se cotizó con los precios de ${datosservidor.tbtemporadanombre}.` : '');
    limpiarerrores();
    aplicarborrador();
    campoasignar.hidden = true;
    tituloformulario.textContent = `Editar encargo N.º ${id}`;
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

function abrirasignar(id) {
    const encargo = encargos.find((item) => String(item.tbsolicitudencargoid) === String(id));

    formularioasignar.reset();
    formularioasignar.elements.tbsolicitudencargoid.value = id;
    formularioasignar.elements.tbempleadoid.value = encargo && encargo.tbempleadoid ? String(encargo.tbempleadoid) : '';
    resumenasignar.textContent = encargo
        ? `Encargo N.º ${id} de ${encargo.tbclientenombre}: ${encargo.descripcion}, entrega ${formatearfecha(encargo.tbsolicitudencargofechaentrega)}.`
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
        ...(encargo.tbsolicitudencargoestado === 'Entregado' && encargo.tbsolicitudencargofechaentregareal
            ? [['Entregado el', formatearfechahora(encargo.tbsolicitudencargofechaentregareal)]] : []),
        ['Lugar de entrega', encargo.tbsolicitudencargoexpress === 1
            ? `A domicilio · ${encargo.tbzonaentreganombre || 'Zona sin registrar'}${encargo.tbsolicitudencargodireccionentrega ? ' — ' + encargo.tbsolicitudencargodireccionentrega : ''}`
            : 'Retiro en el local'],
        ['Total', formatearmonto(encargo.tbsolicitudencargototal)],
        ['Pagado', encargo.abonado > 0
            ? `${formatearmonto(encargo.abonado)}${encargo.saldo > 0 ? ` · falta ${formatearmonto(encargo.saldo)}` : ' (pago completo)'}`
            : 'Todavía no ha pagado'],
        ...(espropietario ? [
            ['Costo de flores y productos', formatearmonto(encargo.tbsolicitudencargocosto)],
            ['Ganancia', formatearmonto(encargo.tbsolicitudencargototal - encargo.tbsolicitudencargocosto)],
        ] : []),
        ['Cotizada con', encargo.tbtemporadanombre ? `Precios de ${encargo.tbtemporadanombre}` : 'Precios normales'],
        ['Asignado a', encargo.tbempleadoid ? `${encargo.tbempleadonombre}${encargo.tbempleadorol === 'Propietario' ? ' (Propietario)' : ''}` : 'Sin asignar'],
        ['Estado', encargo.tbsolicitudencargoestado],
        ['Registrado el', formatearfecha(encargo.tbsolicitudencargofecharegistro)],
        ['Nota', encargo.tbsolicitudencargonota],
    ];

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
    const referencia = personalizado ? encargo.tbsolicitudencargoreferencia : '';
    figurareferencia.hidden = !referencia;
    fotoreferenciadetalle.src = referencia || '';
    columnafoto.hidden = !foto && !puedefoto && !referencia;
    // Cuando la vista tenga un subtítulo propio, el encabezado muestra solo el número.
    // Mientras tanto se conserva la descripción junto al título original.
    const descripciondetalle = document.getElementById('descripciondetalle');
    titulodetalle.textContent = descripciondetalle
        ? `Encargo N.º ${encargo.tbsolicitudencargoid}`
        : `Encargo N.º ${encargo.tbsolicitudencargoid}: ${encargo.descripcion}`;
    if (descripciondetalle) {
        descripciondetalle.textContent = encargo.descripcion;
    }
    accionesdetalle.innerHTML = accionesencargo(encargo) ?? '';
    accionesdetalle.querySelectorAll('[data-accion="ver"]').forEach((elemento) => elemento.remove());
    accionesdetalle.querySelectorAll('button').forEach((elemento) => {
        elemento.className = elemento.classList.contains('enlace-peligro') ? 'boton boton-peligro' : 'boton';
    });
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

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

    // Los materiales devueltos por el servidor representan la receta actual y el
    // stock actual. No se presentan como salidas históricas ya comprobadas.
    const estado = encargo.tbsolicitudencargoestado;
    const porpreparar = ['Pendiente', 'En proceso'].includes(estado);
    const cancelado = estado === 'Cancelado';
    const entregado = estado === 'Entregado';

    titulomateriales.hidden = cancelado;
    tablamateriales.hidden = cancelado || entregado;
    botonmateriales.hidden = !entregado;
    botonmateriales.textContent = 'Ver materiales';
    nombremateriales.textContent = porpreparar ? 'Materiales necesarios' : 'Materiales del arreglo';

    if (porpreparar) {
        encabezadomateriales.innerHTML = `
            <tr><th>Producto</th><th>Necesarias</th><th>En inventario</th><th>¿Alcanza?</th></tr>`;
        cuerpodetalle.innerHTML = encargo.materiales.length === 0
            ? '<tr><td colspan="4" class="vacio">No hay materiales registrados.</td></tr>'
            : encargo.materiales.map((material) => `
                <tr>
                    <td>${escapar(material.tbinventarionombre)}</td>
                    <td>${material.necesarias}</td>
                    <td>${material.stock}</td>
                    <td>${material.alcanza ? '<span class="estado estado-activo">Sí</span>'
                        : `<span class="estado estado-bajo">Faltan ${material.necesarias - material.stock}</span>`}</td>
                </tr>`).join('');
    } else if (!cancelado) {
        // No repetimos la disponibilidad de stock después de terminar el ramo.
        // Estas cantidades provienen de la composición del encargo, no del historial de movimientos.
        encabezadomateriales.innerHTML = '<tr><th>Producto</th><th>Cantidad prevista</th></tr>';
        cuerpodetalle.innerHTML = encargo.materiales.length === 0
            ? '<tr><td colspan="2" class="vacio">No hay materiales registrados.</td></tr>'
            : encargo.materiales.map((material) => `
                <tr><td>${escapar(material.tbinventarionombre)}</td><td>${material.necesarias}</td></tr>`).join('');
    }

    cuerpohistorial.innerHTML = encargo.historial.length === 0
        ? '<tr><td colspan="3" class="vacio">Este encargo no tiene historial registrado.</td></tr>'
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

async function ejecutar(accion, id) {
    const encargo = encargos.find((item) => String(item.tbsolicitudencargoid) === String(id));
    const nombre = encargo ? `el encargo N.º ${id} (${encargo.descripcion})` : `el encargo N.º ${id}`;

    const configuraciones = {
        asignarme: {
            titulo: '¿Asignarte este encargo?',
            mensaje: `Te vas a encargar de preparar ${nombre}.`,
            textoaceptar: 'Asignármela',
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
        entregar: {
            titulo: '¿Confirmar entrega?',
            mensaje: `Se marcará como entregado ${nombre} y se registrará la fecha y hora.`,
            textoaceptar: 'Marcar como entregado',
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

    if (campo.name === 'tbarreglofloralid') {
        mostrararregloelegido();
    }

    if (['tbsolicitudencargotipo', 'tbsolicitudencargomodalidad', 'tbsolicitudencargoexpress'].includes(campo.name)) {
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

    const pasolinea = evento.target.closest('button[data-pasolinea]');
    if (pasolinea) {
        const fila = pasolinea.closest('tr[data-indice]');
        const linea = lineas[Number(fila.dataset.indice)];
        const actual = parseInt(linea.cantidad, 10) || 0;
        let nueva = Math.min(999, Math.max(1, actual + Number(pasolinea.dataset.pasolinea)));

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

document.getElementById('botonproforma').addEventListener('click', abrirproforma);
document.getElementById('botonimprimirproforma').addEventListener('click', imprimirproforma);
document.getElementById('botonpdfproforma').addEventListener('click', descargarproformapdf);
document.getElementById('botoncerrarproforma').addEventListener('click', () => dialogoproforma.close());

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbsolicitudencargoid.value !== '';

    if (esedicion) {
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizará el encargo N.º ${formulario.elements.tbsolicitudencargoid.value}. Total: ${formatearmonto(calcular().total)}.`,
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

const accionesdetalle = document.getElementById('accionesdetalle');

function hacer(accion, id) {
    switch (accion) {
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
        case 'entregar':
        case 'cancelar':
            ejecutar(accion, id);
            break;
    }
}

cuerpotabla.addEventListener('click', (evento) => {
    const elemento = evento.target.closest('button[data-accion]');
    if (elemento) {
        hacer(elemento.dataset.accion, elemento.dataset.id);
    }
});

accionesdetalle.addEventListener('click', (evento) => {
    const elemento = evento.target.closest('button[data-accion]');
    if (elemento) {
        dialogodetalle.close();
        hacer(elemento.dataset.accion, elemento.dataset.id);
    }
});

filtroperiodo.addEventListener('change', () => {
    actualizarfechaencargos();
    cargarencargos();
});
filtrofecha.addEventListener('change', cargarencargos);
filtroestado.addEventListener('change', cargarencargos);
filtrotipo.addEventListener('change', cargarencargos);
filtroempleado.addEventListener('change', cargarencargos);
// Conserva el borrador antes de ir a registrar un cliente.
document.getElementById('enlaceregistrarcliente').addEventListener('click', guardarborrador);

// Galería de arreglos: cambia únicamente el valor del select original.
document.getElementById('botonelegirarreglo').addEventListener('click', () => {
    document.getElementById('buscararregloencargo').value = '';
    dibujarcatalogoencargo();
    dialogocatalogoencargo.showModal();
});
document.getElementById('buscararregloencargo').addEventListener('input', dibujarcatalogoencargo);
document.getElementById('galeriaarreglosencargo').addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-elegirarreglo]');
    if (!boton) { return; }
    const campo = formulario.elements.tbarreglofloralid;
    campo.value = boton.dataset.elegirarreglo;
    campo.dispatchEvent(new Event('change', { bubbles: true }));
    limpiarerrorcampo('tbarreglofloralid');
    dialogocatalogoencargo.close();
});
document.getElementById('botoncerrarcatalogoencargo').addEventListener('click', () => dialogocatalogoencargo.close());

// Los productos con foto son exactamente los de Inventario, incluidos los regalos.
document.getElementById('botonbuscarproductoencargo').addEventListener('click', () => {
    document.getElementById('buscarproductoencargo').value = '';
    dibujarproductosencargo();
    dialogoproductosencargo.showModal();
});
const selectorcategoriaencargo = document.getElementById('categoriaproductoencargo');
categoriasproducto.forEach(([categoria, etiqueta]) => selectorcategoriaencargo.add(new Option(etiqueta, categoria)));
document.getElementById('buscarproductoencargo').addEventListener('input', dibujarproductosencargo);
selectorcategoriaencargo.addEventListener('change', dibujarproductosencargo);
document.getElementById('galeriaproductosencargo').addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-elegirproducto]');
    if (!boton) { return; }
    const producto = buscar(opciones.productos, 'tbinventarioid', boton.dataset.elegirproducto);
    if (!producto) { return; }
    const existente = lineas.find((linea) => String(linea.tbinventarioid) === String(producto.tbinventarioid));
    if (presupuesto() !== null && gastopresupuesto() + preciounidad(producto) > presupuesto()) {
        document.getElementById('galeriaproductosencargo').insertAdjacentHTML('afterbegin',
            '<p class="aviso aviso-error">Este producto supera el presupuesto disponible.</p>');
        return;
    }
    if (existente) {
        if (Number(existente.cantidad) >= Number(producto.tbinventariodisponible)) {
            document.getElementById('galeriaproductosencargo').insertAdjacentHTML('afterbegin',
                '<p class="aviso aviso-error">No hay más unidades disponibles de este producto.</p>');
            return;
        }
        existente.cantidad = String(Number(existente.cantidad) + 1);
    } else {
        lineas.push({ tbinventarioid: String(producto.tbinventarioid), cantidad: '1',
            nombre: producto.tbinventarionombre, precioguardado: null });
    }
    dibujarlineas();
    guardarborrador();
    dialogoproductosencargo.close();
});
document.getElementById('botoncerrarproductosencargo').addEventListener('click', () => dialogoproductosencargo.close());

// El botón Nuevo encargo muestra primero las dos opciones; no crea otro formulario.
document.getElementById('botonnuevo').addEventListener('click', () => dialogoelegirencargo.showModal());
document.getElementById('botoncancelareleccion').addEventListener('click', () => dialogoelegirencargo.close());
document.getElementById('botoncerrarelegir').addEventListener('click', () => dialogoelegirencargo.close());
dialogoelegirencargo.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('button[data-tipoencargo]');
    if (!boton) {
        return;
    }

    const tipo = boton.dataset.tipoencargo;
    const borrador = leerborrador();
    dialogoelegirencargo.close();

    // Evita sobrescribir sin aviso un borrador de la otra modalidad.
    if (borrador && borrador.tbsolicitudencargotipo && borrador.tbsolicitudencargotipo !== tipo) {
        const descartar = await confirmar({
            titulo: '¿Cambiar de tipo de encargo?',
            mensaje: 'Hay un borrador de otro tipo de encargo. Para empezar uno nuevo, se descartará ese borrador.',
            textoaceptar: 'Empezar nuevo encargo',
            peligro: true,
        });
        if (!descartar) {
            dialogoelegirencargo.showModal();
            return;
        }
        borrarborrador();
    }

    abrirnuevo(tipo);
});
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrarformulario').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncancelarasignar').addEventListener('click', () => dialogoasignar.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
// Se activará cuando agreguemos la X al encabezado de encargovista.php.
document.getElementById('botoncerrardetallearriba')?.addEventListener('click', () => dialogodetalle.close());
botonfoto.addEventListener('click', () => campofoto.click());
campofoto.addEventListener('change', subirfoto);
document.getElementById('botonquitarreferencia').addEventListener('click', () => {
    formulario.elements.tbsolicitudencargoreferencia.value = '';
    limpiarerrorcampo('tbsolicitudencargoreferencia');
    actualizarsecciones();
    guardarborrador();
});
document.getElementById('botondescartarborrador').addEventListener('click', () => {
    // Al crear uno nuevo, conservar la modalidad elegida antes de recuperar el borrador.
    const tipo = formulario.elements.tbsolicitudencargotipo.value;
    borrarborrador();
    llenarformulario(datosservidor);
    if (!datosservidor.tbsolicitudencargoid && ['Predefinido', 'Personalizado'].includes(tipo)) {
        formulario.elements.tbsolicitudencargotipo.value = tipo;
        actualizarsecciones();
    }
    limpiarerrores();
    avisoborrador.hidden = true;
});

formulario.elements.tbsolicitudencargofechaentrega.min = hoy();

const opcioneslistas = cargaropciones();

opcioneslistas.finally(() => {
    filtrosdesdeurl({ estado: filtroestado, tipo: filtrotipo, empleado: filtroempleado });
    const parametrosiniciales = new URLSearchParams(window.location.search);
    if (['estado', 'empleado', 'tipo'].some((campo) => parametrosiniciales.has(campo))) {
        filtroperiodo.value = 'todas';
    }
    actualizarfechaencargos();
    cargarencargos();

    const parametros = new URLSearchParams(window.location.search);
    const idver = parametros.get('ver');
    if (idver && /^\d+$/.test(idver)) {
        parametros.delete('ver');
        window.history.replaceState(null, '', `${window.location.pathname}?${parametros}`);
        verdetalle(idver);
    }
});
abrirformulariosipide(abrirnuevo);