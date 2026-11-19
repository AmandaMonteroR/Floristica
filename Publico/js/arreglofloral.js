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
const sugeridoarreglo = document.getElementById('sugeridoarreglo');
const porcentajearreglo = document.getElementById('porcentajearreglo');
const notaprecio = document.getElementById('notaprecio');
const dialogoproductos = document.getElementById('dialogoproductos');
const buscarproductoinput = document.getElementById('buscarproducto');
const chipscategoria = document.getElementById('chipscategoria');
const listaproductos = document.getElementById('listaproductos');
const resumenproductos = document.getElementById('resumenproductos');
const categoriasproducto = ['Flor', 'Envoltura', 'Base', 'Chocolate', 'Peluche', 'Tarjeta', 'Accesorio'];
const campofoto = document.getElementById('campofoto');
const vistafoto = document.getElementById('vistafoto');
const textofoto = document.getElementById('textofoto');
const etiquetafoto = document.getElementById('etiquetafoto');
const dialogodetalle = document.getElementById('dialogodetalle');
const fotodetalle = document.getElementById('fotodetalle');
const titulodetalle = document.getElementById('titulodetalle');
const descripciondetalle = document.getElementById('descripciondetalle');
const preciosdetalle = document.getElementById('preciosdetalle');
const incluyedetalle = document.getElementById('incluyedetalle');
const resumencostos = document.getElementById('resumencostos');
const cuerpodetalle = document.getElementById('cuerpodetalle');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');
const camposformulario = ['tbarreglofloralnombre', 'tbarreglofloraldescripcion', 'tbarreglofloralprecio'];

// usuarioactual viene de menu.php. Los empleados también crean y editan arreglos (con el costo
// de los productos para ponerle precio), pero inactivarlos o reactivarlos es solo del propietario.
const espropietario = usuarioactual.rol === 'Propietario';
let categoriaelegida = '';
// Mientras sea true, el precio de venta sigue al precio sugerido (arreglo nuevo, o al cambiar el %).
// Si la persona escribe otro precio, se respeta.
let precioautomatico = true;

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

/* ---------- Catálogo en PDF para clientes ---------- */

const dialogoexportarcatalogo = document.getElementById('dialogoexportarcatalogo');
const hojacatalogo = document.getElementById('hojacatalogo');
const avisoexportarcatalogo = document.getElementById('avisoexportarcatalogo');

// Usa las mismas rutas que las fotografías de la galería de Catálogo.
function fotocatalogo(ruta) {
    if (!ruta) {
        return '';
    }
    try {
        const url = new URL(String(ruta), document.baseURI);
        return (url.protocol === 'http:' || url.protocol === 'https:') && url.origin === location.origin
            ? url.href : '';
    } catch (error) {
        return '';
    }
}

function fichaexportacion(nombre, precio, foto, descripcion = '') {
    const url = fotocatalogo(foto);
    return `
        <article class="catalogo-ficha">
            <div class="catalogo-imagen">
                <span class="catalogo-sinfoto" aria-label="Sin fotografía">✿<small>Sin fotografía</small></span>
                ${url ? `<img src="${escapar(url)}" alt="${escapar(nombre)}" loading="eager">` : ''}
            </div>
            <div class="catalogo-info">
                <strong>${escapar(nombre)}</strong>
                ${descripcion ? `<p>${escapar(descripcion)}</p>` : ''}
                <span class="catalogo-precio">${formatearmonto(precio)}</span>
            </div>
        </article>`;
}

function seccionexportacion(titulo, items) {
    if (!items.length) {
        return '';
    }
    return `<section class="catalogo-seccion">
        <h4>${escapar(titulo)}</h4>
        <div class="catalogo-productos">${items.join('')}</div>
    </section>`;
}

