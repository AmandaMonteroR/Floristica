const rutabase = 'index.php?modulo=reporte';
const ver = document.querySelector('main').dataset.ver;
const aviso = document.getElementById('aviso');

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function montocorto(monto) {
    return (monto < 0 ? '−₡' : '₡') + Math.abs(Number(monto)).toLocaleString('es-CR', { maximumFractionDigits: 0 });
}

function fechatexto(fecha) {
    return fecha.toLocaleDateString('en-CA');
}

function formatearfecha(fecha) {
    const [dia, hora] = String(fecha).split(' ');
    const [anio, mes, d] = dia.split('-');
    const texto = `${d}/${mes}/${anio}`;
    if (!hora || hora.startsWith('00:00')) {
        return texto;
    }
    const [h, m] = hora.split(':').map(Number);
    return `${texto} ${h % 12 || 12}:${String(m).padStart(2, '0')} ${h < 12 ? 'a. m.' : 'p. m.'}`;
}

async function consultar(accion, parametros = {}) {
    try {
        const respuesta = await fetch(`${rutabase}&accion=${accion}&${new URLSearchParams(parametros)}`);
        return await respuesta.json();
    } catch (error) {
        return { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
    }
}

function mostrarerror(mensaje) {
    aviso.textContent = mensaje;
    aviso.className = 'aviso aviso-error';
    aviso.hidden = false;
}

document.getElementById('botonimprimir').addEventListener('click', () => window.print());

if (ver === 'ganancia') {
    const campodesde = document.getElementById('desde');
    const campohasta = document.getElementById('hasta');
    const chipsperiodo = document.getElementById('chipsperiodo');

    const periodo = (nombre) => {
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
    };

    const marcarchip = (nombre) => {
        chipsperiodo.querySelectorAll('[data-periodo]').forEach((chip) => {
            const activo = chip.dataset.periodo === nombre;
            chip.classList.toggle('chip-activo', activo);
            chip.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
    };

    const linea = (etiqueta, monto, clase = '', nota = '') => `
        <div class="linea-resultado ${clase}">
            <dt>${etiqueta}${nota ? `<small class="texto-suave">${escapar(nota)}</small>` : ''}</dt>
            <dd>${montocorto(monto)}</dd>
        </div>`;

    const cargar = async (desde, hasta) => {
        const respuesta = await consultar('ganancia', { desde, hasta });
        if (!respuesta.exito) {
            mostrarerror(respuesta.mensaje);
            return;
        }
        aviso.hidden = true;

        const datos = respuesta.datos;
        campodesde.value = datos.desde;
        campohasta.value = datos.hasta;
        document.getElementById('tituloperiodo').textContent = `Del ${formatearfecha(datos.desde)} al ${formatearfecha(datos.hasta)}`;

        const categorias = Object.entries(datos.porcategoria)
            .map(([categoria, total]) => linea(`− ${escapar(categoria)}`, -total, 'linea-detalle')).join('');
        document.getElementById('resultados').innerHTML =
            linea('Vendido', datos.vendido, '', `${datos.ventas} ${datos.ventas === 1 ? 'encargo terminado' : 'encargos terminados'}`)
            + linea('− Gastos', -datos.gastos, '', datos.gastos > 0 ? 'Incluye las compras a proveedores' : 'Sin gastos en este período')
            + categorias
            + linea('= Balance', datos.balance, `linea-total ${datos.balance < 0 ? 'negativa' : 'positiva'}`,
                datos.vendido > 0 ? `${datos.margen}% de lo vendido` : '');

        document.getElementById('cuerpomeses').innerHTML = datos.meses.map((mes) => `
            <tr>
                <td>${escapar(mes.etiqueta)}</td>
                <td class="numero">${montocorto(mes.vendido)}</td>
                <td class="numero">${montocorto(mes.gastos)}</td>
                <td class="numero"><strong class="${mes.balance < 0 ? 'texto-peligro' : 'texto-ganancia'}">${montocorto(mes.balance)}</strong></td>
            </tr>`).join('');
    };

    chipsperiodo.addEventListener('click', (evento) => {
        const chip = evento.target.closest('[data-periodo]');
        if (chip) {
            marcarchip(chip.dataset.periodo);
            cargar(...periodo(chip.dataset.periodo));
        }
    });
    [campodesde, campohasta].forEach((campo) => campo.addEventListener('change', () => {
        if (campodesde.value && campohasta.value) {
            marcarchip('');
            cargar(campodesde.value, campohasta.value);
        }
    }));

    marcarchip('mes');
    cargar(...periodo('mes'));
}

if (ver === 'trazabilidad') {
    const tipotraza = document.getElementById('tipotraza');
    const registrotraza = document.getElementById('registrotraza');
    const etiquetatraza = document.getElementById('etiquetatraza');
    const traza = document.getElementById('traza');
    let opciones = { solicitudes: [], pedidos: [] };

    const llenarregistros = (elegido = '') => {
        const lista = tipotraza.value === 'pedido' ? opciones.pedidos : opciones.solicitudes;
        etiquetatraza.textContent = tipotraza.value === 'pedido' ? 'Pedido' : 'Encargo';
        registrotraza.innerHTML = '<option value="">Elija…</option>'
            + lista.map((item) => `<option value="${item.id}"${String(item.id) === String(elegido) ? ' selected' : ''}>${escapar(item.texto)}</option>`).join('');
    };

    const mostrar = async () => {
        const id = registrotraza.value;
        const parametros = new URLSearchParams(window.location.search);
        parametros.set('ver', 'trazabilidad');
        parametros.set('tipo', tipotraza.value);
        if (id) {
            parametros.set('id', id);
        } else {
            parametros.delete('id');
        }
        window.history.replaceState(null, '', `${window.location.pathname}?${parametros}`);

        if (!id) {
            traza.innerHTML = '<p class="texto-suave">Elija un encargo o un pedido para ver todo lo que pasó, cuándo y quién lo hizo.</p>';
            return;
        }

        const respuesta = await consultar('trazabilidad', { tipo: tipotraza.value, id });
        if (!respuesta.exito) {
            traza.innerHTML = `<p class="aviso aviso-error">${escapar(respuesta.mensaje)}</p>`;
            return;
        }

        const datos = respuesta.datos;
        traza.innerHTML = `
            <h2>${escapar(datos.titulo)}</h2>
            <dl class="detalle">${datos.resumen.map(([etiqueta, valor]) => `<dt>${escapar(etiqueta)}</dt><dd>${escapar(valor)}</dd>`).join('')}</dl>
            ${datos.duracion ? `<p class="duracion-traza">De principio a fin: <strong>${escapar(datos.duracion)}</strong></p>` : ''}
            <ol class="linea-tiempo">
                ${datos.eventos.map((evento) => `
                    <li class="evento evento-${evento.tipo}">
                        <time datetime="${escapar(evento.fecha.replace(' ', 'T'))}">${formatearfecha(evento.fecha)}</time>
                        <strong>${escapar(evento.texto)}</strong>
                        ${evento.detalle ? `<span class="detalle-evento">${escapar(evento.detalle)}</span>` : ''}
                        <span class="responsable">${evento.responsable ? `Responsable: ${escapar(evento.responsable)}` : 'Responsable: no registrado'}</span>
                    </li>`).join('') || '<li class="texto-suave">No hay movimientos registrados.</li>'}
            </ol>`;
    };

    (async () => {
        const respuesta = await consultar('opciones');
        if (!respuesta.exito) {
            mostrarerror(respuesta.mensaje);
            return;
        }
        opciones = respuesta.datos;
        const parametros = new URLSearchParams(window.location.search);
        if (parametros.get('tipo') === 'pedido') {
            tipotraza.value = 'pedido';
        }
        llenarregistros(parametros.get('id') ?? '');
        if (registrotraza.value) {
            mostrar();
        }
    })();

    tipotraza.addEventListener('change', () => {
        llenarregistros();
        mostrar();
    });
    registrotraza.addEventListener('change', mostrar);
}