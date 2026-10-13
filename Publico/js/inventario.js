const rutabase = 'index.php?modulo=inventario';
const clavebase = 'floristica.borrador.inventario.';
const extrasconvencimiento = ['Chocolate', 'Maquillaje'];
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
        tbinventariocategoriaflortipo: 'Tipo de flor',
        tbinventariocategoriaflorcolor: 'Color',
        tbinventariocategoriaflorlargotallo: 'Largo del tallo (cm)',
        tbinventariocategoriaflordiasduracion: 'Días de duración',
    },
    Base: {
        tbinventariocategoriabasetipo: 'Tipo',
        tbinventariocategoriabasematerial: 'Material',
        tbinventariocategoriabasecolor: 'Color',
        tbinventariocategoriabaseforma: 'Forma',
        tbinventariocategoriabasemedida: 'Medida (cm)',
    },
    Adorno: {
        tbinventariocategoriaadornotipo: 'Tipo',
        tbinventariocategoriaadornomaterial: 'Material',
        tbinventariocategoriaadornocolor: 'Color',
        tbinventariocategoriaadornomedida: 'Medida (cm)',
        tbinventariocategoriaadornodiseno: 'Diseño u ocasión',
    },
    Extra: {
        tbinventariocategoriaextratipo: 'Tipo',
        tbinventariocategoriaextramarca: 'Marca',
        tbinventariocategoriaextratamano: 'Tamaño',
        tbinventariocategoriaextracolor: 'Color',
        tbinventariocategoriaextracontenido: 'Contenido',
        tbinventariocategoriaextrafechavencimiento: 'Fecha de vencimiento',
    },
};

const cuerpotabla = document.getElementById('cuerpotabla');
const busqueda = document.getElementById('busqueda');
const filtrocategoria = document.getElementById('filtrocategoria');
const filtroestado = document.getElementById('filtroestado');
const aviso = document.getElementById('aviso');
const dialogoformulario = document.getElementById('dialogoformulario');
const formulario = document.getElementById('formulario');
const tituloformulario = document.getElementById('tituloformulario');
const avisoformulario = document.getElementById('avisoformulario');
const avisoborrador = document.getElementById('avisoborrador');
const botonguardar = document.getElementById('botonguardar');
const preciounidad = document.getElementById('preciounidad');
const campovencimiento = document.getElementById('campovencimiento');
const etiquetadiseno = document.getElementById('etiquetadiseno');
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

let temporizadorbusqueda;
let temporizadoraviso;
let datosservidor = {};
let datosoriginales = {};

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
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

    selectproveedor.innerHTML = '';
    selectproveedor.add(new Option('Seleccione…', ''));
    respuesta.datos.forEach((proveedor) => {
        selectproveedor.add(new Option(proveedor.tbproveedornombreempresa, proveedor.tbproveedorid));
    });
}

function mostrarseccioncategoria() {
    formulario.querySelectorAll('.seccion-categoria').forEach((seccion) => {
        seccion.hidden = seccion.dataset.categoria !== selectcategoria.value;
    });
    actualizarvencimiento();
    actualizardiseno();
}

function actualizarvencimiento() {
    const tipo = formulario.elements.tbinventariocategoriaextratipo.value;
    campovencimiento.hidden = !extrasconvencimiento.includes(tipo);
}

function actualizardiseno() {
    const estarjeta = formulario.elements.tbinventariocategoriaadornotipo.value === 'Tarjeta';
    etiquetadiseno.textContent = estarjeta ? 'Diseño u ocasión *' : 'Diseño u ocasión';
}

function calcularpreciounidad() {
    const precio = parseFloat(formulario.elements.tbinventariopreciopaquete.value.replace(',', '.'));
    const unidades = parseInt(formulario.elements.tbinventariounidadespaquete.value, 10);

    preciounidad.textContent = precio > 0 && unidades > 0 ? formatearmonto(precio / unidades) : '—';
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
    calcularpreciounidad();
}

