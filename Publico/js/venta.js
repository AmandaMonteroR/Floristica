// Ventas y ganancias: solicitudes terminadas en un período
const rutabase = 'index.php?modulo=venta';

const cuerpotabla = document.getElementById('cuerpotabla');
const campodesde = document.getElementById('desde');
const campohasta = document.getElementById('hasta');
const chipsperiodo = document.getElementById('chipsperiodo');
const aviso = document.getElementById('aviso');

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

// "2026-10-11 15:25:00" -> "11/10/2026"
function formatearfecha(fechahora) {
    const [anio, mes, dia] = String(fechahora).slice(0, 10).split('-');
    return `${dia}/${mes}/${anio}`;
}

function fechatexto(fecha) {
    return fecha.toLocaleDateString('en-CA');
}

// Fechas de cada botón de período
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
        case 'mespasado': {
            const inicio = new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1);
            const fin = new Date(hoy.getFullYear(), hoy.getMonth(), 0);
            return [fechatexto(inicio), fechatexto(fin)];
        }
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

async function cargar(desde, hasta) {
    let respuesta;
    try {
        const url = `${rutabase}&accion=resumen&${new URLSearchParams({ desde, hasta })}`;
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
        : `${totales.ventas} ${totales.ventas === 1 ? 'venta' : 'ventas'}`
            + (totales.express > 0 ? ` · incluye ${montocorto(totales.express)} de express` : '');
    document.getElementById('datocosto').textContent = montocorto(totales.costo);
    document.getElementById('datoganancia').textContent = montocorto(totales.ganancia);
    document.getElementById('notaganancia').textContent = totales.vendido > 0
        ? `${totales.margen}% de lo vendido` : '';

    if (datos.ventas.length === 0) {
        cuerpotabla.innerHTML = '<tr><td colspan="7" class="vacio">No hay solicitudes terminadas en este período.</td></tr>';
        return;
    }

    cuerpotabla.innerHTML = datos.ventas.map((venta) => `
        <tr>
            <td>${venta.tbsolicitudencargoid}</td>
            <td>${formatearfecha(venta.fecha)}</td>
            <td>${escapar(venta.cliente)}</td>
            <td>${escapar(venta.descripcion)}${venta.temporadaalta ? ' <span class="estado estado-vigente">Temporada alta</span>' : ''}</td>
            <td>${formatearmonto(venta.total)}</td>
            <td>${formatearmonto(venta.costo)}</td>
            <td class="${venta.ganancia < 0 ? 'texto-peligro' : 'texto-ganancia'}">${formatearmonto(venta.ganancia)}</td>
        </tr>`).join('');
}

chipsperiodo.addEventListener('click', (evento) => {
    const chip = evento.target.closest('[data-periodo]');
    if (!chip) {
        return;
    }
    marcarchip(chip.dataset.periodo);
    const [desde, hasta] = periodo(chip.dataset.periodo);
    cargar(desde, hasta);
});

[campodesde, campohasta].forEach((campo) => campo.addEventListener('change', () => {
    if (campodesde.value && campohasta.value) {
        marcarchip('');
        cargar(campodesde.value, campohasta.value);
    }
}));

marcarchip('mes');
cargar(...periodo('mes'));
