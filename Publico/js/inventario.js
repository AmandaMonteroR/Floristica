const rutabase = 'index.php?modulo=inventario';
const clavebase = 'floristica.borrador.inventario.';
const filtros = {
    letras: (valor) => valor.replace(/[^\p{L}\s]/gu, ''),
    nombre: (valor) => valor.replace(/[^\p{L}\d\s.,'&()-]/gu, ''),
    texto: (valor) => valor.replace(/[^\p{L}\d\s.,'&-]/gu, ''),
    entero: (valor) => valor.replace(/\D/g, ''),
    medida: (valor) => valor.replace(/[^\d.\sxX]/g, '').replace(/X/g, 'x'),
    decimal: (valor) => {
        const limpio = valor.replace(/,/g, '.').replace(/[^\d.]/g, '');
        const [entera, ...resto] = limpio.split('.');
        return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
    },
};
const etiquetascategoria = {
    Flor: {
        tbinventariocategoriaflortipo: 'Flor o follaje',
        tbinventariocategoriaflorvariedad: 'Tipo de flor',
        tbinventariocategoriaflorcolor: 'Color',
        tbinventariocategoriaflorlargotallo: 'Largo del tallo (cm)',
        tbinventariocategoriaflordiasduracion: 'Días de duración',
    },
    Envoltura: {
        tbinventariocategoriaenvolturaclase: 'Papel o cinta',
        tbinventariocategoriaenvolturatipo: 'Tipo',
        tbinventariocategoriaenvolturacolor: 'Color',
        tbinventariocategoriaenvolturadiseno: 'Diseño',
        tbinventariocategoriaenvolturamedida: 'Medida (cm)',
        tbinventariocategoriaenvolturamaterial: 'Material',
        tbinventariocategoriaenvolturaacabado: 'Acabado',
        tbinventariocategoriaenvolturapersonalizable: 'Personalizable',
    },
    Base: {
        tbinventariocategoriabasetipo: 'Tipo',
        tbinventariocategoriabasematerial: 'Material',
        tbinventariocategoriabasecolor: 'Color',
        tbinventariocategoriabaseforma: 'Forma',
        tbinventariocategoriabasemedida: 'Medidas (cm)',
        tbinventariocategoriabasediseno: 'Diseño',
    },
    Chocolate: {
        tbinventariocategoriachocolatetipo: 'Tipo',
        tbinventariocategoriachocolatemarca: 'Marca',
        tbinventariocategoriachocolatepresentacion: 'Presentación',
        tbinventariocategoriachocolatepeso: 'Peso (g)',
        tbinventariocategoriachocolatesabor: 'Sabor',
        tbinventariocategoriachocolatefechavencimiento: 'Fecha de vencimiento',
    },
    Peluche: {
        tbinventariocategoriapeluchetipo: 'Tipo',
        tbinventariocategoriapeluchetamano: 'Tamaño (cm)',
        tbinventariocategoriapeluchecolor: 'Color',
        tbinventariocategoriapeluchediseno: 'Diseño',
        tbinventariocategoriapeluchematerial: 'Material',
    },
    Tarjeta: {
        tbinventariocategoriatarjetatipo: 'Tipo de tarjeta',
        tbinventariocategoriatarjetatematica: 'Temática',
        tbinventariocategoriatarjetacolor: 'Color',
        tbinventariocategoriatarjetamaterial: 'Material',
        tbinventariocategoriatarjetamedida: 'Medidas (cm)',
        tbinventariocategoriatarjetapersonalizable: 'Personalizable',
        tbinventariocategoriatarjetaelaboracion: 'Elaboración',
    },
    Spray: {
        tbinventariocategoriaspraytipo: 'Tipo de spray',
        tbinventariocategoriaspraymarca: 'Marca',
        tbinventariocategoriaspraycolor: 'Color de la pintura',
        tbinventariocategoriasprayaroma: 'Aroma',
        tbinventariocategoriaspraycontenido: 'Contenido (ml)',
        tbinventariocategoriasprayfechavencimiento: 'Fecha de vencimiento',
        tbinventariocategoriaspraycobroflor: 'Cobro por flor pintada',
    },
    Accesorio: {
        tbinventariocategoriaaccesoriotipo: 'Tipo de accesorio',
        tbinventariocategoriaaccesoriotamano: 'Tamaño',
        tbinventariocategoriaaccesoriocolor: 'Color',
        tbinventariocategoriaaccesoriomaterial: 'Material',
    },
};


const cuerpotabla = document.getElementById('cuerpotabla');
const busqueda = document.getElementById('busqueda');
const filtrocategoria = document.getElementById('filtrocategoria');
const filtroestado = document.getElementById('filtroestado');
const filtrostock = document.getElementById('filtrostock');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const preciounidad = document.getElementById('preciounidad');
const gananciaunidad = document.getElementById('gananciaunidad');
const campoacabado = document.getElementById('campoacabado');
const campopersonalizable = document.getElementById('campopersonalizable');
const etiquetatipoenvoltura = document.getElementById('etiquetatipoenvoltura');
const etiquetamedidaenvoltura = document.getElementById('etiquetamedidaenvoltura');
const contenedortemporadas = document.getElementById('preciostemporada');
const contenedorotros = document.getElementById('otrosproveedores');
const botonotroproveedor = document.getElementById('botonotroproveedor');
const campospraycolor = document.getElementById('campospraycolor');
const camposprayaroma = document.getElementById('camposprayaroma');
const campospraycobro = document.getElementById('campospraycobro');
const dialogodetalle = document.getElementById('dialogodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const listadetalle = document.getElementById('listadetalle');
const cuerpomovimientos = document.getElementById('cuerpomovimientos');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const selectproveedor = formulario.elements.tbproveedorid;
const selectcategoria = formulario.elements.tbinventariocategoria;
const modalidadcompra = document.getElementById('modalidadcompra');

// Se mantienen los campos y las columnas actuales: por unidad equivale a un paquete de 1.
function actualizarmodalidadcompra() {
    const porunidad = modalidadcompra.value === 'unidad';
    const unidades = formulario.elements.tbinventariounidadespaquete;
    document.getElementById('campounidadespaquete').hidden = porunidad;
    document.getElementById('etiquetapreciocompra').textContent = porunidad
        ? 'Precio de compra por unidad (₡) *' : 'Precio del paquete (₡) *';
    if (porunidad) {
        unidades.value = '1';
    }
    unidades.required = !porunidad;
    document.getElementById('resultadocostounidad').hidden = porunidad;
    calcularpreciounidad();
    compararotros();
}



let temporizadorbusqueda;
let temporizadoraviso;
let datosservidor = {};
let datosoriginales = {};
let catalogos = { colores: [], tiposflor: [] };
let proveedoresactivos = [];
let filaotros = 0;


function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
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


function formatearfecha(fecha) {
    if (!fecha) {
        return '';
    }
    const [anio, mes, dia] = fecha.split('-');
    return `${dia}/${mes}/${anio}`;
}


function formatearmonto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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


async function cargarproveedores() {
    const respuesta = await consultar('proveedores');


    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje || 'No se pudieron cargar los proveedores.', 'error');
        return;
    }


    proveedoresactivos = respuesta.datos;
    llenarproveedores();
}


function llenarproveedores() {
    selectproveedor.innerHTML = '';
    selectproveedor.add(new Option('Seleccione…', ''));
    proveedoresactivos.forEach((proveedor) => {
        selectproveedor.add(new Option(proveedor.tbproveedornombreempresa, proveedor.tbproveedorid));
    });
}


function mostrarseccioncategoria() {
    formulario.querySelectorAll('.seccion-categoria[data-categoria]').forEach((seccion) => {
        seccion.hidden = seccion.dataset.categoria !== selectcategoria.value;
    });
    actualizarvariedades();
    actualizarenvoltura();
    actualizarspray();
}


function actualizarspray() {
    const tipo = formulario.elements.tbinventariocategoriaspraytipo.value;
    campospraycolor.hidden = tipo !== 'Pintura floral';
    campospraycobro.hidden = tipo !== 'Pintura floral';
    camposprayaroma.hidden = tipo !== 'Aromatizante floral';
}


async function cargarcatalogos() {
    const respuesta = await consultar('catalogos');
    if (respuesta.exito) {
        catalogos = respuesta.datos;
    }
}


function prepararcolores(datos = {}) {
    formulario.querySelectorAll('select[data-lista="colores"]').forEach((select) => {
        const elegido = String(datos[select.name] ?? '');
        const vacio = select.options[0] ? select.options[0].text : 'Seleccione…';
        select.innerHTML = '';
        select.add(new Option(vacio, ''));
        catalogos.colores
            .filter((color) => color.estado === 1 || String(color.id) === elegido)
            .forEach((color) => select.add(new Option(color.nombre + (color.estado === 1 ? '' : ' (inactivo)'), color.id)));
        select.value = elegido;
    });
}


function actualizarvariedades(elegido = formulario.elements.tbinventariocategoriaflorvariedad.value) {
    const clase = formulario.elements.tbinventariocategoriaflortipo.value;
    const select = formulario.elements.tbinventariocategoriaflorvariedad;
    const lista = catalogos.tiposflor.filter((tipo) => tipo.clase === clase
        && (tipo.estado === 1 || String(tipo.id) === String(elegido)));


    select.innerHTML = '';
    select.add(new Option(clase ? 'Seleccione…' : 'Primero elija flor o follaje', ''));
    lista.forEach((tipo) => select.add(new Option(tipo.nombre + (tipo.estado === 1 ? '' : ' (inactivo)'), tipo.id)));
    select.value = lista.some((tipo) => String(tipo.id) === String(elegido)) ? String(elegido) : '';
}


function actualizarenvoltura(tipoelegido = formulario.elements.tbinventariocategoriaenvolturatipo.value) {
    const clase = formulario.elements.tbinventariocategoriaenvolturaclase.value;
    const selecttipo = formulario.elements.tbinventariocategoriaenvolturatipo;
    const tipos = JSON.parse(selecttipo.dataset.opciones)[clase] ?? [];


    selecttipo.innerHTML = '';
    selecttipo.add(new Option(clase ? 'Seleccione…' : 'Primero elija papel o cinta', ''));
    tipos.forEach((tipo) => selecttipo.add(new Option(tipo, tipo)));
    selecttipo.value = tipos.includes(tipoelegido) ? tipoelegido : '';


    etiquetatipoenvoltura.textContent = clase === 'Cinta' ? 'Tipo de cinta *' : clase === 'Papel' ? 'Tipo de papel *' : 'Tipo *';
    campoacabado.hidden = clase !== 'Papel';
    campopersonalizable.hidden = clase !== 'Cinta';
    etiquetamedidaenvoltura.textContent = clase === 'Cinta' ? 'Ancho (cm)' : 'Medida del pliego (cm)';
    formulario.elements.tbinventariocategoriaenvolturamedida.placeholder = clase === 'Cinta' ? '2.5' : '60 x 60';
}


function numero(valor) {
    return parseFloat(String(valor).replace(',', '.'));
}


function calcularpreciounidad() {
    const precio = numero(formulario.elements.tbinventariopreciopaquete.value);
    const unidades = parseInt(formulario.elements.tbinventariounidadespaquete.value, 10);
    const venta = numero(formulario.elements.tbinventarioprecioventa.value);
    const costo = precio > 0 && unidades > 0 ? precio / unidades : null;


    preciounidad.textContent = costo !== null ? formatearmonto(costo) : '—';
    gananciaunidad.textContent = costo !== null && venta > 0 ? formatearmonto(venta - costo) : '—';
    gananciaunidad.classList.toggle('texto-peligro', costo !== null && venta > 0 && venta < costo);
}


function agregarotro(otro = {}) {
    const numerofila = filaotros++;
    const elegido = String(otro.tbproveedorid ?? '');
    const lista = [...proveedoresactivos];
    if (elegido !== '' && !lista.some((proveedor) => String(proveedor.tbproveedorid) === elegido)) {
        lista.push({ tbproveedorid: elegido, tbproveedornombreempresa: `${otro.tbproveedornombreempresa} (inactivo)` });
    }
    const opciones = lista.map((proveedor) => `<option value="${proveedor.tbproveedorid}"${String(proveedor.tbproveedorid) === elegido ? ' selected' : ''}>`
        + `${escapar(proveedor.tbproveedornombreempresa)}</option>`).join('');


    contenedorotros.insertAdjacentHTML('beforeend', `
        <div class="fila-otro">
            <label class="campo">
                <span>Proveedor adicional</span>
                <select name="otrosproveedores[${numerofila}][tbproveedorid]"><option value="">Seleccione…</option>${opciones}</select>
            </label>
            <input type="hidden" name="otrosproveedores[${numerofila}][preciopaquete]" value="${otro.preciopaquete !== undefined ? escapar(String(otro.preciopaquete)) : ''}">
            <button type="button" class="enlace enlace-peligro" data-quitarotro>Quitar</button>
                </div>`);
}


function dibujarotros(lista) {
    contenedorotros.innerHTML = '';
    (lista ?? []).forEach((otro) => agregarotro(otro));
    actualizarmodalidadcompra();
}


// Los proveedores adicionales usan el costo registrado en la sección Compra y venta.
// Se mantiene el campo oculto para respetar la estructura actual de la base de datos.
function compararotros() {
    const precio = formulario.elements.tbinventariopreciopaquete.value;
    contenedorotros.querySelectorAll('input[name$="[preciopaquete]"]').forEach((campo) => {
        campo.value = precio;
    });
}

function revisartemporadas() {
    preciostemporada.revisar(contenedortemporadas, formulario.querySelector('[data-error="temporadas"]'));
    preciostemporada.ventas(contenedortemporadas, numero(formulario.elements.tbinventariopreciopaquete.value),
        numero(formulario.elements.tbinventarioprecioventa.value));
}


function claveborrador() {
    const id = formulario.elements.tbinventarioid.value;
    return clavebase + (id !== '' ? id : 'nuevo');
}


function leerformulario() {
    const datos = {};
    Array.from(formulario.elements).forEach((campo) => {
        if (campo.name && campo.name !== 'tbinventarioid') {
            datos[campo.name] = campo.value;
        }
    });
    return datos;
}


function guardarborrador() {
    if (existente) {
        return;
    }
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
    Object.entries(datos).forEach(([campo, valor]) => {
        if (campo !== 'tbinventarioid' && formulario.elements[campo]) {
            formulario.elements[campo].value = valor ?? '';
        }
    });


    mostrarseccioncategoria();
    actualizarenvoltura(datos.tbinventariocategoriaenvolturatipo ?? '');
    actualizarvariedades(datos.tbinventariocategoriaflorvariedad ?? '');
    calcularpreciounidad();
}


function llenarformulario(datos) {
    formulario.reset();
    prepararcolores(datos.categoria ?? {});
    preciostemporada.dibujar(contenedortemporadas, datos.preciostemporada);
    formulario.elements.tbinventarioid.value = datos.tbinventarioid ?? '';
    dibujarotros(datos.otrosproveedores);


    if (datos.tbproveedorid && !selectproveedor.querySelector(`option[value="${datos.tbproveedorid}"]`)) {
        selectproveedor.add(new Option(`${datos.tbproveedornombreempresa} (inactivo)`, datos.tbproveedorid));
    }


    // Los productos antiguos conservan su modo de compra según sus unidades por paquete.
    modalidadcompra.value = Number(datos.tbinventariounidadespaquete) > 1 ? 'paquete'
        : (datos.tbinventarioid ? 'unidad' : (datos.tbinventariocategoria === 'Flor' ? 'paquete' : 'unidad'));
    asignarcampos({
        tbinventarionombre: datos.tbinventarionombre,
        tbproveedorid: datos.tbproveedorid,
        tbinventariocategoria: datos.tbinventariocategoria,
        tbinventariopreciopaquete: datos.tbinventariopreciopaquete,
        tbinventariounidadespaquete: datos.tbinventariounidadespaquete,
        tbinventarioprecioventa: datos.tbinventarioprecioventa,
        tbinventariostock: datos.tbinventariostock,
        tbinventariostockminimo: datos.tbinventariostockminimo,
        ...(datos.categoria ?? {}),
    });


    actualizarmodalidadcompra();
    preciostemporada.ventas(contenedortemporadas, Number(datos.tbinventariopreciopaquete), Number(datos.tbinventarioprecioventa));
    compararotros();
    datosoriginales = leerformulario();
}


function aplicarborrador() {
    const borrador = leerborrador();
    avisoborrador.hidden = true;


    if (!borrador) {
        return;
    }


    const haycambios = Object.keys(borrador).some(
        (campo) => (borrador[campo] ?? '') !== (datosoriginales[campo] ?? '')
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
    llenarformulario(datosservidor);
    limpiarfototemporal();
    mostrarfoto({ tbinventariofoto: datosservidor.tbinventariofoto || '', tbinventarionombre: datosservidor.tbinventarionombre || '' });
    limpiarerrores();
    avisoborrador.hidden = true;
}


function nivelstock(producto) {
    const stock = producto.tbinventariodisponible;
    const minimo = producto.tbinventariostockminimo;


    if (stock <= minimo) {
        return { clase: 'stock-bajo', texto: 'Bajo el mínimo' };
    }
    if (stock <= Math.ceil(minimo * 1.5)) {
        return { clase: 'stock-medio', texto: 'Cerca del mínimo' };
    }
    return { clase: 'stock-bien', texto: 'Suficiente' };
}


function textoapartado(producto) {
    if (producto.tbinventariostockreservado <= 0) {
        return '';
    }
    const texto = producto.tbinventariofaltante > 0
        ? `<span class="aviso-stock">Faltan ${producto.tbinventariofaltante}</span>`
        : `${producto.tbinventariodisponible} libres`;
    return `<small class="texto-suave nota-apartado" title="Apartado para encargos abiertos">${producto.tbinventariostockreservado} ${producto.tbinventariostockreservado === 1 ? 'apartada' : 'apartadas'} · ${texto}</small>`;
}


async function cargarinventario() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        categoria: filtrocategoria.value,
        estado: filtroestado.value,
    });


    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="9" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }


    const productos = filtrostock.value === ''
        ? respuesta.datos
        : respuesta.datos.filter((producto) => nivelstock(producto).clase === `stock-${filtrostock.value}`);


    if (productos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtrocategoria.value === ''
            && filtroestado.value === '' && filtrostock.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay insumos registrados. Use "Nuevo insumo" para agregar el primero.'
            : 'No hay productos que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="9" class="vacio">${mensaje}</td></tr>`;
        return;
    }


    cuerpotabla.innerHTML = productos.map((producto) => {
        const activo = producto.tbinventarioestado === 1;
        const nivel = nivelstock(producto);


        return `
            <tr class="${activo ? '' : 'fila-inactiva'}">
                <td class="celda-foto-inventario" data-etiqueta="Foto">${producto.tbinventariofoto
                    ? `<img class="miniatura-inventario" src="${escapar(producto.tbinventariofoto).replace(/"/g, '"')}" alt="" loading="lazy">`
                    : '<span class="miniatura-vacia-inventario" title="Sin foto" aria-label="Sin foto">✿</span>'}</td>
                <td>${escapar(producto.tbinventarionombre)}</td>
                <td>${escapar(producto.tbinventariocategoria)}</td>
                <td>${escapar(producto.tbproveedornombreempresa)}${producto.cantidadotrosproveedores > 0
                    ? `<small class="texto-suave nota-apartado">y ${producto.cantidadotrosproveedores} ${producto.cantidadotrosproveedores === 1 ? 'proveedor más' : 'proveedores más'}</small>` : ''}</td>
                <td>${formatearmonto(producto.preciounidadactual)}</td>
                <td>${formatearmonto(producto.precioventaactual)}${producto.temporadaprecio
                    ? `<br><span class="estado estado-vigente">${escapar(producto.temporadaprecio)}</span>` : ''}</td>
                <td><span class="stock ${nivel.clase}" title="${nivel.texto} (mínimo: ${producto.tbinventariostockminimo})">${producto.tbinventariostock}</span>${textoapartado(producto)}</td>
                <td><span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="columna-acciones">
                    <button type="button" class="enlace" data-accion="ver" data-id="${producto.tbinventarioid}">Ver</button>
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