function llenarformulario(datos) {
    formulario.reset();
    formulario.elements.tbinventarioid.value = datos.tbinventarioid ?? '';

    if (datos.tbproveedorid && !selectproveedor.querySelector(`option[value="${datos.tbproveedorid}"]`)) {
        selectproveedor.add(new Option(`${datos.tbproveedornombreempresa} (inactivo)`, datos.tbproveedorid));
    }

    asignarcampos({
        tbinventarionombre: datos.tbinventarionombre,
        tbproveedorid: datos.tbproveedorid,
        tbinventariocategoria: datos.tbinventariocategoria,
        tbinventariopreciopaquete: datos.tbinventariopreciopaquete,
        tbinventariounidadespaquete: datos.tbinventariounidadespaquete,
        tbinventariostock: datos.tbinventariostock,
        tbinventariostockminimo: datos.tbinventariostockminimo,
        ...(datos.categoria ?? {}),
    });

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
    limpiarerrores();
    avisoborrador.hidden = true;
}

function nivelstock(producto) {
    const stock = producto.tbinventariostock;
    const minimo = producto.tbinventariostockminimo;

    if (stock <= minimo) {
        return { clase: 'stock-bajo', texto: 'Bajo el mínimo' };
    }
    if (stock <= Math.ceil(minimo * 1.5)) {
        return { clase: 'stock-medio', texto: 'Cerca del mínimo' };
    }
    return { clase: 'stock-bien', texto: 'Suficiente' };
}