function documentocatalogo(arreglos, adicionales) {
    const nombres = (a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' });
    const tarjetasarreglos = [...arreglos]
        .sort((a, b) => nombres(a.tbarreglofloralnombre, b.tbarreglofloralnombre))
        .map((arreglo) => fichaexportacion(
            arreglo.tbarreglofloralnombre,
            arreglo.precioactual,
            arreglo.tbarreglofloralfoto,
            arreglo.tbarreglofloraldescripcion
        ));

    const grupos = [
        ['Chocolates', 'Chocolate'],
        ['Peluches', 'Peluche'],
        ['Tarjetas y mensajes', 'Tarjeta'],
        ['Coronas, perfumes y accesorios', 'Accesorio'],
    ];

    let contenido = seccionexportacion('Arreglos florales', tarjetasarreglos);
    grupos.forEach(([titulo, categoria]) => {
        const items = adicionales.filter((producto) => producto.tbinventariocategoria === categoria)
            .sort((a, b) => nombres(a.tbinventarionombre, b.tbinventarionombre))
            .map((producto) => fichaexportacion(
                producto.tbinventarionombre,
                producto.tbinventariopreciovigente,
                producto.tbinventariofoto
            ));
        contenido += seccionexportacion(titulo, items);
    });

    const cintas = adicionales.filter((producto) => producto.tbinventariocategoria === 'Envoltura'
        && /cinta|frase|personalizad|moño|lazo/i.test(producto.tbinventarionombre))
        .sort((a, b) => nombres(a.tbinventarionombre, b.tbinventarionombre))
        .map((producto) => fichaexportacion(
            producto.tbinventarionombre,
            producto.tbinventariopreciovigente,
            producto.tbinventariofoto
        ));
    contenido += seccionexportacion('Cintas y personalizaciones', cintas);

    const fecha = new Date().toLocaleDateString('es-CR', { day: 'numeric', month: 'long', year: 'numeric' });
    return `<div class="catalogo-portada">
            <div class="catalogo-marca"><img src="imagenes/logo.png" alt="">Floristica</div>
            <div><h3>Flores y regalos para cada ocasión</h3>
                <p class="catalogo-fecha">Catálogo actualizado el ${escapar(fecha)}</p></div>
        </div>
        ${contenido}
        <footer class="catalogo-pie">Precios en colones costarricenses, sujetos a disponibilidad.<br>
            ¡Gracias por preferirnos!</footer>`;
}

// La foto se oculta si ya no existe en el servidor y se ve el indicador Sin fotografía.
function revisarfotosexportacion() {
    hojacatalogo.querySelectorAll('.catalogo-imagen img').forEach((imagen) => {
        imagen.addEventListener('error', () => { imagen.remove(); }, { once: true });
        if (imagen.complete && imagen.naturalWidth === 0) {
            imagen.remove();
        }
    });
}

async function exportarcatalogo() {
    const [respuestaarreglos, respuestaproductos] = await Promise.all([
        consultar('listar', { texto: '', estado: '1' }),
        consultar('productos'),
    ]);

    if (!respuestaarreglos.exito || !respuestaproductos.exito) {
        mostraraviso('No se pudo cargar el catálogo para exportarlo. Intente de nuevo.', 'error');
        return;
    }

    const arreglos = respuestaarreglos.datos.filter((arreglo) => Number(arreglo.tbarreglofloralestado) === 1);
    const disponibles = respuestaproductos.datos.filter((producto) =>
        Number(producto.tbinventariodisponible) > 0 && Number(producto.tbinventariopreciovigente) > 0);
    const adicionales = disponibles.filter((producto) =>
        ['Chocolate', 'Peluche', 'Tarjeta', 'Accesorio', 'Envoltura'].includes(producto.tbinventariocategoria));

    if (arreglos.length === 0 && adicionales.length === 0) {
        mostraraviso('No hay arreglos ni regalos disponibles para exportar.', 'error');
        return;
    }

    avisoexportarcatalogo.hidden = true;
    hojacatalogo.innerHTML = documentocatalogo(arreglos, adicionales);
    revisarfotosexportacion();
    dialogoexportarcatalogo.showModal();
    dialogoexportarcatalogo.scrollTop = 0;
}

// Generación segura: reutiliza jsPDF (como la proforma), sin capturar el catálogo entero.
// Las fotografías se reducen individualmente; nunca se crea un lienzo de varias páginas.
function imagenparapdf(url) {
    return new Promise((resolver) => {
        if (!url) { resolver(null); return; }
        const imagen = new Image();
        let finalizado = false;
        const terminar = (resultado) => {
            if (finalizado) { return; }
            finalizado = true;
            clearTimeout(limite);
            imagen.onload = null;
            imagen.onerror = null;
            resolver(resultado);
        };
        const limite = setTimeout(() => terminar(null), 8000);
        imagen.onload = () => terminar(imagen);
        imagen.onerror = () => terminar(null);
        imagen.src = url;
    });
}

function fichasparapdf() {
    return [...hojacatalogo.querySelectorAll('.catalogo-seccion')].map((seccion) => ({
        titulo: seccion.querySelector('h4')?.textContent || 'Productos',
        productos: [...seccion.querySelectorAll('.catalogo-ficha')].map((ficha) => ({
            nombre: ficha.querySelector('.catalogo-info strong')?.textContent || 'Producto',
            descripcion: ficha.querySelector('.catalogo-info p')?.textContent || '',
            precio: ficha.querySelector('.catalogo-precio')?.textContent || '',
            foto: ficha.querySelector('.catalogo-imagen img')?.src || '',
        })),
    }));
}

async function generarcatalogopdf() {
    if (!window.jspdf?.jsPDF) {
        throw new Error('No se pudo cargar la biblioteca de PDF. Recargue la página.');
    }
    const secciones = fichasparapdf();
    if (!secciones.some((seccion) => seccion.productos.length)) {
        throw new Error('No hay productos para generar el PDF.');
    }

    const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'letter', compress: true });
    const ancho = pdf.internal.pageSize.getWidth();
    const alto = pdf.internal.pageSize.getHeight();
    const margen = 36;
    const separacion = 12;
    const anchoFicha = (ancho - margen * 2 - separacion) / 2;
    const altoFicha = 192;
    let filaY = 99;
    let columna = 0;
    let pagina = 1;

    function cabecerapagina() {
        pdf.setFillColor(176, 33, 84);
        pdf.rect(0, 0, ancho, 8, 'F');
        pdf.setTextColor(164, 32, 82);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(19);
        pdf.text('Floristica', margen, 40);
        pdf.setTextColor(60, 41, 52);
        pdf.setFontSize(12);
        pdf.text('Catálogo de flores y regalos', margen, 57);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(9);
        pdf.setTextColor(125, 95, 110);
        pdf.text(new Date().toLocaleDateString('es-CR'), ancho - margen, 42, { align: 'right' });
        pdf.setDrawColor(235, 195, 212);
        pdf.line(margen, 68, ancho - margen, 68);
    }

    function nuevapagina() {
        pdf.addPage();
        pagina += 1;
        filaY = 99;
        columna = 0;
        cabecerapagina();
    }

    function escribirtitulo(titulo) {
        if (columna !== 0) { columna = 0; filaY += altoFicha + separacion; }
        if (filaY + 31 + altoFicha > alto - 53) { nuevapagina(); }
        pdf.setTextColor(176, 33, 84);
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(12);
        pdf.text(titulo, margen, filaY);
        pdf.setDrawColor(237, 201, 216);
        pdf.line(margen, filaY + 6, ancho - margen, filaY + 6);
        filaY += 22;
    }

    async function dibujarficha(producto) {
        if (columna === 0 && filaY + altoFicha > alto - 53) { nuevapagina(); }
        const x = margen + columna * (anchoFicha + separacion);
        const y = filaY;
        const imagenX = x + 8;
        const imagenY = y + 8;
        const imagenAncho = anchoFicha - 16;
        const imagenAlto = 115;

        pdf.setFillColor(255, 255, 255);
        pdf.setDrawColor(236, 210, 221);
        pdf.roundedRect(x, y, anchoFicha, altoFicha, 6, 6, 'FD');
        pdf.setFillColor(255, 240, 245);
        pdf.roundedRect(imagenX, imagenY, imagenAncho, imagenAlto, 4, 4, 'F');

        const imagen = await imagenparapdf(producto.foto);
        if (imagen) {
            try {
                // Máximo 360 × 190 píxeles por fotografía; se libera tras agregarla al PDF.
                const lienzo = document.createElement('canvas');
                lienzo.width = 360;
                lienzo.height = 190;
                const contexto = lienzo.getContext('2d');
                const escala = Math.max(lienzo.width / imagen.naturalWidth, lienzo.height / imagen.naturalHeight);
                const anchoReal = imagen.naturalWidth * escala;
                const altoReal = imagen.naturalHeight * escala;
                contexto.fillStyle = '#fff0f5';
                contexto.fillRect(0, 0, lienzo.width, lienzo.height);
                contexto.drawImage(imagen, (lienzo.width - anchoReal) / 2, (lienzo.height - altoReal) / 2,
                    anchoReal, altoReal);
                pdf.addImage(lienzo.toDataURL('image/jpeg', 0.7), 'JPEG', imagenX, imagenY, imagenAncho, imagenAlto);
                lienzo.width = 0;
                lienzo.height = 0;
            } catch (error) {
                // Una foto dañada no impide exportar los demás productos.
            }
        } else {
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(9);
            pdf.setTextColor(160, 80, 112);
            pdf.text('Sin fotografía', x + anchoFicha / 2, imagenY + 61, { align: 'center' });
        }

        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(10);
        pdf.setTextColor(55, 39, 49);
        const lineasNombre = pdf.splitTextToSize(producto.nombre, anchoFicha - 20).slice(0, 2);
        pdf.text(lineasNombre, x + 10, y + 140);
        if (producto.descripcion) {
            pdf.setFont('helvetica', 'normal');
            pdf.setFontSize(8);
            pdf.setTextColor(128, 100, 113);
            const descripcion = pdf.splitTextToSize(producto.descripcion, anchoFicha - 20).slice(0, 1);
            pdf.text(descripcion, x + 10, y + 162);
        }
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(12);
        pdf.setTextColor(176, 33, 84);
        pdf.text(producto.precio.replace('₡', 'CRC '), x + 10, y + 182);

        if (columna === 0) { columna = 1; }
        else { columna = 0; filaY += altoFicha + separacion; }
    }

    cabecerapagina();
    for (const seccion of secciones) {
        if (!seccion.productos.length) { continue; }
        escribirtitulo(seccion.titulo);
        for (const producto of seccion.productos) {
            await dibujarficha(producto);
            // Permite que el navegador siga respondiendo durante catálogos grandes.
            await new Promise((resolver) => setTimeout(resolver, 0));
        }
    }

    const totalPaginas = pdf.internal.getNumberOfPages();
    for (let indice = 1; indice <= totalPaginas; indice += 1) {
        pdf.setPage(indice);
        pdf.setDrawColor(237, 201, 216);
        pdf.line(margen, alto - 40, ancho - margen, alto - 40);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(8);
        pdf.setTextColor(130, 101, 118);
        pdf.text('Precios en colones, sujetos a disponibilidad.', margen, alto - 27);
        pdf.text(`${indice} / ${totalPaginas}`, ancho - margen, alto - 27, { align: 'right' });
    }
    pdf.setProperties({ title: 'Catálogo Floristica', creator: 'Floristica' });
    return pdf;
}