function modostock(producto) {
    const editando = producto !== null;
    document.getElementById('etiquetastock').textContent = editando ? 'Stock actual (unidades)' : 'Stock inicial (unidades) *';
    document.getElementById('contadorstock').hidden = editando;
    document.getElementById('stockfijo').hidden = !editando;
    if (editando) {
        formulario.elements.tbinventariostock.value = producto.tbinventariostock;
        document.getElementById('stockfijovalor').textContent = `${producto.tbinventariostock} ${Number(producto.tbinventariostock) === 1 ? 'unidad' : 'unidades'}`;
    }
}


// Retorno desde el registro de Proveedores sin perder el insumo que se estaba creando.
const claveregresoproveedor = 'floristica.inventario.regresoproveedor';
async function registrarproveedordesdeinsumo(enlace) {
    if (formulario.elements.tbinventarioid.value) {
        // En edición se mantiene el formulario actual: los nuevos proveedores se registran aparte.
        guardarborrador();
    }
    // Un solo enlace: si falta el principal, se completa ese primero.
    // Si ya existe y hay un proveedor adicional sin elegir, se completa el adicional.
    const filasotros = [...contenedorotros.querySelectorAll('.fila-otro')];
    const indicevacio = filasotros.findIndex((fila) => !fila.querySelector('select').value);
    const destino = !selectproveedor.value || indicevacio < 0 ? 'principal' : String(indicevacio);
    const campos = leerformulario();
    const otros = [...contenedorotros.querySelectorAll('.fila-otro')].map((fila) => ({
        tbproveedorid: fila.querySelector('select').value,
        preciopaquete: formulario.elements.tbinventariopreciopaquete.value,
    }));
    const estado = { campos, otros, destino, modalidad: modalidadcompra.value };
    const foto = campofoto.files[0];
    if (foto && !formulario.elements.tbinventarioid.value) {
        try {
            estado.foto = await new Promise((resolver, rechazar) => {
                const lector = new FileReader();
                lector.onload = () => resolver({ nombre: foto.name, tipo: foto.type, datos: lector.result });
                lector.onerror = rechazar;
                lector.readAsDataURL(foto);
            });
        } catch (error) {
            // El resto del formulario se conserva incluso si la foto no puede leerse.
        }
    }
    try {
        sessionStorage.setItem(claveregresoproveedor, JSON.stringify(estado));
    } catch (error) {
        // Si el navegador no admite guardar la foto, conservar al menos los campos.
        delete estado.foto;
        try { sessionStorage.setItem(claveregresoproveedor, JSON.stringify(estado)); } catch (_) { return; }
    }
    window.location.href = enlace.href;
}