async function cargarinventario() {
    const respuesta = await consultar('listar', {
        texto: busqueda.value,
        categoria: filtrocategoria.value,
        estado: filtroestado.value,
    });

    if (!respuesta.exito) {
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    if (respuesta.datos.length === 0) {
        const sinfiltros = busqueda.value.trim() === '' && filtrocategoria.value === '' && filtroestado.value === '';
        const mensaje = sinfiltros
            ? 'Aún no hay productos registrados. Use "Nuevo producto" para agregar el primero.'
            : 'No hay productos que coincidan con la búsqueda.';
        cuerpotabla.innerHTML = `<tr><td colspan="7" class="vacio">${mensaje}</td></tr>`;
        return;
    }

    cuerpotabla.innerHTML = respuesta.datos.map((producto) => {
        const activo = producto.tbinventarioestado === 1;
        const botonestado = activo
            ? `<button type="button" class="enlace enlace-peligro" data-accion="inactivar" data-id="${producto.tbinventarioid}">Inactivar</button>`
            : `<button type="button" class="enlace" data-accion="reactivar" data-id="${producto.tbinventarioid}">Reactivar</button>`;
        const nivel = nivelstock(producto);

        return `
            <tr class="${activo ? '' : 'fila-inactiva'}">
                <td>${escapar(producto.tbinventarionombre)}</td>
                <td>${escapar(producto.tbinventariocategoria)}</td>
                <td>${escapar(producto.tbproveedornombreempresa)}</td>
                <td>${formatearmonto(producto.tbinventariopreciounidad)}</td>
                <td><span class="stock ${nivel.clase}" title="${nivel.texto} (mínimo: ${producto.tbinventariostockminimo})">${producto.tbinventariostock}</span></td>
                <td><span class="estado ${activo ? 'estado-activo' : 'estado-inactivo'}">${activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="columna-acciones">
                    <button type="button" class="enlace" data-accion="ver" data-id="${producto.tbinventarioid}">Ver</button>
                    <button type="button" class="enlace" data-accion="editar" data-id="${producto.tbinventarioid}">Editar</button>
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
    await proveedoreslistos;
    datosservidor = {};
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Nuevo producto';
    botonguardar.textContent = 'Guardar producto';
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
    llenarformulario(datosservidor);
    limpiarerrores();
    aplicarborrador();
    tituloformulario.textContent = 'Editar producto';
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
    const filas = [
        ['Categoría', producto.tbinventariocategoria],
        ['Proveedor', producto.tbproveedornombreempresa],
        ['Precio del paquete', formatearmonto(producto.tbinventariopreciopaquete)],
        ['Unidades por paquete', String(producto.tbinventariounidadespaquete)],
        ['Precio por unidad', formatearmonto(producto.tbinventariopreciounidad)],
        ['Stock actual', `${producto.tbinventariostock} (${nivelstock(producto).texto.toLowerCase()})`],
        ['Stock mínimo', String(producto.tbinventariostockminimo)],
    ];

    const etiquetas = etiquetascategoria[producto.tbinventariocategoria] ?? {};
    Object.entries(etiquetas).forEach(([campo, etiqueta]) => {
        let valor = producto.categoria[campo];
        if (campo === 'tbinventariocategoriaextrafechavencimiento') {
            valor = formatearfecha(valor);
        }
        filas.push([etiqueta, valor === null || valor === undefined ? '' : String(valor)]);
    });

    filas.push(['Estado', producto.tbinventarioestado === 1 ? 'Activo' : 'Inactivo']);

    titulodetalle.textContent = producto.tbinventarionombre;
    listadetalle.innerHTML = filas
        .map(([etiqueta, valor]) => `<dt>${etiqueta}</dt><dd>${valor ? escapar(valor) : 'No registrado'}</dd>`)
        .join('');

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
        return;
    }

    const datos = new FormData();
    datos.append('id', id);

    const respuesta = await enviar(accion, datos);
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        cargarinventario();
    }
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

    const error = formulario.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
    guardarborrador();
});

formulario.addEventListener('change', guardarborrador);

// Botones − y + de unidades por paquete, stock y stock mínimo
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
    // Avisa del cambio para que se limpie el error, se guarde el borrador y se recalcule el precio por unidad
    campo.dispatchEvent(new Event('input', { bubbles: true }));
});

selectcategoria.addEventListener('change', mostrarseccioncategoria);
formulario.elements.tbinventariocategoriaextratipo.addEventListener('change', actualizarvencimiento);
formulario.elements.tbinventariocategoriaadornotipo.addEventListener('change', actualizardiseno);
formulario.elements.tbinventariopreciopaquete.addEventListener('input', calcularpreciounidad);
formulario.elements.tbinventariounidadespaquete.addEventListener('input', calcularpreciounidad);

formulario.addEventListener('submit', async (evento) => {
    evento.preventDefault();

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
    botonguardar.disabled = true;

    const respuesta = await enviar(esedicion ? 'actualizar' : 'crear', new FormData(formulario));

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
    temporizadorbusqueda = setTimeout(cargarinventario, 300);
});

filtrocategoria.addEventListener('change', cargarinventario);
filtroestado.addEventListener('change', cargarinventario);
document.getElementById('botonnuevo').addEventListener('click', abrirnuevo);
document.getElementById('botoncancelar').addEventListener('click', () => dialogoformulario.close());
document.getElementById('botoncerrardetalle').addEventListener('click', () => dialogodetalle.close());

// "Ver historial" del producto que está abierto en el detalle (solo existe para la dueña)
let iddetalle = null;
const botonverhistorial = document.getElementById('botonverhistorial');
if (botonverhistorial) {
    botonverhistorial.addEventListener('click', () => ventanahistorial.mostrar('inventario', iddetalle, titulodetalle.textContent));
}

document.getElementById('botondescartarborrador').addEventListener('click', descartarborrador);

formulario.elements.tbinventariocategoriaextrafechavencimiento.min = new Date().toLocaleDateString('en-CA');

const proveedoreslistos = cargarproveedores();
cargarinventario();


// Si se entró desde "Agregar nuevo ..." del menú, abre el formulario
abrirformulariosipide(abrirnuevo);