async function descargarcatalogopdf(compartir = false) {
    const boton = document.getElementById(compartir ? 'botoncompartircatalogo' : 'botonpdfcatalogo');
    const aviso = avisoexportarcatalogo;
    aviso.hidden = true;
    document.getElementById('botonpdfcatalogo').disabled = true;
    document.getElementById('botoncompartircatalogo').disabled = true;
    const textoAnterior = boton.textContent;
    boton.textContent = 'Generando PDF…';

    try {
        const pdf = await generarcatalogopdf();
        const nombre = `Catalogo_Floristica_${new Date().toISOString().slice(0, 10)}.pdf`;
        if (compartir && typeof navigator.share === 'function' && typeof navigator.canShare === 'function') {
            const archivo = new File([pdf.output('blob')], nombre, { type: 'application/pdf' });
            if (navigator.canShare({ files: [archivo] })) {
                try { await navigator.share({ files: [archivo], title: 'Catálogo Floristica' }); }
                catch (error) { if (error.name !== 'AbortError') { throw error; } }
                return;
            }
        }
        pdf.save(nombre);
        if (compartir) {
            aviso.textContent = 'Se descargó el PDF. Puede adjuntarlo en WhatsApp o compartirlo desde Descargas.';
            aviso.className = 'aviso aviso-info';
            aviso.hidden = false;
        }
    } catch (error) {
        aviso.textContent = error.message || 'No se pudo generar el PDF. Use Imprimir como alternativa.';
        aviso.className = 'aviso aviso-error';
        aviso.hidden = false;
    } finally {
        boton.textContent = textoAnterior;
        document.getElementById('botonpdfcatalogo').disabled = false;
        document.getElementById('botoncompartircatalogo').disabled = false;
    }
}