async function restaurarinsumodesdeproveedor() {
    const params = new URLSearchParams(location.search);
    if (params.get('volver') !== 'proveedor') return;
    let estado;
    try { estado = JSON.parse(sessionStorage.getItem(claveregresoproveedor) || 'null'); }
    catch (_) { estado = null; }
    if (!estado) return;
    await abrirnuevo();
    modalidadcompra.value = estado.modalidad === 'paquete' ? 'paquete' : 'unidad';
    dibujarotros(estado.otros || []);
    asignarcampos(estado.campos || {});
    actualizarmodalidadcompra();
    const id = params.get('proveedorid');
    if (id && proveedoresactivos.some((p) => String(p.tbproveedorid) === id)) {
        if (estado.destino === 'principal') {
            selectproveedor.value = id;
        } else {
            const selectores = [...contenedorotros.querySelectorAll('.fila-otro select')];
            const indice = Number(estado.destino);
            const destino = selectores[indice];
            if (destino) destino.value = id;
        }
    }
    if (estado.foto && !formulario.elements.tbinventarioid.value) {
        try {
            const respuesta = await fetch(estado.foto.datos);
            const blob = await respuesta.blob();
            const archivo = new File([blob], estado.foto.nombre, { type: estado.foto.tipo });
            const transferencia = new DataTransfer();
            transferencia.items.add(archivo);
            campofoto.files = transferencia.files;
            campofoto.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (_) {
            errorfoto.textContent = 'Vuelva a seleccionar la fotografía para guardarla.';
        }
    }
    sessionStorage.removeItem(claveregresoproveedor);
    params.delete('nuevo'); params.delete('volver'); params.delete('proveedorid');
    history.replaceState(null, '', `${location.pathname}?${params}`);
    guardarborrador();
}

dialogoformulario.addEventListener('click', (evento) => {
    const enlace = evento.target.closest('a.enlaceregistrarproveedor');
    if (!enlace) return;
    evento.preventDefault();
    registrarproveedordesdeinsumo(enlace);
});

async function abrirnuevo() {
    await proveedoreslistos;
    await catalogoslistos;
    const temporadas = await consultar('temporadas');
    datosservidor = { preciostemporada: temporadas.exito ? temporadas.datos : [] };
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    salirexistente();
    modostock(null);
    limpiarfototemporal();
    document.getElementById('seccionfoto').hidden = false;
    document.getElementById('notafoto').textContent = 'Se guardará al registrar el insumo.';
    mostrarfoto({ tbinventariofoto: '', tbinventarionombre: '' });
    tituloformulario.textContent = 'Nuevo insumo';
    botonguardar.textContent = 'Guardar insumo';
    dialogoformulario.showModal();
}


async function abrireditar(id) {
    await proveedoreslistos;
    await catalogoslistos;
    const respuesta = await consultar('obtener', { id });


    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }


    datosservidor = respuesta.datos;
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    salirexistente();
    modostock(datosservidor);
    limpiarfototemporal();
    document.getElementById('seccionfoto').hidden = false;
    document.getElementById('notafoto').textContent = 'Se guarda al seleccionarla.';
    mostrarfoto(datosservidor);
    tituloformulario.textContent = 'Editar insumo';
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


    const producto = respuesta.datos;
    const nombrecategoria = { Flor: 'la flor o follaje', Envoltura: 'la envoltura', Base: 'la base', Chocolate: 'el chocolate',
        Peluche: 'el peluche', Tarjeta: 'la tarjeta', Spray: 'el spray', Accesorio: 'el accesorio' };
    const filas = [
        ['Categoría', producto.tbinventariocategoria],
        ['Estado', producto.tbinventarioestado === 1 ? 'Activo' : 'Inactivo'],
        ['§', 'Stock'],
        ['Stock actual', `${producto.tbinventariostock} ${producto.tbinventariostock === 1 ? 'unidad' : 'unidades'}`],
        ['Apartado para encargos', `${producto.tbinventariostockreservado}${producto.tbinventariofaltante > 0
            ? ` (faltan ${producto.tbinventariofaltante} para prepararlas todas)` : ''}`],
        ['Disponible', `${producto.tbinventariodisponible} (${nivelstock(producto).texto.toLowerCase()})`],
        ['Stock mínimo', String(producto.tbinventariostockminimo)],
        ['§', 'Proveedores y precios'],
        ['Proveedor', `${producto.tbproveedornombreempresa}${(producto.otrosproveedores ?? []).length > 0 ? ' (principal)' : ''}`],
        ...(producto.otrosproveedores ?? []).map((otro) => {
            const diferencia = (otro.preciopaquete - producto.tbinventariopreciopaquete) / Math.max(1, producto.tbinventariounidadespaquete);
            const comparacion = Math.abs(diferencia) < 0.01 ? 'igual que el principal'
                : `${formatearmonto(Math.abs(diferencia))} ${diferencia < 0 ? 'más barato' : 'más caro'} la unidad`;
            return [`También: ${otro.tbproveedornombreempresa}${otro.activo ? '' : ' (inactivo)'}`,
                `${formatearmonto(otro.preciopaquete)} el paquete (${formatearmonto(otro.preciounidad)} la unidad) · ${comparacion}`];
        }),
        ['Precio del paquete', formatearmonto(producto.tbinventariopreciopaquete)],
        ['Unidades por paquete', String(producto.tbinventariounidadespaquete)],
        ['Costo por unidad', formatearmonto(producto.tbinventariopreciounidad)],
        ['Precio de venta por unidad', formatearmonto(producto.tbinventarioprecioventa)],
        ['Ganancia por unidad', formatearmonto(producto.tbinventarioprecioventa - producto.tbinventariopreciounidad)],
        ...(producto.preciostemporada ?? []).filter((temporada) => temporada.precio !== null).map((temporada) => {
            const venta = Math.abs(temporada.precio - producto.tbinventariopreciopaquete) < 0.01
                ? producto.tbinventarioprecioventa
                : preciostemporada.redondear(producto.tbinventarioprecioventa * temporada.precio / producto.tbinventariopreciopaquete);
            return [
                `En ${temporada.tbtemporadanombre}${temporada.vigente ? ' (en uso)' : ''}`,
                `Proveedor ${formatearmonto(temporada.precio)} el paquete · se vende a ${formatearmonto(venta)} la unidad`,
            ];
        }),
        ['§', `Datos de ${nombrecategoria[producto.tbinventariocategoria] ?? 'la categoría'}`],
    ];


    const etiquetas = etiquetascategoria[producto.tbinventariocategoria] ?? {};
    Object.entries(etiquetas).forEach(([campo, etiqueta]) => {
        let valor = producto.categoria[campo];
        if (producto.tbinventariocategoria === 'Envoltura' && valor === null
            && (campo.endsWith('acabado') || campo.endsWith('personalizable'))) {
            return;
        }
        if (producto.tbinventariocategoria === 'Spray' && valor === null && !campo.endsWith('fechavencimiento')) {
            return;
        }
        if (campo === 'tbinventariocategoriachocolatefechavencimiento' || campo === 'tbinventariocategoriasprayfechavencimiento') {
            valor = valor ? formatearfecha(valor) : 'No vence';
        }
        if (campo === 'tbinventariocategoriaspraycobroflor') {
            valor = formatearmonto(valor);
        }
        if (campo.endsWith('personalizable') && valor !== null && valor !== undefined) {
            valor = Number(valor) === 1 ? 'Sí' : 'No';
        }
        if ((campo + 'nombre') in producto.categoria) {
            valor = producto.categoria[campo + 'nombre'];
        }
        filas.push([etiqueta, valor === null || valor === undefined ? '' : String(valor)]);
    });


    titulodetalle.textContent = producto.tbinventarionombre;
    mostrarfoto(producto);
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => (etiqueta === '§'
            ? `<dt class="detalle-seccion">${escapar(valor)}</dt>`
            : `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`))
        .join('');
    document.getElementById('botonstockdetalle').hidden = producto.tbinventarioestado !== 1;
    document.getElementById('botondanadasdetalle').hidden = producto.tbinventarioestado !== 1 || producto.tbinventariostock === 0;
    const botonestado = document.getElementById('botonestadodetalle');
    botonestado.dataset.accion = producto.tbinventarioestado === 1 ? 'inactivar' : 'reactivar';
    botonestado.textContent = producto.tbinventarioestado === 1 ? 'Inactivar' : 'Reactivar';
    botonestado.classList.toggle('boton-peligro', producto.tbinventarioestado === 1);


    cuerpomovimientos.innerHTML = producto.movimientos.length === 0
        ? '<tr><td colspan="5" class="vacio">Este producto todavía no tiene movimientos registrados.</td></tr>'
        : producto.movimientos.map((movimiento) => {
            const entra = movimiento.cantidad >= 0;
            return `
            <tr>
                <td>${formatearfechahora(movimiento.fecha)}</td>
                <td>${escapar(movimiento.motivo)}</td>
                <td class="${entra ? 'cantidad-entrada' : 'cantidad-salida'}">${entra ? '+' : '−'}${Math.abs(movimiento.cantidad)}</td>
                <td>${movimiento.stockfinal}</td>
                <td>${movimiento.hechopor ? escapar(movimiento.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
            </tr>`;
        }).join('');


    dialogodetalle.showModal();
}


