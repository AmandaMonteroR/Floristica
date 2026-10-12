const rutabase = 'index.php?modulo=pedido';
const clavebase = 'floristica.borrador.pedido.';

const cuerpotabla = document.getElementById('cuerpotabla');
const filtroproveedor = document.getElementById('filtroproveedor');
const filtroestado = document.getElementById('filtroestado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const cuerpolineas = document.getElementById('cuerpolineas');
const totalpedido = document.getElementById('totalpedido');
const selectproveedor = formulario.elements.tbproveedorid;
const campofecha = formulario.elements.tbpedidofecha;
const dialogoconfirmarcompra = document.getElementById('dialogoconfirmarcompra');
const formularioconfirmar = document.getElementById('formularioconfirmar');
const resumenconfirmar = document.getElementById('resumenconfirmar');
const avisoconfirmar = document.getElementById('avisoconfirmar');
const campoplazo = document.getElementById('campoplazo');
const botonguardarconfirmar = document.getElementById('botonguardarconfirmar');
const dialogodetalle = document.getElementById('dialogodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpodetalle = document.getElementById('cuerpodetalle');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');

let temporizadoraviso;
let lineas = [];
let productos = [];
let datosservidor = {};
let datosoriginales = '';
const productosporproveedor = {};

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

function formatearmonto(monto) {
    return '₡' + Number(monto || 0).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function hoy() {
    return new Date().toLocaleDateString('en-CA');
}

function soloentero(valor) {
    return String(valor ?? '').replace(/\D/g, '');
}

function solodecimal(valor) {
    const limpio = String(valor ?? '').replace(/,/g, '.').replace(/[^\d.]/g, '');
    const [entera, ...resto] = limpio.split('.');
    return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
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

/* ---------- Proveedores y productos ---------- */

async function cargarproveedores() {
    const respuesta = await consultar('proveedores');

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje || 'No se pudieron cargar los proveedores.', 'error');
        return;
    }

    [selectproveedor, filtroproveedor].forEach((select) => {
        const textovacio = select === filtroproveedor ? 'Todos' : 'Seleccione…';
        select.innerHTML = '';
        select.add(new Option(textovacio, ''));
        respuesta.datos.forEach((proveedor) => {
            select.add(new Option(proveedor.tbproveedornombreempresa, proveedor.tbproveedorid));
        });
    });
}

async function cargarproductos(idproveedor) {
    if (!idproveedor) {
        productos = [];
        return;
    }

    if (!productosporproveedor[idproveedor]) {
        const respuesta = await consultar('productos', { proveedor: idproveedor });
        productosporproveedor[idproveedor] = respuesta.exito ? respuesta.datos : [];
    }

    productos = productosporproveedor[idproveedor];
}

function buscarproducto(id) {
    return productos.find((producto) => String(producto.tbinventarioid) === String(id));
}

/* ---------- Líneas del pedido ---------- */

function lineavacia() {
    return { tbinventarioid: '', tbpedidodetallecantidad: '', tbpedidodetalleprecio: '', tbinventarionombre: '' };
}

function subtotal(linea) {
    const cantidad = parseInt(linea.tbpedidodetallecantidad, 10) || 0;
    const precio = parseFloat(linea.tbpedidodetalleprecio) || 0;
    return cantidad * precio;
}

function actualizartotal() {
    totalpedido.textContent = formatearmonto(lineas.reduce((suma, linea) => suma + subtotal(linea), 0));
}

function opcionesproducto(linea) {
    const usados = lineas
        .filter((otra) => otra !== linea && otra.tbinventarioid !== '')
        .map((otra) => String(otra.tbinventarioid));

    let opciones = `<option value="">${productos.length ? 'Seleccione…' : 'El proveedor no tiene productos activos'}</option>`;

    productos.forEach((producto) => {
        const id = String(producto.tbinventarioid);
        const seleccionado = id === String(linea.tbinventarioid) ? ' selected' : '';
        const usado = usados.includes(id) ? ' disabled' : '';
        opciones += `<option value="${id}"${seleccionado}${usado}>${escapar(producto.tbinventarionombre)} (${producto.tbinventariounidadespaquete} u.)</option>`;
    });

    // Producto guardado que ya no está activo
    if (linea.tbinventarioid !== '' && !buscarproducto(linea.tbinventarioid)) {
        opciones += `<option value="${linea.tbinventarioid}" selected>${escapar(linea.tbinventarionombre || 'Producto')} (inactivo)</option>`;
    }

    return opciones;
}

function dibujarlineas() {
    if (lineas.length === 0) {
        cuerpolineas.innerHTML = `<tr><td colspan="5" class="vacio">${selectproveedor.value === ''
            ? 'Seleccione primero el proveedor.'
            : 'Use "Agregar producto" para añadir líneas al pedido.'}</td></tr>`;
        actualizartotal();
        return;
    }

    cuerpolineas.innerHTML = lineas.map((linea, indice) => `
        <tr data-indice="${indice}">
            <td><select data-campo="tbinventarioid" aria-label="Producto de la línea ${indice + 1}">${opcionesproducto(linea)}</select></td>
            <td>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar un paquete en la línea ${indice + 1}">−</button>
                    <input type="text" data-campo="tbpedidodetallecantidad" inputmode="numeric" maxlength="4" autocomplete="off" value="${escapar(linea.tbpedidodetallecantidad)}" aria-label="Paquetes de la línea ${indice + 1}">
                    <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar un paquete en la línea ${indice + 1}">+</button>
                </div>
            </td>
            <td><input type="text" data-campo="tbpedidodetalleprecio" inputmode="decimal" maxlength="11" autocomplete="off" value="${escapar(linea.tbpedidodetalleprecio)}" aria-label="Precio de la línea ${indice + 1}"></td>
            <td class="subtotal-linea">${formatearmonto(subtotal(linea))}</td>
            <td><button type="button" class="enlace enlace-peligro" data-quitar="${indice}" aria-label="Quitar línea ${indice + 1}">Quitar</button></td>
        </tr>`).join('');

    actualizartotal();
}

function agregarlinea() {
    if (selectproveedor.value === '') {
        mostrarerrores({ tbproveedorid: 'Seleccione el proveedor antes de agregar productos.' });
        return;
    }

    lineas.push(lineavacia());
    dibujarlineas();
    guardarborrador();
    cuerpolineas.querySelector('tr:last-child select').focus();
}

/* ---------- Borrador ---------- */

function claveborrador() {
    const id = formulario.elements.tbpedidoid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function leerformulario() {
    return {
        tbproveedorid: selectproveedor.value,
        tbpedidofecha: campofecha.value,
        lineas: lineas.map((linea) => ({ ...linea })),
    };
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

async function asignarcampos(datos) {
    selectproveedor.value = datos.tbproveedorid ?? '';
    campofecha.value = datos.tbpedidofecha ?? '';
    await cargarproductos(selectproveedor.value);
    lineas = (datos.lineas ?? []).map((linea) => ({ ...lineavacia(), ...linea }));
    dibujarlineas();
}

async function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbpedidoid.value = datos.tbpedidoid ?? '';

    if (datos.tbproveedorid && !selectproveedor.querySelector(`option[value="${datos.tbproveedorid}"]`)) {
        selectproveedor.add(new Option(`${datos.tbproveedornombreempresa} (inactivo)`, datos.tbproveedorid));
    }

    await asignarcampos({
        tbproveedorid: datos.tbproveedorid ? String(datos.tbproveedorid) : '',
        tbpedidofecha: datos.tbpedidofecha ?? hoy(),
        lineas: (datos.detalles ?? []).map((detalle) => ({
            tbinventarioid: String(detalle.tbinventarioid),
            tbinventarionombre: detalle.tbinventarionombre,
            tbpedidodetallecantidad: String(detalle.tbpedidodetallecantidad),
            tbpedidodetalleprecio: String(detalle.tbpedidodetalleprecio),
        })),
    });

    datosoriginales = JSON.stringify(leerformulario());
}

async function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    if (JSON.stringify(borrador) === datosoriginales) {
        borrarborrador();
        return;
    }

    await asignarcampos(borrador);
    avisoborrador.hidden = false;
}

async function descartarborrador() {
    borrarborrador();
    await llenarformulario(datosservidor);
    limpiarerrores();
    avisoborrador.hidden = true;
}

/* ---------- Tabla de pedidos ---------- */

function claseestado(estado) {
    return {
        Pendiente: 'estado-pendiente',
        Confirmado: 'estado-confirmado',
        Recibido: 'estado-recibido',
    }[estado] ?? 'estado-inactivo';
}

async function cargarpedidos() {
    const respuesta = await consultar('listar', {
        proveedor: filtroproveedor.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="6" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = filtroproveedor.value === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay pedidos registrados. Use "Nuevo pedido" para agregar el primero.'
            : 'No hay pedidos que coincidan con el filtro.';
        cuerpotabla.innerHTML = `<tr><td colspan="6" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((pedido) => {
        const id = pedido.tbpedidoid;
        let acciones = `<button type="button" class="enlace" data-accion="ver" data-id="${id}">Ver</button>`;

        if (pedido.tbpedidoestado === 'Pendiente') {
            acciones += `
                <button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>
                <button type="button" class="enlace" data-accion="confirmar" data-id="${id}">Confirmar</button>`;
        } else if (pedido.tbpedidoestado === 'Confirmado') {
            acciones += `
                <button type="button" class="enlace" data-accion="recibir" data-id="${id}">Recibir</button>`;
        }

        return `
            <tr>
                <td>${id}</td>
                <td>${formatearfecha(pedido.tbpedidofecha)}</td>
                <td>${escapar(pedido.tbproveedornombreempresa)}</td>
                <td>${formatearmonto(pedido.total)}</td>
                <td><span class="estado ${claseestado(pedido.tbpedidoestado)}">${escapar(pedido.tbpedidoestado)}</span></td>
                <td class="columna-acciones">${acciones}</td>
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
    (form === formulario ? avisoformulario : avisoconfirmar).hidden = true;
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

/* ---------- Abrir formulario ---------- */

async function abrirnuevo() {
    await proveedoreslistos;
    datosservidor = {};
    await llenarformulario(datosservidor);
    limpiarerrores();
    await aplicarborrador();
    tituloformulario.textContent = 'Nuevo pedido';
    botonguardar.textContent = 'Guardar pedido';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    await proveedoreslistos;
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosservidor = respuesta.datos;
    await llenarformulario(datosservidor);
    limpiarerrores();
    await aplicarborrador();
    tituloformulario.textContent = `Editar pedido N.º ${id}`;
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

/* ---------- Ver detalle ---------- */

async function verdetalle(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const pedido = respuesta.datos;
    const filas = [
        ['Proveedor', pedido.tbproveedornombreempresa],
        ['Fecha del pedido', formatearfecha(pedido.tbpedidofecha)],
        ['Estado', pedido.tbpedidoestado],
        ['Total', formatearmonto(pedido.total)],
    ];

    if (pedido.confirmacion) {
        const pago = pedido.confirmacion;
        filas.push(['Confirmado el', formatearfecha(pago.tbconfirmacioncomprafecha)]);
        filas.push(['Tipo de pago', pago.tbconfirmacioncompratipopago === 'Crédito'
            ? `Crédito a ${pago.tbconfirmacioncompraplazodias} días`
            : 'Contado']);
        filas.push(['Monto confirmado', formatearmonto(pago.tbconfirmacioncompramonto)]);
    }

    titulodetalle.textContent = `Pedido N.º ${pedido.tbpedidoid}`;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

    cuerpodetalle.innerHTML = pedido.detalles.map((detalle) => `
        <tr>
            <td>${escapar(detalle.tbinventarionombre)}</td>
            <td>${detalle.tbpedidodetallecantidad}</td>
            <td>${detalle.tbpedidodetallecantidad * detalle.tbinventariounidadespaquete}</td>
            <td>${formatearmonto(detalle.tbpedidodetalleprecio)}</td>
            <td>${formatearmonto(detalle.tbpedidodetallecantidad * detalle.tbpedidodetalleprecio)}</td>
        </tr>`).join('');

    dialogodetalle.showModal();
}

/* ---------- Confirmar compra y recibir ---------- */

function actualizarplazo() {
    const credito = formularioconfirmar.elements.tbconfirmacioncompratipopago.value === 'Crédito';
    campoplazo.hidden = !credito;
    if (!credito) {
        formularioconfirmar.elements.tbconfirmacioncompraplazodias.value = '';
    }
}

async function abrirconfirmar(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const pedido = respuesta.datos;
    formularioconfirmar.reset();
    formularioconfirmar.elements.tbpedidoid.value = id;
    resumenconfirmar.textContent = `Pedido N.º ${id} a ${pedido.tbproveedornombreempresa} por ${formatearmonto(pedido.total)}. Después de confirmar ya no se podrá editar.`;
    actualizarplazo();
    limpiarerrores(formularioconfirmar);
    dialogoconfirmarcompra.showModal();
}

async function recibir(id) {
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const resumen = respuesta.datos.detalles
        .map((detalle) => `${detalle.tbpedidodetallecantidad * detalle.tbinventariounidadespaquete} u. de ${detalle.tbinventarionombre}`)
        .join(', ');

    const aceptado = await confirmar({
        titulo: '¿Recibir el pedido?',
        mensaje: `Se sumarán al inventario: ${resumen}.`,
        textoaceptar: 'Recibir pedido',
    });

    if (!aceptado) {
        return;
    }

    const datos = new FormData();
    datos.append('id', id);

    const resultado = await enviar('recibir', datos);
    mostraraviso(resultado.mensaje, resultado.exito ? 'exito' : 'error');

    if (resultado.exito) {
        cargarpedidos();
    }
}

/* ---------- Eventos ---------- */

selectproveedor.addEventListener('change', async () => {
    const tenialineas = lineas.some((linea) => linea.tbinventarioid !== '');
    await cargarproductos(selectproveedor.value);
    lineas = selectproveedor.value === '' ? [] : [lineavacia()];
    dibujarlineas();
    guardarborrador();

    if (tenialineas) {
        mostrarerrores({ detalles: 'Se quitaron los productos porque cambió el proveedor.' });
    }
});

campofecha.addEventListener('input', guardarborrador);

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;
    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
});

cuerpolineas.addEventListener('input', (evento) => {
    const campo = evento.target;
    const fila = campo.closest('tr[data-indice]');
    if (!fila || !campo.dataset.campo) {
        return;
    }

    const linea = lineas[Number(fila.dataset.indice)];

    if (campo.dataset.campo === 'tbpedidodetallecantidad') {
        campo.value = soloentero(campo.value);
    } else if (campo.dataset.campo === 'tbpedidodetalleprecio') {
        campo.value = solodecimal(campo.value);
    }

    linea[campo.dataset.campo] = campo.value;
    fila.querySelector('.subtotal-linea').textContent = formatearmonto(subtotal(linea));
    formulario.querySelector('[data-error="detalles"]').textContent = '';
    actualizartotal();
    guardarborrador();
});

cuerpolineas.addEventListener('change', (evento) => {
    const campo = evento.target;
    if (campo.dataset.campo !== 'tbinventarioid') {
        return;
    }

    const linea = lineas[Number(campo.closest('tr').dataset.indice)];
    const producto = buscarproducto(campo.value);
    linea.tbinventarioid = campo.value;
    linea.tbinventarionombre = producto ? producto.tbinventarionombre : '';

    // Sugiere el precio del paquete registrado en el inventario
    if (producto && linea.tbpedidodetalleprecio === '') {
        linea.tbpedidodetalleprecio = String(producto.tbinventariopreciopaquete);
    }
    if (producto && linea.tbpedidodetallecantidad === '') {
        linea.tbpedidodetallecantidad = '1';
    }

    dibujarlineas();
    guardarborrador();
});

// Botones − y + de la cantidad de paquetes
cuerpolineas.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-paso]');
    if (!boton) {
        return;
    }

    const fila = boton.closest('tr[data-indice]');
    const linea = lineas[Number(fila.dataset.indice)];
    const actual = parseInt(linea.tbpedidodetallecantidad, 10) || 0;
    const nueva = Math.min(9999, Math.max(1, actual + Number(boton.dataset.paso)));

    linea.tbpedidodetallecantidad = String(nueva);
    fila.querySelector('[data-campo="tbpedidodetallecantidad"]').value = linea.tbpedidodetallecantidad;
    fila.querySelector('.subtotal-linea').textContent = formatearmonto(subtotal(linea));
    formulario.querySelector('[data-error="detalles"]').textContent = '';
    actualizartotal();
    guardarborrador();
});