function imprimircatalogo() {
    const titulo = document.title;
    document.title = 'Catálogo Floristica';
    window.addEventListener('afterprint', () => { document.title = titulo; }, { once: true });
    window.print();
}

/* ---------- Productos del inventario ---------- */

async function cargarproductos() {
    const respuesta = await consultar('productos');
    productos = respuesta.exito ? respuesta.datos : [];
    dibujarregalos();
}

// Productos que se venden como regalos: se consultan del mismo Inventario.
function dibujarregalos() {
    const contenedor = document.getElementById('galeriaregalos');
    const regalos = productos.filter((producto) =>
        ['Chocolate', 'Peluche', 'Tarjeta', 'Accesorio'].includes(producto.tbinventariocategoria));
    contenedor.innerHTML = regalos.length ? regalos.map((producto) => `
        <article class="tarjeta-seleccion-encargo">
            ${producto.tbinventariofoto
                ? `<img src="${escapar(producto.tbinventariofoto)}" alt="${escapar(producto.tbinventarionombre)}" loading="lazy">`
                : '<span class="sin-foto-encargo" aria-label="Sin fotografía">✿</span>'}
            <strong>${escapar(producto.tbinventarionombre)}</strong>
            <span>${formatearmonto(producto.tbinventariopreciovigente)}</span>
            <small>${escapar(producto.tbinventariocategoria)} · ${producto.tbinventariodisponible} disponibles</small>
        </article>`).join('') : '<p class="nota">Los chocolates, peluches y regalos se agregan desde Inventario.</p>';
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
        tbarreglofloralinsumocantidad: '',
        tbinventarionombre: '',
        tbinventariopreciounidad: 0,
    };
}