const fotodetalle = document.getElementById('fotodetalle');
const fotovacia = document.getElementById('fotovacia');
const fotodetallever = document.getElementById('fotodetallever');
const fotovaciaver = document.getElementById('fotovaciaver');
const campofoto = document.getElementById('campofoto');
const botonfoto = document.getElementById('botonfoto');
const botonquitarfoto = document.getElementById('botonquitarfoto');
const errorfoto = document.getElementById('errorfoto');
let urlfototemporal = null;
function limpiarfototemporal() {
    if (urlfototemporal) {
        URL.revokeObjectURL(urlfototemporal);
        urlfototemporal = null;
    }
    campofoto.value = '';
}
function mostrarfoto(producto) {
    const foto = producto.tbinventariofoto;
    fotodetalle.hidden = !foto;
    fotodetalle.src = foto || '';
    fotodetalle.alt = producto.tbinventarionombre;
    fotovacia.hidden = Boolean(foto);
    fotodetallever.hidden = !foto;
    fotodetallever.src = foto || '';
    fotodetallever.alt = producto.tbinventarionombre;
    fotovaciaver.hidden = Boolean(foto);
    botonfoto.textContent = foto ? 'Cambiar foto' : 'Agregar foto';
    botonfoto.disabled = false;
    botonquitarfoto.hidden = !foto;
    errorfoto.textContent = '';
    errorfoto.className = 'error';
}


