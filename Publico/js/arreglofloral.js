const rutabase = 'index.php?modulo=catalogo';
const clavebase = 'floristica.borrador.arreglo.';
const tiposfoto = ['image/jpeg', 'image/png', 'image/webp'];
const tamanomaximo = 2 * 1024 * 1024;

const galeria = document.getElementById('galeria');
const busqueda = document.getElementById('busqueda');
const filtroestado = document.getElementById('filtroestado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const cuerpolineas = document.getElementById('cuerpolineas');
const costoarreglo = document.getElementById('costoarreglo');
const gananciaarreglo = document.getElementById('gananciaarreglo');
const campofoto = document.getElementById('campofoto');
const vistafoto = document.getElementById('vistafoto');
const textofoto = document.getElementById('textofoto');
const etiquetafoto = document.getElementById('etiquetafoto');
const dialogodetalle = document.getElementById('dialogodetalle');
const fotodetalle = document.getElementById('fotodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpodetalle = document.getElementById('cuerpodetalle');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const camposformulario = ['tbarreglofloralnombre', 'tbarreglofloraldescripcion', 'tbarreglofloralprecio'];

// usuarioactual viene de menu.php. Un empleado solo puede ver el catálogo.
const esduena = usuarioactual.rol === 'Dueña';

let temporizadorbusqueda;
let temporizadoraviso;
let lineas = [];
let productos = [];
let datosservidor = {};
let datosoriginales = '';
let urlvistaprevia = null;

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearmonto(monto) {
    const valor = Number(monto || 0);
    const signo = valor < 0 ? '-' : '';
    return signo + '₡' + Math.abs(valor).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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

/* ---------- Productos del inventario ---------- */

async function cargarproductos() {
    const respuesta = await consultar('productos');
    productos = respuesta.exito ? respuesta.datos : [];
}

function buscarproducto(id) {
    return productos.find((producto) => String(producto.tbinventarioid) === String(id));
}

/* ---------- Foto ---------- */

function mostrarfoto(url) {
    if (urlvistaprevia) {
        URL.revokeObjectURL(urlvistaprevia);
        urlvistaprevia = null;
    }

    vistafoto.hidden = !url;
    textofoto.hidden = Boolean(url);
    if (url) {
        vistafoto.src = url;
    } else {
        vistafoto.removeAttribute('src');
    }
}

function revisarfoto() {
    const error = formulario.querySelector('[data-error="tbarreglofloralfoto"]');
    const archivo = campofoto.files[0];
    error.textContent = '';

    if (!archivo) {
        return;
    }

    if (!tiposfoto.includes(archivo.type)) {
        error.textContent = 'La foto debe ser una imagen JPG, PNG o WEBP.';
        campofoto.value = '';
        return;
    }

    if (archivo.size > tamanomaximo) {
        error.textContent = 'La foto no puede pesar más de 2 MB.';
        campofoto.value = '';
        return;
    }

    mostrarfoto(null);
    urlvistaprevia = URL.createObjectURL(archivo);
    vistafoto.src = urlvistaprevia;
    vistafoto.hidden = false;
    textofoto.hidden = true;
}

/* ---------- Líneas del arreglo ---------- */

function lineavacia() {
    return {
        tbinventarioid: '',
        tbarreglofloraldetallecantidad: '',
        tbinventarionombre: '',
        tbinventariopreciounidad: 0,
    };
}

function costounidad(linea) {
    const producto = buscarproducto(linea.tbinventarioid);
    return producto ? producto.tbinventariopreciounidad : Number(linea.tbinventariopreciounidad) || 0;
}

function subtotal(linea) {
    return (parseInt(linea.tbarreglofloraldetallecantidad, 10) || 0) * costounidad(linea);
}

function actualizartotales() {
    const costo = lineas.reduce((suma, linea) => suma + subtotal(linea), 0);
    const precio = parseFloat(formulario.elements.tbarreglofloralprecio.value) || 0;
    const ganancia = precio - costo;

    costoarreglo.textContent = formatearmonto(costo);
    gananciaarreglo.textContent = formatearmonto(ganancia);
    gananciaarreglo.classList.toggle('texto-peligro', ganancia < 0);
}

function opcionesproducto(linea) {
    const usados = lineas
        .filter((otra) => otra !== linea && otra.tbinventarioid !== '')
        .map((otra) => String(otra.tbinventarioid));

    let opciones = '<option value="">Seleccione…</option>';

    ['Flor', 'Base', 'Adorno', 'Extra'].forEach((categoria) => {
        const grupo = productos.filter((producto) => producto.tbinventariocategoria === categoria);
        if (grupo.length === 0) {
            return;
        }

        opciones += `<optgroup label="${categoria}">`;
        grupo.forEach((producto) => {
            const id = String(producto.tbinventarioid);
            const seleccionado = id === String(linea.tbinventarioid) ? ' selected' : '';
            const usado = usados.includes(id) ? ' disabled' : '';
            opciones += `<option value="${id}"${seleccionado}${usado}>${escapar(producto.tbinventarionombre)}</option>`;
        });
        opciones += '</optgroup>';
    });

    // Producto guardado que ya no está activo
    if (linea.tbinventarioid !== '' && !buscarproducto(linea.tbinventarioid)) {
        opciones += `<option value="${linea.tbinventarioid}" selected>${escapar(linea.tbinventarionombre || 'Producto')} (inactivo)</option>`;
    }

    return opciones;
}

function dibujarlineas() {
    if (lineas.length === 0) {
        cuerpolineas.innerHTML = '<tr><td colspan="5" class="vacio">Use "Agregar producto" para elegir las flores, bases, adornos o extras del arreglo.</td></tr>';
        actualizartotales();
        return;
    }

    cuerpolineas.innerHTML = lineas.map((linea, indice) => `
        <tr data-indice="${indice}">
            <td><select data-campo="tbinventarioid" aria-label="Producto de la línea ${indice + 1}">${opcionesproducto(linea)}</select></td>
            <td>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar una unidad en la línea ${indice + 1}">−</button>
                    <input type="text" data-campo="tbarreglofloraldetallecantidad" inputmode="numeric" maxlength="3" autocomplete="off" value="${escapar(linea.tbarreglofloraldetallecantidad)}" aria-label="Unidades de la línea ${indice + 1}">
                    <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar una unidad en la línea ${indice + 1}">+</button>
                </div>
            </td>
            <td>${formatearmonto(costounidad(linea))}</td>
            <td class="subtotal-linea">${formatearmonto(subtotal(linea))}</td>
            <td><button type="button" class="enlace enlace-peligro" data-quitar="${indice}" aria-label="Quitar línea ${indice + 1}">Quitar</button></td>
        </tr>`).join('');

    actualizartotales();
}

function actualizarfila(fila, linea) {
    fila.querySelector('[data-campo="tbarreglofloraldetallecantidad"]').value = linea.tbarreglofloraldetallecantidad;
    fila.querySelector('.subtotal-linea').textContent = formatearmonto(subtotal(linea));
    formulario.querySelector('[data-error="detalles"]').textContent = '';
    actualizartotales();
    guardarborrador();
}

/* ---------- Borrador ---------- */

function claveborrador() {
    const id = formulario.elements.tbarreglofloralid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}

function leerformulario() {
    const datos = {};
    camposformulario.forEach((campo) => {
        datos[campo] = formulario.elements[campo].value;
    });
    datos.lineas = lineas.map((linea) => ({
        tbinventarioid: linea.tbinventarioid,
        tbarreglofloraldetallecantidad: linea.tbarreglofloraldetallecantidad,
        tbinventarionombre: linea.tbinventarionombre,
        tbinventariopreciounidad: linea.tbinventariopreciounidad,
    }));
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
    lineas = (datos.lineas ?? []).map((linea) => ({ ...lineavacia(), ...linea }));
    dibujarlineas();
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbarreglofloralid.value = datos.tbarreglofloralid ?? '';
    mostrarfoto(datos.tbarreglofloralfoto || null);
    etiquetafoto.textContent = datos.tbarreglofloralid ? 'Foto (toque para cambiarla)' : 'Foto *';

    asignarcampos({
        tbarreglofloralnombre: datos.tbarreglofloralnombre,
        tbarreglofloraldescripcion: datos.tbarreglofloraldescripcion,
        tbarreglofloralprecio: datos.tbarreglofloralprecio !== undefined ? String(datos.tbarreglofloralprecio) : '',
        lineas: (datos.detalles ?? []).map((detalle) => ({
            tbinventarioid: String(detalle.tbinventarioid),
            tbarreglofloraldetallecantidad: String(detalle.tbarreglofloraldetallecantidad),
            tbinventarionombre: detalle.tbinventarionombre,
            tbinventariopreciounidad: detalle.tbinventariopreciounidad,
        })),
    });

    datosoriginales = JSON.stringify(leerformulario());
}

function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;

    if (!borrador) {
        return;
    }

    if (JSON.stringify(borrador) === datosoriginales) {
        borrarborrador();
        return;
    }

    asignarcampos(borrador);
    avisoborrador.hidden = false;
}

function descartarborrador() {
    borrarborrador();
    llenarformulario(datosservidor);
    limpiarerrores();
    avisoborrador.hidden = true;
}

/* ---------- Galería ---------- */

async function cargararreglos() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        galeria.innerHTML = `<p class="vacio-galeria">${escapar(respuesta.mensaje)}</p>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtroestado.value === '';
        galeria.innerHTML = `<p class="vacio-galeria">${sinfiltros
            ? 'Aún no hay arreglos en el catálogo. Use "Nuevo arreglo" para agregar el primero.'
            : 'No hay arreglos que coincidan con la búsqueda.'}</p>`;
        return;
    }

    galeria.innerHTML = respuesta.datos.map((arreglo) => {
        const activo = arreglo.tbarreglofloralestado === 1;
        const id = arreglo.tbarreglofloralid;
        const ganancia = arreglo.tbarreglofloralprecio - arreglo.costo;
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Reactivar</button>`;
        const botonesduena = esduena
            ? `<button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>
                        ${botonestado}`
            : '';

        return `
            <article class="tarjeta-arreglo ${activo ? '' : 'tarjeta-inactiva'}" data-nombre="${escapar(arreglo.tbarreglofloralnombre)}">
                <img src="${escapar(arreglo.tbarreglofloralfoto)}" alt="${escapar(arreglo.tbarreglofloralnombre)}" loading="lazy">
                <div class="tarjeta-cuerpo">
                    <div class="tarjeta-titulo">
                        <h2>${escapar(arreglo.tbarreglofloralnombre)}</h2>
                        <span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
                    </div>
                    <p class="tarjeta-precio">${formatearmonto(arreglo.tbarreglofloralprecio)}</p>
                    ${esduena ? `<p class="tarjeta-costo">Costo ${formatearmonto(arreglo.costo)} · Ganancia <span class="${ganancia < 0 ? 'texto-peligro' : ''}">${formatearmonto(ganancia)}</span></p>` : ''}
                    <div class="tarjeta-acciones">
                        <button type="button" class="enlace" data-accion="ver" data-id="${id}">Ver</button>
                        ${botonesduena}
                    </div>
                </div>
            </article>`;
    }).join('');
}