function costounidad(linea) {
    const producto = buscarproducto(linea.tbinventarioid);
    return producto ? producto.tbinventariopreciounidad : Number(linea.tbinventariopreciounidad) || 0;
}

function subtotal(linea) {
    return (parseInt(linea.tbarreglofloralinsumocantidad, 10) || 0) * costounidad(linea);
}

// % de ganancia elegido (0 a 500)
function porcentajeganancia() {
    const valor = parseInt(porcentajearreglo.value, 10);
    return Number.isNaN(valor) ? 0 : Math.min(500, Math.max(0, valor));
}

// Precio sugerido: el costo de los productos más el %, redondeado hacia arriba a ₡100
function preciosugerido(costo) {
    return costo > 0 ? Math.ceil((costo * (1 + porcentajeganancia() / 100)) / 100) * 100 : 0;
}

function costoproductos() {
    return lineas.reduce((suma, linea) => suma + subtotal(linea), 0);
}

function actualizartotales() {
    const costo = costoproductos();
    const sugerido = preciosugerido(costo);
    const campoprecio = formulario.elements.tbarreglofloralprecio;

    if (precioautomatico && sugerido > 0 && campoprecio.value !== String(sugerido)) {
        campoprecio.value = String(sugerido);
        formulario.querySelector('[data-error="tbarreglofloralprecio"]').textContent = '';
    }

    const precio = parseFloat(campoprecio.value) || 0;
    const ganancia = precio - costo;
    const porcentajereal = costo > 0 ? Math.round((ganancia / costo) * 100) : 0;

    costoarreglo.textContent = formatearmonto(costo);
    sugeridoarreglo.textContent = formatearmonto(sugerido);
    gananciaarreglo.textContent = costo > 0 && precio > 0 ? `${formatearmonto(ganancia)} (${porcentajereal}%)` : formatearmonto(ganancia);
    gananciaarreglo.classList.toggle('texto-peligro', ganancia < 0);

    notaprecio.textContent = sugerido === 0 ? 'Agregue los productos y se calcula el precio sugerido.'
        : precioautomatico ? 'Es el precio sugerido; puede escribir otro.'
            : precio !== sugerido ? 'Usted puso otro precio. Toque un % para volver al sugerido.' : '';

    formulario.querySelectorAll('[data-porcentaje]').forEach((chip) => {
        chip.classList.toggle('chip-activo', Number(chip.dataset.porcentaje) === porcentajeganancia() && porcentajearreglo.value !== '');
    });
}

function cambiarporcentaje(valor) {
    porcentajearreglo.value = String(Math.min(500, Math.max(0, valor)));
    precioautomatico = true;
    actualizartotales();
    guardarborrador();
}

// Unidades que hay en inventario del producto de la línea (null si ya no está activo)
function stocklinea(linea) {
    const producto = buscarproducto(linea.tbinventarioid);
    return producto ? Number(producto.tbinventariostock) : null;
}

function celdastock(linea) {
    const stock = stocklinea(linea);
    const cantidad = parseInt(linea.tbarreglofloralinsumocantidad, 10) || 0;
    if (stock === null) {
        return '<span class="texto-suave">Inactivo</span>';
    }
    return cantidad > stock
        ? `<span class="texto-peligro"><strong>Solo hay ${stock}</strong></span>`
        : `${stock}`;
}

function dibujarlineas() {
    const columnas = 6;
    if (lineas.length === 0) {
        cuerpolineas.innerHTML = `<tr><td colspan="${columnas}" class="vacio">Use "Agregar productos" para elegir las flores, el papel, las cintas, la base y lo demás del arreglo.</td></tr>`;
        actualizartotales();
        return;
    }

    cuerpolineas.innerHTML = lineas.map((linea, indice) => {
        const producto = buscarproducto(linea.tbinventarioid);
        const nombre = producto ? producto.tbinventarionombre : `${linea.tbinventarionombre || 'Producto'} (inactivo)`;
        return `
        <tr data-indice="${indice}">
            <td>${escapar(nombre)}${producto ? `<br><small class="texto-suave">${escapar(producto.tbinventariocategoria)}</small>` : ''}</td>
            <td>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar una unidad de ${escapar(nombre)}">−</button>
                    <input type="text" data-campo="tbarreglofloralinsumocantidad" inputmode="numeric" maxlength="3" autocomplete="off" value="${escapar(linea.tbarreglofloralinsumocantidad)}" aria-label="Unidades de ${escapar(nombre)}">
                    <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar una unidad de ${escapar(nombre)}">+</button>
                </div>
            </td>
            <td class="stock-linea">${celdastock(linea)}</td>
            <td>${formatearmonto(costounidad(linea))}</td>
            <td class="subtotal-linea">${formatearmonto(subtotal(linea))}</td>
            <td><button type="button" class="enlace enlace-peligro" data-quitar="${indice}" aria-label="Quitar ${escapar(nombre)}">Quitar</button></td>
        </tr>`;
    }).join('');

    actualizartotales();
}