async function terminarfoto(respuesta) {
    if (!respuesta.exito) {
        errorfoto.textContent = respuesta.mensaje;
        errorfoto.className = 'error';
        botonfoto.disabled = false;
        botonfoto.textContent = fotodetalle.hidden ? 'Agregar foto' : 'Cambiar foto';
        return;
    }
    datosservidor.tbinventariofoto = respuesta.datos.tbinventariofoto;
    mostrarfoto({
        tbinventariofoto: respuesta.datos.tbinventariofoto,
        tbinventarionombre: formulario.elements.tbinventarionombre.value,
    });
    errorfoto.textContent = respuesta.mensaje;
    errorfoto.className = 'texto-exito';
}


async function subirfoto() {
    const archivo = campofoto.files[0];
    if (!archivo) {
        return;
    }
    if (archivo.size > 2 * 1024 * 1024) {
        if (formulario.elements.tbinventarioid.value === '') {
            limpiarfototemporal();
            mostrarfoto({ tbinventariofoto: '', tbinventarionombre: '' });
        } else {
            campofoto.value = '';
        }
        errorfoto.textContent = 'La foto no puede pesar más de 2 MB.';
        errorfoto.className = 'error';
        return;
    }


    if (formulario.elements.tbinventarioid.value === '') {
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) {
            limpiarfototemporal();
            mostrarfoto({ tbinventariofoto: '', tbinventarionombre: '' });
            errorfoto.textContent = 'La foto debe ser JPG, PNG o WEBP.';
            errorfoto.className = 'error';
            return;
        }
        if (urlfototemporal) {
            URL.revokeObjectURL(urlfototemporal);
        }
        urlfototemporal = URL.createObjectURL(archivo);
        mostrarfoto({ tbinventariofoto: urlfototemporal, tbinventarionombre: formulario.elements.tbinventarionombre.value || 'Foto del producto' });
        return;
    }
    const datos = new FormData();
    datos.append('id', formulario.elements.tbinventarioid.value);
    datos.append('tbinventariofoto', archivo);
    botonfoto.disabled = true;
    botonfoto.textContent = 'Subiendo…';
    const respuesta = await enviar('subirfoto', datos);
    campofoto.value = '';
    await terminarfoto(respuesta);
}


async function quitarfoto() {
    if (formulario.elements.tbinventarioid.value === '') {
        limpiarfototemporal();
        mostrarfoto({ tbinventariofoto: '', tbinventarionombre: '' });
        return;
    }
    const datos = new FormData();
    datos.append('id', formulario.elements.tbinventarioid.value);
    botonquitarfoto.disabled = true;
    const respuesta = await enviar('quitarfoto', datos);
    botonquitarfoto.disabled = false;
    await terminarfoto(respuesta);
}


botonfoto.addEventListener('click', () => campofoto.click());
campofoto.addEventListener('change', subirfoto);
botonquitarfoto.addEventListener('click', quitarfoto);


async function cambiarestado(accion, id, nombre) {
    const configuracion = accion === 'inactivar'
        ? {
            titulo: '¿Inactivar producto?',
            mensaje: `${nombre} quedará inactivo y no se podrá usar en pedidos ni en arreglos. Podrá reactivarlo cuando lo necesite.`,
            textoaceptar: 'Inactivar',
            peligro: true,
        }
        : {
            titulo: '¿Reactivar producto?',
            mensaje: `${nombre} volverá a estar disponible para pedidos y arreglos.`,
            textoaceptar: 'Reactivar',
        };


    if (!(await confirmar(configuracion))) {
        return false;
    }


    const datos = new FormData();
    datos.append('id', id);


    const respuesta = await enviar(accion, datos);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');


    if (respuesta.exito) {
        cargarinventario();
    }
    return respuesta.exito;
}


formulario.addEventListener('input', (evento) => {
    const campo = evento.target;
    const filtro = filtros[campo.dataset.filtro];


    if (filtro) {
        const valor = filtro(campo.value);
        if (valor !== campo.value) {
            campo.value = valor;
        }
    }


    preciostemporada.filtrar(campo);


    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');


    if (campo.name.startsWith('temporadas[') || campo.name === 'tbinventariopreciopaquete'
        || campo.name === 'tbinventarioprecioventa') {
        revisartemporadas();
    }
    if (campo.name.startsWith('otrosproveedores[') || campo.name === 'tbinventariopreciopaquete'
        || campo.name === 'tbinventariounidadespaquete') {
        formulario.querySelector('[data-error="otrosproveedores"]').textContent = '';
        compararotros();
    }
    guardarborrador();
});


formulario.addEventListener('change', guardarborrador);


const limitescontador = {
    tbinventariounidadespaquete: [1, 10000],
    tbinventariostock: [0, 999999],
    tbinventariostockminimo: [0, 999999],
};