/* ---------- Errores ---------- */

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

/* ---------- Abrir formulario y detalle ---------- */

async function abrirnuevo() {
    // Solo la dueña crea arreglos (el servidor también lo bloquea)
    if (!esduena) {
        return;
    }

    await cargarproductos();
    datosservidor = {};
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Nuevo arreglo';
    botonguardar.textContent = 'Guardar arreglo';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    if (!esduena) {
        return;
    }

    await cargarproductos();
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosservidor = respuesta.datos;
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Editar arreglo';
    botonguardar.textContent = 'Guardar cambios';
    dialogoformulario.showModal();
}

async function verdetalle(id) {
    iddetalle = id;
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const arreglo = respuesta.datos;
    const filas = [
        ['Descripción', arreglo.tbarreglofloraldescripcion],
        ['Precio de venta', formatearmonto(arreglo.tbarreglofloralprecio)],
    ];

    // El costo y la ganancia solo los ve la dueña
    if (esduena) {
        filas.push(['Costo de productos', formatearmonto(arreglo.costo)]);
        filas.push(['Ganancia', formatearmonto(arreglo.tbarreglofloralprecio - arreglo.costo)]);
    }

    filas.push(['Estado', arreglo.tbarreglofloralestado === 1 ? 'Activo' : 'Inactivo']);

    fotodetalle.src = arreglo.tbarreglofloralfoto;
    fotodetalle.alt = arreglo.tbarreglofloralnombre;
    titulodetalle.textContent = arreglo.tbarreglofloralnombre;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

    cuerpodetalle.innerHTML = arreglo.detalles.map((detalle) => `
        <tr>
            <td>${escapar(detalle.tbinventarionombre)}</td>
            <td>${escapar(detalle.tbinventariocategoria)}</td>
            <td>${detalle.tbarreglofloraldetallecantidad}</td>
            <td>${esduena ? formatearmonto(detalle.tbinventariopreciounidad) : '—'}</td>
            <td>${esduena ? formatearmonto(detalle.tbarreglofloraldetallecantidad * detalle.tbinventariopreciounidad) : '—'}</td>
        </tr>`).join('');

    dialogodetalle.showModal();
}

