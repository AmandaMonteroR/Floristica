const rutabase = 'index.php?modulo=venta';

const campodesde = document.getElementById('desde');
const campohasta = document.getElementById('hasta');
const chipsperiodo = document.getElementById('chipsperiodo');
const chipsagrupar = document.getElementById('chipsagrupar');
const aviso = document.getElementById('aviso');
const cuerpobalance = document.getElementById('cuerpobalance');
const piebalance = document.getElementById('piebalance');
const cuerpoventas = document.getElementById('cuerpoventas');

const nombresagrupar = { dia: 'Día', semana: 'Semana', mes: 'Mes' };
const PORPAGINA = 7;
let agrupar = 'dia';
let datoscargados = null;

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function montocorto(monto) {
    const numero = Number(monto);
    return (numero < 0 ? '−' : '') + '₡' + Math.abs(numero).toLocaleString('es-CR', { maximumFractionDigits: 0 });
}

function formatearfecha(fechahora) {
    const [anio, mes, dia] = String(fechahora).slice(0, 10).split('-');
    return `${dia}/${mes}/${anio}`;
}

function fechatexto(fecha) {
    return fecha.toLocaleDateString('en-CA');
}

function clasebalance(monto) {
    return monto < 0 ? 'texto-peligro' : 'texto-ganancia';
}