formulario.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-paso][data-campo]');
    if (!boton) {
        return;
    }


    const campo = formulario.elements[boton.dataset.campo];
    const [minimo, maximo] = limitescontador[boton.dataset.campo];
    const actual = parseInt(campo.value, 10);
    const nuevo = Number.isNaN(actual) ? minimo : actual + Number(boton.dataset.paso);


    campo.value = String(Math.min(maximo, Math.max(minimo, nuevo)));
    campo.dispatchEvent(new Event('input', { bubbles: true }));
});
botonotroproveedor.addEventListener('click', () => {
    agregarotro();
    contenedorotros.querySelector('.fila-otro:last-child select').focus();
});
contenedorotros.addEventListener('click', (evento) => {
    const boton = evento.target.closest('[data-quitarotro]');
    if (boton) {
        boton.closest('.fila-otro').remove();
        formulario.querySelector('[data-error="otrosproveedores"]').textContent = '';
        guardarborrador();
    }
});
modalidadcompra.addEventListener('change', () => {
    // Al cambiar de modalidad, evitar que costos de paquete se interpreten como costos unitarios.
    formulario.elements.tbinventariopreciopaquete.value = '';
    contenedorotros.querySelectorAll('input[name$="[preciopaquete]"]').forEach((campo) => { campo.value = ''; });
    actualizarmodalidadcompra();
    revisartemporadas();
    guardarborrador();
});
selectcategoria.addEventListener('change', () => {
    mostrarseccioncategoria();
    // En un insumo nuevo, las flores empiezan por paquete y los regalos por unidad.
    if (!formulario.elements.tbinventarioid.value && !formulario.elements.tbinventariopreciopaquete.value) {
        modalidadcompra.value = selectcategoria.value === 'Flor' ? 'paquete' : 'unidad';
        actualizarmodalidadcompra();
    }
});
formulario.elements.tbinventariocategoriaflortipo.addEventListener('change', () => actualizarvariedades());
formulario.elements.tbinventariocategoriaenvolturaclase.addEventListener('change', () => actualizarenvoltura());
formulario.elements.tbinventariocategoriaspraytipo.addEventListener('change', actualizarspray);
formulario.elements.tbinventariopreciopaquete.addEventListener('input', calcularpreciounidad);
formulario.elements.tbinventariounidadespaquete.addEventListener('input', calcularpreciounidad);
formulario.elements.tbinventarioprecioventa.addEventListener('input', calcularpreciounidad);


formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();


    if (existente) {
        guardarcompra();
        return;
    }


    const esedicion = formulario.elements.tbinventarioid.value !== '';


    if (esedicion) {
        const nombre = formulario.elements.tbinventarionombre.value.trim() || 'este producto';
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
    // Evita duplicar precios visibles, manteniendo el campo requerido por el controlador.
    compararotros();
    botonguardar.disabled = true;
    const fotonueva = !esedicion ? campofoto.files[0] : null;
    const respuesta = await enviar(esedicion ? 'actualizar' : 'crear', new FormData(formulario));
    if (respuesta.exito && fotonueva) {
        const datosfoto = new FormData();
        datosfoto.append('id', respuesta.datos.tbinventarioid);
        datosfoto.append('tbinventariofoto', fotonueva);
        const resultadofoto = await enviar('subirfoto', datosfoto);
        if (!resultadofoto.exito) {
            borrarborrador(clave);
            avisoborrador.hidden = true;
            botonguardar.disabled = false;
            dialogoformulario.close();
            mostraraviso('El insumo se registró, pero no se guardó su fotografía: ' + resultadofoto.mensaje + ' Puede agregarla desde Editar.', 'error');
            cargarinventario();
            return;
        }
    }
    botonguardar.disabled = false;
    if (respuesta.exito) {
        borrarborrador(clave);
        avisoborrador.hidden = true;
        dialogoformulario.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarinventario();
        return;
    }


    if (respuesta.datos && respuesta.datos.errores) {
        mostrarerrores(respuesta.datos.errores);
    }


    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;


    if (respuesta.datos && respuesta.datos.existente) {
        avisoformulario.hidden = true;
        entrarexistente(respuesta.datos.existente.tbinventarioid);
    }
});


cuerpotabla.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-accion]');
    if (!boton) {
        return;
    }


    if (boton.dataset.accion === 'ver') {
        verdetalle(boton.dataset.id);
    }
});


busqueda.addEventListener('input', () => {
    clearTimeout(temporizadorbusqueda);
    temporizadorbusqueda = setTimeout(cargarinventario, 300);
});


filtrocategoria.addEventListener('change', cargarinventario);
filtroestado.addEventListener('change', cargarinventario);
filtrostock.addEventListener('change', cargarinventario);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrarformulario').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());
document.getElementById('botoneditardetalle').addEventListener('click', () => {
    dialogodetalle.close();
    abrireditar(iddetalle);
});
document.getElementById('botonestadodetalle').addEventListener('click', async (evento) => {
    const id = iddetalle;
    const cambiado = await cambiarestado(evento.currentTarget.dataset.accion, id, titulodetalle.textContent);
    if (cambiado && dialogodetalle.open) {
        dialogodetalle.close();
        verdetalle(id);
    }
});


let iddetalle = null;
const botonverhistorial = document.getElementById('botonverhistorial');
if (botonverhistorial) {
    botonverhistorial.addEventListener('click', () => ventanahistorial.mostrar('inventario', iddetalle, titulodetalle.textContent));
}
document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);


const vencimientominimo = new Date();
vencimientominimo.setDate(vencimientominimo.getDate() + 30);
formulario.elements.tbinventariocategoriachocolatefechavencimiento.min = vencimientominimo.toLocaleDateString('en-CA');
formulario.elements.tbinventariocategoriasprayfechavencimiento.min = vencimientominimo.toLocaleDateString('en-CA');


