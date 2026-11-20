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

function cortesseguros(contenido, escala) {
    const inicio = contenido.getBoundingClientRect().top;
    const cortes = [...contenido.querySelectorAll('li, tr, .linea-resultado, dt, h2, p, article')]
        .map((elemento) => Math.round((elemento.getBoundingClientRect().bottom - inicio) * escala));
    return [...new Set(cortes)].sort((a, b) => a - b);
}

async function descargarpdf() {
    const boton = document.getElementById('botonpdf');
    const esbalance = ver === 'ganancia';
    const contenido = esbalance ? document.querySelector('.reporte-ganancia') : document.getElementById('traza');

    if (!esbalance && !document.getElementById('registrotraza').value) {
        mostrarerror('Elija un encargo o un pedido para descargar su trazabilidad.');
        return;
    }

    const titulo = esbalance
        ? `Balance ${document.getElementById('tituloperiodo').textContent.replace(/^Del/, 'del')}`
        : `Trazabilidad: ${contenido.querySelector('h2')?.textContent || ''}`;

    boton.disabled = true;
    boton.textContent = 'Generando…';

    try {
        const escalalienzo = 2;
        const lienzo = await window.html2canvas(contenido, { scale: escalalienzo, backgroundColor: '#ffffff', useCORS: true });
        const cortes = cortesseguros(contenido, escalalienzo);
        const pdf = new window.jspdf.jsPDF({ orientation: 'portrait', unit: 'pt', format: 'letter', compress: true });
        const ancho = pdf.internal.pageSize.getWidth();
        const alto = pdf.internal.pageSize.getHeight();
        const margen = 36;
        const util = ancho - margen * 2;
        const escala = util / lienzo.width;

        pdf.setFillColor(176, 33, 84);
        pdf.rect(0, 0, ancho, 8, 'F');
        pdf.setFont('helvetica', 'bold');
        pdf.setFontSize(19);
        pdf.setTextColor(164, 32, 82);
        pdf.text('Floristica', margen, 42);
        pdf.setFontSize(12);
        pdf.setTextColor(60, 41, 52);
        pdf.text(pdf.splitTextToSize(titulo, util), margen, 60);
        pdf.setFont('helvetica', 'normal');
        pdf.setFontSize(9);
        pdf.setTextColor(125, 95, 110);
        pdf.text(`Generado el ${new Date().toLocaleDateString('es-CR')}`, ancho - margen, 42, { align: 'right' });

        let y = 80;
        let origen = 0;
        while (origen < lienzo.height) {
            const disponible = Math.floor((alto - margen - y) / escala);
            let trozo = Math.min(disponible, lienzo.height - origen);
            if (origen + trozo < lienzo.height) {
                const corte = cortes.filter((valor) => valor > origen + 40 && valor <= origen + disponible).pop();
                if (corte) {
                    trozo = corte - origen;
                }
            }

            const parte = document.createElement('canvas');
            parte.width = lienzo.width;
            parte.height = trozo;
            const contexto = parte.getContext('2d');
            contexto.fillStyle = '#ffffff';
            contexto.fillRect(0, 0, parte.width, parte.height);
            contexto.drawImage(lienzo, 0, origen, lienzo.width, trozo, 0, 0, lienzo.width, trozo);
            pdf.addImage(parte.toDataURL('image/jpeg', 0.92), 'JPEG', margen, y, util, trozo * escala);

            origen += trozo;
            if (origen < lienzo.height) {
                pdf.addPage();
                y = margen;
            }
        }

        const nombre = esbalance
            ? titulo.replace(/\//g, '-')
            : `Trazabilidad ${document.getElementById('tipotraza').value === 'pedido' ? 'pedido' : 'encargo'} ${document.getElementById('registrotraza').value}`;
        pdf.save(`${nombre}.pdf`);
    } catch (error) {
        mostrarerror('No se pudo generar el PDF. Intente de nuevo.');
    } finally {
        boton.disabled = false;
        boton.textContent = 'Descargar PDF';
    }
}

document.getElementById('botonpdf').addEventListener('click', descargarpdf);

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