function periodo(nombre) {
    const hoy = new Date();
    switch (nombre) {
        case 'hoy':
            return [fechatexto(hoy), fechatexto(hoy)];
        case 'semana': {
            const lunes = new Date(hoy);
            lunes.setDate(hoy.getDate() - ((hoy.getDay() + 6) % 7));
            return [fechatexto(lunes), fechatexto(hoy)];
        }
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

function marcar(contenedor, atributo, valor) {
    contenedor.querySelectorAll(`[data-${atributo}]`).forEach((chip) => {
        const activo = chip.dataset[atributo] === valor;
        chip.classList.toggle('chip-activo', activo);
        chip.setAttribute('aria-pressed', activo ? 'true' : 'false');
    });
}

function botonpagina(texto, destino, etiqueta, actual = false) {
    return `<button type="button" class="pagina${actual ? ' pagina-actual' : ''}" data-pagina="${destino}"`
        + ` aria-label="${etiqueta}"${actual ? ' aria-current="page"' : ''}>${texto}</button>`;
}

function numerospagina(pagina, total) {
    const salida = [];
    for (let numero = 1; numero <= total; numero++) {
        if (numero === 1 || numero === total || Math.abs(numero - pagina) <= 1) {
            salida.push(numero);
        } else if (salida[salida.length - 1] !== '…') {
            salida.push('…');
        }
    }
    return salida;
}

function crearpaginacion(nav, redibujar) {
    const paginacion = {
        pagina: 1,
        filas(lista) {
            return lista.slice((this.pagina - 1) * PORPAGINA, this.pagina * PORPAGINA);
        },
        dibujar(cantidad) {
            if (cantidad === 0) {
                nav.hidden = true;
                return;
            }
            const total = Math.ceil(cantidad / PORPAGINA);
            const desde = (this.pagina - 1) * PORPAGINA;
            const hasta = Math.min(desde + PORPAGINA, cantidad);
            const botones = numerospagina(this.pagina, total).map((numero) => (numero === '…'
                ? '<span class="pagina-puntos" aria-hidden="true">…</span>'
                : botonpagina(numero, numero, `Página ${numero}`, numero === this.pagina)));

            nav.innerHTML = `
                <span class="paginacion-resumen">${desde + 1}–${hasta} de ${cantidad}</span>
                <div class="paginacion-botones">
                    ${botonpagina('‹', this.pagina - 1, 'Página anterior')}
                    ${botones.join('')}
                    ${botonpagina('›', this.pagina + 1, 'Página siguiente')}
                </div>`;
            nav.querySelector('[aria-label="Página anterior"]').disabled = this.pagina === 1;
            nav.querySelector('[aria-label="Página siguiente"]').disabled = this.pagina === total;
            nav.hidden = false;
        },
    };

    nav.addEventListener('click', (evento) => {
        const destino = evento.target.closest('[data-pagina]');
        if (destino && !destino.disabled && datoscargados) {
            paginacion.pagina = Number(destino.dataset.pagina);
            redibujar();
        }
    });

    return paginacion;
}

const paginasbalance = crearpaginacion(document.getElementById('paginacionbalance'), () => dibujarbalance(datoscargados));
const paginasventas = crearpaginacion(document.getElementById('paginacionventas'), () => dibujarventas(datoscargados.ventas));

function dibujarbalance(datos) {
    document.getElementById('titulobalance').textContent = `Balance por ${nombresagrupar[datos.agrupar].toLowerCase()}`;
    document.getElementById('columnaperiodo').textContent = nombresagrupar[datos.agrupar];

    if (datos.periodos.length === 0) {
        cuerpobalance.innerHTML = '<tr><td colspan="6" class="vacio">No hubo ventas ni gastos en este período.</td></tr>';
        piebalance.innerHTML = '';
        paginasbalance.dibujar(0);
        return;
    }

    cuerpobalance.innerHTML = paginasbalance.filas(datos.periodos).map((fila) => `
        <tr>
            <td>${escapar(fila.etiqueta)}</td>
            <td class="numero">${fila.ventas}</td>
            <td class="numero">${montocorto(fila.vendido)}</td>
            <td class="numero">${montocorto(fila.gastos)}</td>
            <td class="numero"><strong class="${clasebalance(fila.balance)}">${montocorto(fila.balance)}</strong></td>
            <td class="columna-acciones">
                ${datos.agrupar !== 'dia' || datos.desde !== datos.hasta
                    ? `<button type="button" class="enlace" data-desde="${fila.desde}" data-hasta="${fila.hasta}">Ver detalle</button>` : ''}
            </td>
        </tr>`).join('');

    const totales = datos.totales;
    piebalance.innerHTML = `
        <tr>
            <td><strong>Total</strong></td>
            <td class="numero">${totales.ventas}</td>
            <td class="numero">${montocorto(totales.vendido)}</td>
            <td class="numero">${montocorto(totales.gastos)}</td>
            <td class="numero"><strong class="${clasebalance(totales.balance)}">${montocorto(totales.balance)}</strong></td>
            <td></td>
        </tr>`;
    paginasbalance.dibujar(datos.periodos.length);
}

function dibujarventas(ventas) {
    paginasventas.dibujar(ventas.length);
    cuerpoventas.innerHTML = ventas.length === 0
        ? '<tr><td colspan="4" class="vacio">No se vendió nada en este período.</td></tr>'
        : paginasventas.filas(ventas).map((venta) => `
            <tr>
                <td>${formatearfecha(venta.fecha)}</td>
                <td>${escapar(venta.cliente)}</td>
                <td>${escapar(venta.descripcion)}${venta.express ? ' <span class="texto-suave">· express</span>' : ''}${venta.temporadaalta
                    ? ' <span class="estado estado-vigente">Temporada alta</span>' : ''}</td>
                <td class="numero">${montocorto(venta.total)}</td>
            </tr>`).join('');
}

async function cargar(desde, hasta) {
    let respuesta;
    try {
        const url = `${rutabase}&accion=resumen&${new URLSearchParams({ desde, hasta, agrupar })}`;
        respuesta = await (await fetch(url)).json();
    } catch (error) {
        respuesta = { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
    }

    if (!respuesta.exito) {
        aviso.textContent = respuesta.mensaje;
        aviso.className = 'aviso aviso-error';
        aviso.hidden = false;
        return;
    }
    aviso.hidden = true;

    const datos = respuesta.datos;
    campodesde.value = datos.desde;
    campohasta.value = datos.hasta;

    const totales = datos.totales;
    document.getElementById('datovendido').textContent = montocorto(totales.vendido);
    document.getElementById('notavendido').textContent = totales.ventas === 0
        ? 'Sin ventas en este período'
        : `${totales.ventas} ${totales.ventas === 1 ? 'arreglo vendido' : 'arreglos vendidos'}`
            + (totales.express > 0 ? ` · ${montocorto(totales.express)} de express` : '');
    document.getElementById('datogastos').textContent = montocorto(totales.gastos);
    document.getElementById('notagastos').textContent = totales.gastos === 0
        ? 'Sin gastos en este período'
        : `${montocorto(totales.compras)} en compras a proveedores · ${montocorto(totales.otrosgastos)} en otros gastos`;
    document.getElementById('datobalance').textContent = montocorto(totales.balance);
    document.getElementById('tarjetabalance').className = `tarjeta-dato ${totales.balance < 0 ? 'tarjeta-perdida' : 'tarjeta-ganancia'}`;

    datoscargados = datos;
    paginasbalance.pagina = 1;
    paginasventas.pagina = 1;
    dibujarbalance(datos);
    dibujarventas(datos.ventas);
}

function recargar() {
    if (campodesde.value && campohasta.value) {
        cargar(campodesde.value, campohasta.value);
    }
}

chipsperiodo.addEventListener('click', (evento) => {
    const chip = evento.target.closest('[data-periodo]');
    if (!chip) {
        return;
    }
    marcar(chipsperiodo, 'periodo', chip.dataset.periodo);
    cargar(...periodo(chip.dataset.periodo));
});

chipsagrupar.addEventListener('click', (evento) => {
    const chip = evento.target.closest('[data-agrupar]');
    if (!chip) {
        return;
    }
    agrupar = chip.dataset.agrupar;
    marcar(chipsagrupar, 'agrupar', agrupar);
    recargar();
});

[campodesde, campohasta].forEach((campo) => campo.addEventListener('change', () => {
    marcar(chipsperiodo, 'periodo', '');
    recargar();
}));

cuerpobalance.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-desde]');
    if (!boton) {
        return;
    }
    agrupar = 'dia';
    marcar(chipsagrupar, 'agrupar', agrupar);
    marcar(chipsperiodo, 'periodo', '');
    cargar(boton.dataset.desde, boton.dataset.hasta);
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

marcar(chipsperiodo, 'periodo', 'mes');
marcar(chipsagrupar, 'agrupar', agrupar);
cargar(...periodo('mes'));