const normalizar = (texto) => String(texto ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim().toLowerCase();
const avisoexistente = document.getElementById('avisoexistente');
const campopaquetes = document.getElementById('campopaquetes');
const resultadocompra = document.getElementById('resultadocompra');
let existente = null;


function vendedores(producto) {
    return [
        { id: String(producto.tbproveedorid), nombre: producto.tbproveedornombreempresa, precio: Number(producto.tbinventariopreciopaquete), principal: true },
        ...(producto.otrosproveedores ?? []).filter((otro) => otro.activo).map((otro) => ({
            id: String(otro.tbproveedorid), nombre: otro.tbproveedornombreempresa, precio: Number(otro.preciopaquete), principal: false,
        })),
    ];
}


function llenarvendedores(select, producto) {
    select.innerHTML = '';
    vendedores(producto).forEach((vendedor) => {
        select.add(new Option(`${vendedor.nombre}${vendedor.principal ? ' (principal)' : ''} · ${formatearmonto(vendedor.precio)} el paquete`, vendedor.id));
    });
}


async function entrarexistente(id) {
    const respuesta = await consultar('obtener', { id });
    if (!respuesta.exito) {
        return;
    }
    const producto = respuesta.datos;
    if (producto.tbinventarioestado !== 1) {
        const error = formulario.querySelector('[data-error="tbinventarionombre"]');
        error.textContent = `Ya existe "${producto.tbinventarionombre}", pero está inactivo. Reactívelo en la lista para volver a usarlo.`;
        formulario.elements.tbinventarionombre.setAttribute('aria-invalid', 'true');
        return;
    }


    borrarborrador();
    existente = producto;
    llenarformulario(producto);
    limpiarfototemporal();
    document.getElementById('seccionfoto').hidden = true;
    modostock(producto);
    formulario.elements.tbinventarioid.value = '';
    limpiarerrores();


    Array.from(formulario.elements).forEach((campo) => {
        const libre = ['tbinventarionombre', 'tbproveedorid', 'paquetescompra'].includes(campo.name)
            || campo.dataset.pasopaquete !== undefined || ['botoncancelar', 'botonguardar', 'botonotroinsumo'].includes(campo.id);
        if (!libre) {
            campo.disabled = true;
            campo.dataset.bloqueadoexistente = '1';
        }
    });
    document.getElementById('botonstockformulario').hidden = true;
    document.getElementById('etiquetaproveedor').textContent = '¿A qué proveedor se lo compró? *';
    llenarvendedores(selectproveedor, producto);
    campopaquetes.hidden = false;
    resultadocompra.hidden = false;
    formulario.elements.paquetescompra.value = '1';
    document.getElementById('textoexistente').textContent = `"${producto.tbinventarionombre}" ya está registrado. Elija a qué proveedor se lo compró `
        + `y cuántos paquetes de ${producto.tbinventariounidadespaquete} unidades: se suman al stock que ya tiene.`;
    avisoexistente.hidden = false;
    tituloformulario.textContent = `Agregar stock a ${producto.tbinventarionombre}`;
    botonguardar.textContent = 'Sumar al stock';
    calcularcompra();
    formulario.elements.paquetescompra.focus();
    formulario.elements.paquetescompra.select();
}


function salirexistente(nombre = null) {
    Array.from(formulario.elements).forEach((campo) => {
        if (campo.dataset.bloqueadoexistente) {
            campo.disabled = false;
            delete campo.dataset.bloqueadoexistente;
        }
    });
    document.getElementById('botonstockformulario').hidden = false;
    document.getElementById('etiquetaproveedor').textContent = 'Proveedor *';
    campopaquetes.hidden = true;
    resultadocompra.hidden = true;
    avisoexistente.hidden = true;
    formulario.querySelector('[data-error="paquetes"]').textContent = '';


    if (!existente) {
        return;
    }
    existente = null;
    llenarproveedores();
    llenarformulario({ preciostemporada: datosservidor.preciostemporada ?? [] });
    limpiarfototemporal();
    document.getElementById('seccionfoto').hidden = false;
    mostrarfoto({ tbinventariofoto: '', tbinventarionombre: '' });
    modostock(null);
    formulario.elements.tbinventarionombre.value = nombre ?? '';
    tituloformulario.textContent = 'Nuevo insumo';
    botonguardar.textContent = 'Guardar insumo';
}


function calcularcompra() {
    if (!existente) {
        return;
    }
    const vendedor = vendedores(existente).find((item) => item.id === selectproveedor.value);
    const paquetes = parseInt(formulario.elements.paquetescompra.value, 10);
    if (Number.isNaN(paquetes) || paquetes < 1) {
        resultadocompra.textContent = '';
        return;
    }
    const unidades = paquetes * existente.tbinventariounidadespaquete;
    resultadocompra.textContent = `Se suman ${unidades} unidades: el stock pasa de ${existente.tbinventariostock} a ${existente.tbinventariostock + unidades}`
        + (vendedor ? ` · ${formatearmonto(vendedor.precio * paquetes)} a ${vendedor.nombre}` : '');
}


async function guardarcompra() {
    limpiarerrores();
    const datos = new FormData();
    datos.append('tbinventarioid', existente.tbinventarioid);
    datos.append('sentido', 'sumar');
    datos.append('tbproveedorid', selectproveedor.value);
    datos.append('paquetes', formulario.elements.paquetescompra.value);


    botonguardar.disabled = true;
    const respuesta = await enviar('movimientostock', datos);
    botonguardar.disabled = false;


    if (respuesta.exito) {
        salirexistente();
        dialogoformulario.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarinventario();
        return;
    }


    mostrarerrores((respuesta.datos && respuesta.datos.errores) || {});
    avisoformulario.textContent = respuesta.mensaje;
    avisoformulario.hidden = false;
}


formulario.elements.tbinventarionombre.addEventListener('change', async () => {
    const campo = formulario.elements.tbinventarionombre;
    const nombre = campo.value.trim();
    if (formulario.elements.tbinventarioid.value !== '' || nombre === '') {
        return;
    }
    if (existente) {
        if (normalizar(nombre) !== normalizar(existente.tbinventarionombre)) {
            salirexistente(campo.value);
        } else {
            return;
        }
    }


    const respuesta = await consultar('listar', { texto: nombre, estado: '' });
    const igual = respuesta.exito
        ? respuesta.datos.find((producto) => normalizar(producto.tbinventarionombre) === normalizar(nombre))
        : null;
    if (igual && campo.value.trim() === nombre && formulario.elements.tbinventarioid.value === '') {
        entrarexistente(igual.tbinventarioid);
    }
});


selectproveedor.addEventListener('change', calcularcompra);
formulario.elements.paquetescompra.addEventListener('input', (evento) => {
    evento.target.value = evento.target.value.replace(/\D/g, '').slice(0, 4);
    formulario.querySelector('[data-error="paquetes"]').textContent = '';
    calcularcompra();
});
formulario.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-pasopaquete]');
    if (!boton) {
        return;
    }
    const campo = formulario.elements.paquetescompra;
    const actual = parseInt(campo.value, 10);
    campo.value = String(Math.min(9999, Math.max(1, (Number.isNaN(actual) ? 0 : actual) + Number(boton.dataset.pasopaquete))));
    campo.dispatchEvent(new Event('input', { bubbles: true }));
});
document.getElementById('botonotroinsumo').addEventListener('click', () => {
    salirexistente('');
    formulario.elements.tbinventarionombre.focus();
});
dialogoformulario.addEventListener('close', () => {
    limpiarfototemporal();
    if (existente) {
        salirexistente();
    }
});


const dialogostock = document.getElementById('dialogostock');
const formulariostock = document.getElementById('formulariostock');
const avisostock = document.getElementById('avisostock');
const resumenstock = document.getElementById('resumenstock');
const resultadostock = document.getElementById('resultadostock');
const botonguardarstock = document.getElementById('botonguardarstock');
let productostock = null;


function limpiarerroresstock() {
    formulariostock.querySelectorAll('[data-error]').forEach((error) => {
        error.textContent = '';
    });
    formulariostock.querySelectorAll('[aria-invalid]').forEach((campo) => campo.removeAttribute('aria-invalid'));
    avisostock.hidden = true;
}


const sumando = () => formulariostock.elements.sentido.value === 'sumar';


function calcularresultadostock() {
    if (!productostock) {
        return;
    }
    const vendedor = vendedores(productostock).find((item) => item.id === formulariostock.elements.tbproveedorid.value);
    document.getElementById('preciocompra').textContent = sumando() && vendedor
        ? `${formatearmonto(vendedor.precio)} el paquete de ${productostock.tbinventariounidadespaquete} unidades` : '';


    const numero = parseInt(sumando() ? formulariostock.elements.paquetes.value : formulariostock.elements.cantidad.value, 10);
    if (Number.isNaN(numero) || numero < 1) {
        resultadostock.textContent = '';
        return;
    }
    const cambio = sumando() ? numero * productostock.tbinventariounidadespaquete : -numero;
    const nuevo = productostock.tbinventariostock + cambio;
    if (nuevo < 0) {
        resultadostock.textContent = `Solo hay ${productostock.tbinventariostock} en stock.`;
        resultadostock.className = 'resultado-stock texto-peligro';
        return;
    }
    const falta = productostock.tbinventariostockreservado > nuevo
        ? ` · Ya no alcanza para los encargos abiertos (tienen ${productostock.tbinventariostockreservado} apartadas)` : '';
    resultadostock.textContent = `${sumando() ? `Se suman ${cambio} unidades` : `Se rebajan ${-cambio}`}: pasa de `
        + `${productostock.tbinventariostock} a ${nuevo}${falta}`;
    resultadostock.className = `resultado-stock${falta ? ' texto-peligro' : ''}`;
}