cuerpolineas.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-quitar]');
    if (!boton) {
        return;
    }

    lineas.splice(Number(boton.dataset.quitar), 1);
    dibujarlineas();
    guardarborrador();
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbpedidoid.value !== '';

    if (esedicion) {
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizará el pedido N.º ${formulario.elements.tbpedidoid.value}.`,
            textoaceptar: 'Guardar cambios',
        });

        if (!aceptado) {
            return;
        }
    }

    const datos = new FormData();
    datos.append('tbpedidoid', formulario.elements.tbpedidoid.value);
    datos.append('tbproveedorid', selectproveedor.value);
    datos.append('tbpedidofecha', campofecha.value);
    lineas.forEach((linea, indice) => {
        datos.append(`detalles[${indice}][tbinventarioid]`, linea.tbinventarioid);
        datos.append(`detalles[${indice}][tbpedidodetallecantidad]`, linea.tbpedidodetallecantidad);
        datos.append(`detalles[${indice}][tbpedidodetalleprecio]`, linea.tbpedidodetalleprecio);
    });

    const clave = claveborrador();

    limpiarerrores();
    botonguardar.disabled = true;

    const respuesta = await enviar(esedicion ? 'actualizar' : 'crear', datos);

    botonguardar.disabled = false;

    if (respuesta.exito) {
        borrarborrador(clave);
        avisoborrador.hidden = true;
        dialogoformulario.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarpedidos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});

formularioconfirmar.elements.tbconfirmacioncompratipopago.addEventListener('change', actualizarplazo);

formularioconfirmar.addEventListener('input', (evento) => {
    const campo = evento.target;
    if (campo.name === 'tbconfirmacioncompraplazodias') {
        campo.value = soloentero(campo.value);
    }
    const error = formularioconfirmar.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
});

formularioconfirmar.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    limpiarerrores(formularioconfirmar);
    botonguardarconfirmar.disabled = true;

    const respuesta = await enviar('confirmar', new FormData(formularioconfirmar));

    botonguardarconfirmar.disabled = false;

    if (respuesta.exito) {
        dialogoconfirmarcompra.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarpedidos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores, formularioconfirmar);
    }

    avisoconfirmar.textContent = respuesta.mensaje;
    avisoconfirmar.hidden = false;
});

cuerpotabla.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) {
        return;
    }

    const id = boton.dataset.id;

    switch (boton.dataset.accion) {
        case 'ver':
            verdetalle(id);
            break;
        case 'editar':
            abrireditar(id);
            break;
        case 'confirmar':
            abrirconfirmar(id);
            break;
        case 'recibir':
            recibir(id);
            break;
    }
});

filtroproveedor.addEventListener('change', cargarpedidos);
filtroestado.addEventListener('change', cargarpedidos);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botonagregarlinea').addEventListener('click', agregarlinea);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncancelarconfirmar').addEventListener('click', () => dialogoconfirmarcompra.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

campofecha.max = hoy();

const proveedoreslistos = cargarproveedores();
cargarpedidos();