function actualizarfila(fila, linea) {
    fila.querySelector('[data-campo="tbarreglofloralinsumocantidad"]').value = linea.tbarreglofloralinsumocantidad;
    fila.querySelector('.stock-linea').innerHTML = celdastock(linea);
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
        tbarreglofloralinsumocantidad: linea.tbarreglofloralinsumocantidad,
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
            tbarreglofloralinsumocantidad: String(detalle.tbarreglofloralinsumocantidad),
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

// Monto sin decimales cuando no hacen falta: ₡12 000
function montocorto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { maximumFractionDigits: 2 });
}

// Precio normal y de temporada alta; el que se cobra ahora va resaltado
function dosprecios(arreglo) {
    const alta = arreglo.altaencendida;
    return `
        <div class="tarjeta-precios">
            <span class="${alta ? '' : 'precio-activo'}"><small>Normal</small> ${montocorto(arreglo.tbarreglofloralprecio)}</span>
            <span class="${alta ? 'precio-activo' : ''}" title="Temporada alta"><small>Temp. alta</small> ${montocorto(arreglo.precioalto)}</span>
        </div>`;
}

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
        const ganancia = arreglo.precioactual - arreglo.costoactual;
        const precio = dosprecios(arreglo);
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${id}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${id}">Reactivar</button>`;
        // Editar lo pueden hacer todos; inactivar o reactivar, solo el propietario
        const botonespropietario = `<button type="button" class="enlace" data-accion="editar" data-id="${id}">Editar</button>
                        ${espropietario ? botonestado : ''}`;

        return `
            <article class="tarjeta-arreglo ${activo ? '' : 'tarjeta-inactiva'}" data-nombre="${escapar(arreglo.tbarreglofloralnombre)}">
                <img src="${escapar(arreglo.tbarreglofloralfoto)}" alt="${escapar(arreglo.tbarreglofloralnombre)}" loading="lazy">
                <div class="tarjeta-cuerpo">
                    <div class="tarjeta-titulo">
                        <h2>${escapar(arreglo.tbarreglofloralnombre)}</h2>
                        <span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span>
                    </div>
                    ${precio}
                    ${espropietario ? `<p class="tarjeta-costo">Costo ${formatearmonto(arreglo.costoactual)} · Ganancia <span class="${ganancia < 0 ? 'texto-peligro' : ''}">${formatearmonto(ganancia)}</span></p>` : ''}
                    <div class="tarjeta-acciones">
                        <button type="button" class="enlace" data-accion="ver" data-id="${id}">Ver</button>
                        ${botonespropietario}
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
    await cargarproductos();
    datosservidor = {};
    precioautomatico = false;
    porcentajearreglo.value = '100';
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    // Arreglo nuevo: el precio sigue al sugerido, salvo que el borrador traiga otro precio
    const precio = formulario.elements.tbarreglofloralprecio.value;
    precioautomatico = precio === '' || Number(precio) === preciosugerido(costoproductos());
    actualizartotales();
    tituloformulario.textContent = 'Nuevo arreglo';
    botonguardar.textContent = 'Guardar arreglo';
    dialogoformulario.showModal();
}

async function abrireditar(id) {
    await cargarproductos();
    const respuesta = await consultar('obtener', { id });

    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    datosservidor = respuesta.datos;
    // Al editar se respeta el precio que tiene; el % que se muestra es el que da ese precio
    precioautomatico = false;
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    const costo = costoproductos();
    const precio = Number(formulario.elements.tbarreglofloralprecio.value) || 0;
    porcentajearreglo.value = String(costo > 0 && precio > 0 ? Math.min(500, Math.max(0, Math.round((precio / costo - 1) * 100))) : 100);
    actualizartotales();
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
    const alta = arreglo.altaencendida;

    fotodetalle.src = arreglo.tbarreglofloralfoto;
    fotodetalle.alt = arreglo.tbarreglofloralnombre;
    titulodetalle.innerHTML = escapar(arreglo.tbarreglofloralnombre)
        + (arreglo.tbarreglofloralestado === 1 ? '' : ' <span class="estado estado-inactivo">Inactivo</span>');
    descripciondetalle.textContent = arreglo.tbarreglofloraldescripcion || '';
    descripciondetalle.hidden = !arreglo.tbarreglofloraldescripcion;

    preciosdetalle.innerHTML = `
        <div class="${alta ? '' : 'precio-activo'}">
            <span>Precio normal</span>
            <strong>${montocorto(arreglo.tbarreglofloralprecio)}</strong>
            ${alta ? '' : '<small>Se cobra ahora</small>'}
        </div>
        <div class="${alta ? 'precio-activo' : ''}">
            <span>Temporada alta</span>
            <strong>${montocorto(arreglo.precioalto)}</strong>
            ${alta ? '<small>Se cobra ahora</small>' : '<small>Sube solo con sus flores</small>'}
        </div>`;

    incluyedetalle.innerHTML = arreglo.detalles.map((detalle) => `
        <li><strong>${detalle.tbarreglofloralinsumocantidad}</strong> ${escapar(detalle.tbinventarionombre)}</li>`).join('');

    // El costo y la ganancia solo los ve el propietario, y quedan cerrados hasta que los abra
    if (espropietario && resumencostos) {
        const fila = (titulo, precio, costo) => `
            <div>
                <span>${titulo}</span>
                <span>Costo ${formatearmonto(costo)}</span>
                <strong class="${precio - costo < 0 ? 'texto-peligro' : ''}">Ganancia ${formatearmonto(precio - costo)}</strong>
            </div>`;
        resumencostos.innerHTML = fila('Normal', arreglo.tbarreglofloralprecio, arreglo.costo)
            + fila('Temporada alta', arreglo.precioalto, arreglo.costoalto);

        cuerpodetalle.innerHTML = arreglo.detalles.map((detalle) => `
            <tr>
                <td>${escapar(detalle.tbinventarionombre)}</td>
                <td>${detalle.tbarreglofloralinsumocantidad}</td>
                <td>${formatearmonto(detalle.tbarreglofloralinsumocantidad * detalle.tbinventariopreciounidad)}</td>
                <td>${formatearmonto(detalle.tbarreglofloralinsumocantidad * (detalle.costoalto ?? detalle.tbinventariopreciounidad))}</td>
            </tr>`).join('');
        dialogodetalle.querySelector('.costos-detalle').open = false;
    }

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

/* ---------- Ventana para elegir productos ---------- */

function lineade(id) {
    return lineas.find((linea) => String(linea.tbinventarioid) === String(id));
}

function dibujarproductos() {
    const texto = buscarproductoinput.value.trim().toLowerCase();
    const lista = productos.filter((producto) => (categoriaelegida === '' || producto.tbinventariocategoria === categoriaelegida)
        && producto.tbinventarionombre.toLowerCase().includes(texto));

    chipscategoria.innerHTML = ['', ...categoriasproducto]
        .filter((categoria) => categoria === '' || productos.some((producto) => producto.tbinventariocategoria === categoria))
        .map((categoria) => `<button type="button" class="chip ${categoria === categoriaelegida ? 'chip-activo' : ''}" data-categoria="${categoria}">${categoria || 'Todas'}</button>`)
        .join('');

    listaproductos.innerHTML = lista.length ? lista.map((producto) => {
        const linea = lineade(producto.tbinventarioid);
        const stock = Number(producto.tbinventariostock);
        const cantidad = linea ? parseInt(linea.tbarreglofloralinsumocantidad, 10) || 0 : 0;
        const precio = `Costo ${formatearmonto(producto.tbinventariopreciounidad)}`;
        const control = linea
            ? `<div class="contador">
                    <button type="button" class="boton-contador" data-producto="${producto.tbinventarioid}" data-paso="-1" aria-label="Una unidad menos">−</button>
                    <span class="cantidad-producto">${cantidad}</span>
                    <button type="button" class="boton-contador" data-producto="${producto.tbinventarioid}" data-paso="1" aria-label="Una unidad más" ${cantidad >= stock ? 'disabled' : ''}>+</button>
               </div>`
            : `<button type="button" class="boton" data-producto="${producto.tbinventarioid}" data-paso="1" ${stock < 1 ? 'disabled' : ''}>${stock < 1 ? 'Sin stock' : 'Agregar'}</button>`;
        return `
            <li class="${linea ? 'producto-elegido' : ''}">
                <div class="info-producto">
                    <strong>${escapar(producto.tbinventarionombre)}</strong>
                    <small class="texto-suave">${escapar(producto.tbinventariocategoria)} · ${precio}</small>
                </div>
                <span class="stock-producto ${stock < 1 ? 'texto-peligro' : ''}">${stock} en inventario</span>
                ${control}
            </li>`;
    }).join('') : '<li class="vacio">No hay productos que coincidan.</li>';

    const elegidos = lineas.filter((linea) => linea.tbinventarioid !== '').length;
    resumenproductos.textContent = elegidos === 1 ? '1 producto en el arreglo' : `${elegidos} productos en el arreglo`;
}

function cambiarproducto(id, paso) {
    const producto = buscarproducto(id);
    if (!producto) {
        return;
    }
    let linea = lineade(id);
    if (!linea) {
        linea = { ...lineavacia(), tbinventarioid: String(id), tbinventarionombre: producto.tbinventarionombre,
            tbinventariopreciounidad: producto.tbinventariopreciounidad ?? 0, tbarreglofloralinsumocantidad: '0' };
        lineas.push(linea);
    }
    const cantidad = (parseInt(linea.tbarreglofloralinsumocantidad, 10) || 0) + paso;
    if (cantidad < 1) {
        lineas.splice(lineas.indexOf(linea), 1);
    } else {
        linea.tbarreglofloralinsumocantidad = String(Math.min(cantidad, Math.max(Number(producto.tbinventariostock), 1), 999));
    }
    formulario.querySelector('[data-error="detalles"]').textContent = '';
    dibujarlineas();
    dibujarproductos();
    guardarborrador();
}

function agregarlinea() {
    categoriaelegida = '';
    buscarproductoinput.value = '';
    dibujarproductos();
    dialogoproductos.showModal();
    buscarproductoinput.focus();
}

buscarproductoinput.addEventListener('input', dibujarproductos);
chipscategoria.addEventListener('click', (evento) => {
    const chip = evento.target.closest('[data-categoria]');
    if (chip) {
        categoriaelegida = chip.dataset.categoria;
        dibujarproductos();
    }
});
listaproductos.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-producto]');
    if (boton) {
        cambiarproducto(boton.dataset.producto, Number(boton.dataset.paso));
    }
});
document.getElementById('botonlistoproductos').addEventListener('click', () => dialogoproductos.close());

// % de ganancia: botones − y +, y los porcentajes rápidos
formulario.addEventListener('click', (evento) => {
    const paso = evento.target.closest('[data-pasoporcentaje]');
    const chip = evento.target.closest('[data-porcentaje]');
    if (paso) {
        cambiarporcentaje(porcentajeganancia() + Number(paso.dataset.pasoporcentaje));
    } else if (chip) {
        cambiarporcentaje(Number(chip.dataset.porcentaje));
    }
});

campofoto.addEventListener('change', revisarfoto);

formulario.addEventListener('input', (evento) => {
    const campo = evento.target;

    if (campo.name === 'tbarreglofloralprecio') {
        campo.value = solodecimal(campo.value);
        precioautomatico = false;
        actualizartotales();
    }

    if (campo === porcentajearreglo) {
        campo.value = campo.value.replace(/\D/g, '').slice(0, 3);
        precioautomatico = true;
        actualizartotales();
        return;
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
    if (!fila || campo.dataset.campo !== 'tbarreglofloralinsumocantidad') {
        return;
    }

    const linea = lineas[Number(fila.dataset.indice)];
    linea.tbarreglofloralinsumocantidad = soloentero(campo.value);
    actualizarfila(fila, linea);
});

cuerpolineas.addEventListener('click', (evento) => {
    const paso = evento.target.closest('button[data-paso]');
    const quitar = evento.target.closest('button[data-quitar]');

    if (paso) {
        const fila = paso.closest('tr[data-indice]');
        const linea = lineas[Number(fila.dataset.indice)];
        const actual = parseInt(linea.tbarreglofloralinsumocantidad, 10) || 0;
        linea.tbarreglofloralinsumocantidad = String(Math.min(999, Math.max(1, actual + Number(paso.dataset.paso))));
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
        datos.append(`detalles[${indice}][tbarreglofloralinsumocantidad]`, linea.tbarreglofloralinsumocantidad);
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

// "Ver historial" del arreglo que está abierto en el detalle (solo existe para el propietario)
let iddetalle = null;
const botonverhistorial = document.getElementById('botonverhistorial');
if (botonverhistorial) {
    botonverhistorial.addEventListener('click', () => ventanahistorial.mostrar('catalogo', iddetalle,
        titulodetalle.textContent.replace(' Inactivo', '')));
}

document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

document.getElementById('botonexportarcatalogo').addEventListener('click', exportarcatalogo);
document.getElementById('botonpdfcatalogo').addEventListener('click', () => descargarcatalogopdf());
document.getElementById('botoncompartircatalogo').addEventListener('click', () => descargarcatalogopdf(true));
document.getElementById('botonimprimircatalogo').addEventListener('click', imprimircatalogo);
document.getElementById('botoncerrarcatalogo').addEventListener('click', () => dialogoexportarcatalogo.close());

cargararreglos();
cargarproductos();