async function abrirstock(id, sentido = 'sumar') {
    const respuesta = await consultar('obtener', { id });
    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }


    productostock = respuesta.datos;
    formulariostock.reset();
    limpiarerroresstock();
    const suma = sentido === 'sumar';
    formulariostock.elements.tbinventarioid.value = productostock.tbinventarioid;
    formulariostock.elements.sentido.value = sentido;
    document.getElementById('camposcompra').hidden = !suma;
    document.getElementById('camposdanadas').hidden = suma;
    document.getElementById('titulostock').textContent = suma
        ? `Agregar stock a ${productostock.tbinventarionombre}` : `Rebajar ${productostock.tbinventarionombre} dañadas`;
    resumenstock.textContent = `Hay ${productostock.tbinventariostock} en stock · ${productostock.tbinventariostockreservado} apartadas `
        + `para encargos · ${productostock.tbinventariodisponible} disponibles.`;
    document.getElementById('notastock').textContent = suma
        ? 'Si lo compró con un pedido al proveedor, no lo sume aquí: se suma solo al marcar el pedido como Recibido.'
        : 'Solo las que se dañaron o se marchitaron. Las que se usan en los arreglos se rebajan solas al terminar el encargo.';
    botonguardarstock.textContent = suma ? 'Sumar al stock' : 'Rebajar del stock';
    botonguardarstock.classList.toggle('boton-peligro', !suma);
    llenarvendedores(formulariostock.elements.tbproveedorid, productostock);
    formulariostock.elements.paquetes.value = suma ? '1' : '';
    calcularresultadostock();
    dialogostock.showModal();
    (suma ? formulariostock.elements.paquetes : formulariostock.elements.cantidad).focus();
}


formulariostock.addEventListener('change', calcularresultadostock);


formulariostock.addEventListener('input', (evento) => {
    const campo = evento.target;
    if (campo.name === 'paquetes' || campo.name === 'cantidad') {
        campo.value = campo.value.replace(/\D/g, '').slice(0, campo.name === 'paquetes' ? 4 : 6);
        formulariostock.querySelector(`[data-error="${campo.name}"]`).textContent = '';
        campo.removeAttribute('aria-invalid');
    }
    calcularresultadostock();
});


formulariostock.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-pasostock]');
    if (!boton) {
        return;
    }
    const campo = formulariostock.elements[boton.dataset.camposstock];
    const actual = parseInt(campo.value, 10);
    const maximo = campo.name === 'paquetes' ? 9999 : 999999;
    campo.value = String(Math.min(maximo, Math.max(1, (Number.isNaN(actual) ? 0 : actual) + Number(boton.dataset.pasostock))));
    campo.dispatchEvent(new Event('input', { bubbles: true }));
});


formulariostock.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limpiarerroresstock();
    botonguardarstock.disabled = true;


    const datos = new FormData(formulariostock);
    const respuesta = await enviar('movimientostock', datos);
    botonguardarstock.disabled = false;


    if (respuesta.exito) {
        dialogostock.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargarinventario();
        if (dialogodetalle.open && String(iddetalle) === String(productostock.tbinventarioid)) {
            verdetalle(iddetalle);
        }
        return;
    }


    Object.entries((respuesta.datos && respuesta.datos.errores) || {}).forEach(([campo, mensaje]) => {
        const error = formulariostock.querySelector(`[data-error="${campo}"]`);
        if (error) {
            error.textContent = mensaje;
        }
        const control = formulariostock.elements[campo];
        if (control && control.setAttribute) {
            control.setAttribute('aria-invalid', 'true');
        }
    });
    avisostock.textContent = respuesta.mensaje;
    avisostock.hidden = false;
});


document.getElementById('botoncancelarstock').addEventListener('click', () => dialogostock.close());
document.getElementById('botonstockdetalle').addEventListener('click', () => abrirstock(iddetalle, 'sumar'));
document.getElementById('botondanadasdetalle').addEventListener('click', () => abrirstock(iddetalle, 'restar'));
document.getElementById('botonstockformulario').addEventListener('click', () => {
    const id = formulario.elements.tbinventarioid.value;
    dialogoformulario.close();
    abrirstock(id, 'sumar');
});


const dialogocatalogos = document.getElementById('dialogocatalogos');
const avisocatalogos = document.getElementById('avisocatalogos');


function filacatalogo(catalogo, item) {
    const activo = item.estado === 1;
    const clase = item.clase ? `<small class="etiqueta-clase">${escapar(item.clase)}</small>` : '';
    return `
        <li class="${activo ? '' : 'catalogo-inactivo'}">
            <span class="nombre-catalogo">${escapar(item.nombre)} ${clase}</span>
            <small class="texto-suave">${item.usos === 1 ? '1 insumo' : `${item.usos} insumos`}</small>
            <span class="tarjeta-acciones">
                <button type="button" class="enlace ${activo ? 'enlace-peligro' : ''}" data-accion="${activo ? 'inactivar' : 'reactivar'}"
                        data-catalogo="${catalogo}" data-id="${item.id}" data-estado="${activo ? 0 : 1}">${activo ? 'Inactivar' : 'Reactivar'}</button>
            </span>
        </li>`;
}


function dibujarcatalogos() {
    document.getElementById('listacolores').innerHTML = catalogos.colores.map((color) => filacatalogo('color', color)).join('');
    document.getElementById('listatiposflor').innerHTML = catalogos.tiposflor.map((tipo) => filacatalogo('tipoflor', tipo)).join('');
}


function avisarcatalogos(mensaje, tipo) {
    avisocatalogos.textContent = mensaje;
    avisocatalogos.className = `aviso aviso-${tipo}`;
    avisocatalogos.hidden = false;
}


async function abrircatalogos() {
    await cargarcatalogos();
    avisocatalogos.hidden = true;
    dibujarcatalogos();
    dialogocatalogos.showModal();
}


dialogocatalogos.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    const formulariocatalogo = evento.target;
    const datos = new FormData(formulariocatalogo);
    datos.append('catalogo', formulariocatalogo.dataset.catalogo);


    const respuesta = await enviar('guardarcatalogo', datos);
    avisarcatalogos(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');
    if (respuesta.exito) {
        formulariocatalogo.elements.nombre.value = '';
        await cargarcatalogos();
        dibujarcatalogos();
    }
});


dialogocatalogos.addEventListener('click', async (evento) => {
    const boton = evento.target.closest('button[data-catalogo]');
    if (!boton) {
        return;
    }
    const datos = new FormData();
    datos.append('catalogo', boton.dataset.catalogo);
    datos.append('id', boton.dataset.id);
    datos.append('estado', boton.dataset.estado);


    const respuesta = await enviar('estadocatalogo', datos);
    avisarcatalogos(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');
    if (respuesta.exito) {
        await cargarcatalogos();
        dibujarcatalogos();
    }
});


dialogocatalogos.addEventListener('input', (evento) => {
    if (evento.target.name === 'nombre') {
        evento.target.value = filtros.letras(evento.target.value);
    }
});


document.getElementById('botoncatalogos').addEventListener('click', abrircatalogos);
document.getElementById('botoncerrarcatalogos').addEventListener('click', () => dialogocatalogos.close());


const proveedoreslistos = cargarproveedores();
const catalogoslistos = cargarcatalogos();
filtrosdesdeurl({ categoria: filtrocategoria, estado: filtroestado, stock: filtrostock });
cargarinventario();


abrirformulariosipide(abrirnuevo);


preciostemporada.aviso({
    modulo: 'inventario',
    singular: 'flor',
    plural: 'flores',
    alguardar: (mensaje) => {
        mostraraviso(mensaje, 'exito');
        cargarinventario();
    },
});
restaurarinsumodesdeproveedor();