async function cambiarestado(accion, id, nombre) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Inactivar arreglo?',
            mensaje: `${nombre} dejará de mostrarse como disponible en el catálogo. Podrá reactivarlo cuando lo necesite.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        }
        : {
            titulo: '¿Reactivar arreglo?',
            mensaje: `${nombre} volverá a estar disponible en el catálogo.`,
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
        cargararreglos();
    }
}

/* ---------- Eventos ---------- */

function agregarlinea() {
    lineas.push(lineavacia());
    dibujarlineas();
    guardarborrador();
    cuerpolineas.querySelector('tr:last-child select').focus();
}

campofoto.addEventListener('change', revisarfoto);

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbarreglofloralprecio') {
        campo.value = solodecimal(campo.value);
        actualizartotales();
    }

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error && campo.type !== 'file') {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');

    if (camposformulario.includes(campo.name)) {
        guardarborrador();
    }
});

cuerpolineas.addEventListener('input', (evento) => {
    const campo = evento.target;
    const fila = campo.closest('tr[data-indice]');
    if (!fila || campo.dataset.campo !== 'tbarreglofloraldetallecantidad') {
        return;
    }

    const linea = lineas[Number(fila.dataset.indice)];
    linea.tbarreglofloraldetallecantidad = soloentero(campo.value);
    actualizarfila(fila, linea);
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
    linea.tbinventariopreciounidad = producto ? producto.tbinventariopreciounidad : 0;

    if (producto && linea.tbarreglofloraldetallecantidad === '') {
        linea.tbarreglofloraldetallecantidad = '1';
    }

    dibujarlineas();
    guardarborrador();
});

cuerpolineas.addEventListener('click', (evento) => {
    const paso = evento.target.closest('button[data-paso]');
    const quitar = evento.target.closest('button[data-quitar]');

    if (paso) {
        const fila = paso.closest('tr[data-indice]');
        const linea = lineas[Number(fila.dataset.indice)];
        const actual = parseInt(linea.tbarreglofloraldetallecantidad, 10) || 0;
        linea.tbarreglofloraldetallecantidad = String(Math.min(999, Math.max(1, actual + Number(paso.dataset.paso))));
        actualizarfila(fila, linea);
    } else if (quitar) {
        lineas.splice(Number(quitar.dataset.quitar), 1);
        dibujarlineas();
        guardarborrador();
    }
});

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

    const esedicion = formulario.elements.tbarreglofloralid.value !== '';

    if (esedicion) {
        const nombre = formulario.elements.tbarreglofloralnombre.value.trim() || 'este arreglo';
        const aceptado = await confirmar({
            titulo: '¿Guardar los cambios?',
            mensaje: `Se actualizarán los datos de ${nombre}.`,
            textoaceptar: 'Guardar cambios',
        });

        if (!aceptado) {
            return;
        }
    }

    const datos = new FormData();
    datos.append('tbarreglofloralid', formulario.elements.tbarreglofloralid.value);
    camposformulario.forEach((campo) => {
        datos.append(campo, formulario.elements[campo].value);
    });
    if (campofoto.files[0]) {
        datos.append('tbarreglofloralfoto', campofoto.files[0]);
    }
    lineas.forEach((linea, indice) => {
        datos.append(`detalles[${indice}][tbinventarioid]`, linea.tbinventarioid);
        datos.append(`detalles[${indice}][tbarreglofloraldetallecantidad]`, linea.tbarreglofloraldetallecantidad);
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
        cargararreglos();
        return;
    }

    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }

    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
});

galeria.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) {
        return;
    }

    const id = boton.dataset.id;
    const nombre = boton.closest('.tarjeta-arreglo').dataset.nombre;

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
    temporizadorbusqueda = setTimeout(cargararreglos, 300);
});

filtroestado.addEventListener('change', cargararreglos);
const botonnuevo = document.getElementById('botonnuevo');
if (botonnuevo) {
    botonnuevo.addEventListener('click', abrirnuevo);
}
document.getElementById('botonagregarlinea').addEventListener('click', agregarlinea);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());

// "Ver historial" del arreglo que está abierto en el detalle (solo existe para la dueña)
let iddetalle = null;
const botonverhistorial = document.getElementById('botonverhistorial');
if (botonverhistorial) {
    botonverhistorial.addEventListener('click', () => ventanahistorial.mostrar('catalogo', iddetalle, titulodetalle.textContent));
}

document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

cargararreglos();