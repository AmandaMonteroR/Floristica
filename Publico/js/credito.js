// Cuentas por cobrar (clientas) y por pagar (proveedores): lista con el saldo de cada una, ventana para abonar
// y anular un abono mal registrado (no se borra: deja de sumar y queda con su motivo)
const rutabase = 'index.php?modulo=credito';
const ver = document.querySelector('main').dataset.ver;

const aviso = document.getElementById('aviso');
const cuerpotabla = document.getElementById('cuerpotabla');
const filtrotexto = document.getElementById('filtrotexto');
const filtroestado = document.getElementById('filtroestado');
const dialogocuenta = document.getElementById('dialogocuenta');
const titulocuenta = document.getElementById('titulocuenta');
const listacuenta = document.getElementById('listacuenta');
const cuerpoabonos = document.getElementById('cuerpoabonos');
const formularioabono = document.getElementById('formularioabono');
const avisoabono = document.getElementById('avisoabono');
const botonabonar = document.getElementById('botonabonar');
const dialogoanular = document.getElementById('dialogoanular');
const formularioanular = document.getElementById('formularioanular');
const mensajeanular = document.getElementById('mensajeanular');
const avisoanular = document.getElementById('avisoanular');
const botonanular = document.getElementById('botonanular');

let temporizadoraviso;
let cuentaactual = null;
let cuentas = { porpagar: [], porcobrar: [] };
let abonoaanular = null;

// Color de la etiqueta de cada estado de la cuenta
const clasesestado = {
    'Pagado': 'estado-activo',
    'Vencido': 'estado-bajo',
    'Entregada sin pagar': 'estado-bajo',
    'Por vencer': 'estado-vigente',
    'Con adelanto': 'estado-vigente',
    'Al día': 'estado-confirmado',
    'Sin pagar': 'estado-inactivo',
};

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

// "2026-10-11" -> "11/10/2026"
function formatearfecha(fecha) {
    const [anio, mes, dia] = String(fecha).slice(0, 10).split('-');
    return `${dia}/${mes}/${anio}`;
}

function hoy() {
    return new Date().toLocaleDateString('en-CA');
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

function mostraraviso(mensaje, tipo) {
    aviso.textContent = mensaje;
    aviso.className = `aviso aviso-${tipo}`;
    aviso.hidden = false;
    clearTimeout(temporizadoraviso);
    temporizadoraviso = setTimeout(() => {
        aviso.hidden = true;
    }, 5000);
}

function etiquetaestado(cuenta) {
    let texto = cuenta.estadocuenta;
    if (cuenta.tipo === 'pagar' && cuenta.saldo > 0) {
        if (cuenta.dias < 0) {
            texto += ` hace ${-cuenta.dias} ${cuenta.dias === -1 ? 'día' : 'días'}`;
        } else if (cuenta.dias === 0) {
            texto += ' (hoy)';
        } else if (cuenta.estadocuenta === 'Por vencer') {
            texto += ` en ${cuenta.dias} ${cuenta.dias === 1 ? 'día' : 'días'}`;
        }
    }
    return `<span class="estado ${clasesestado[cuenta.estadocuenta] ?? ''}">${escapar(texto)}</span>`;
}

function filacobrar(cuenta) {
    return `
        <tr>
            <td>${cuenta.id}</td>
            <td>${escapar(cuenta.nombre)}<br><small class="texto-suave">${escapar(cuenta.telefono)}</small></td>
            <td>${escapar(cuenta.detalle.split(' · ')[1] ?? '')}<br><small class="texto-suave">Entrega ${formatearfecha(cuenta.fechaentrega)}</small></td>
            <td class="numero">${formatearmonto(cuenta.monto)}</td>
            <td class="numero">${formatearmonto(cuenta.abonado)}</td>
            <td class="numero"><strong>${formatearmonto(cuenta.saldo)}</strong></td>
            <td>${etiquetaestado(cuenta)}</td>
            <td class="columna-acciones">
                <button type="button" class="enlace" data-tipo="cobrar" data-id="${cuenta.id}">${cuenta.saldo > 0 ? 'Abonar' : 'Ver'}</button>
            </td>
        </tr>`;
}

function filapagar(cuenta) {
    return `
        <tr>
            <td>${cuenta.id}</td>
            <td>${escapar(cuenta.nombre)}<br><small class="texto-suave">${escapar(cuenta.detalle.split(' · ')[1] ?? '')}</small></td>
            <td>${formatearfecha(cuenta.fechainicio)}<br><small class="texto-suave">${cuenta.plazo} días de plazo</small></td>
            <td>${formatearfecha(cuenta.vence)}</td>
            <td class="numero">${formatearmonto(cuenta.monto)}</td>
            <td class="numero">${formatearmonto(cuenta.abonado)}</td>
            <td class="numero"><strong>${formatearmonto(cuenta.saldo)}</strong></td>
            <td>${etiquetaestado(cuenta)}</td>
            <td class="columna-acciones">
                <button type="button" class="enlace" data-tipo="pagar" data-id="${cuenta.id}">${cuenta.saldo > 0 ? 'Abonar' : 'Ver'}</button>
            </td>
        </tr>`;
}

// Mayúsculas y tildes no cuentan al buscar
function sintildes(texto) {
    return String(texto ?? '').normalize('NFD').replace(/\p{M}/gu, '').toLowerCase();
}

// La lista con los filtros: estado (por defecto, solo las que deben algo) y lo que se escribió en Buscar
function mostrarlista() {
    const estado = filtroestado.value;
    const texto = sintildes(filtrotexto.value.trim());
    const lista = (ver === 'pagar' ? cuentas.porpagar : cuentas.porcobrar).filter((cuenta) => {
        if (estado === 'pendientes' ? cuenta.saldo <= 0 : estado !== '' && cuenta.estadocuenta !== estado) {
            return false;
        }
        return texto === '' || sintildes(`${cuenta.nombre} ${cuenta.telefono ?? ''} ${cuenta.id} n.º ${cuenta.id}`).includes(texto);
    });
    const filtrado = estado !== 'pendientes' || texto !== '';

    if (ver === 'pagar') {
        cuerpotabla.innerHTML = lista.length
            ? lista.map(filapagar).join('')
            : `<tr><td colspan="9" class="vacio">${filtrado ? 'No hay cuentas con esos filtros.' : 'No se le debe nada a los proveedores. Los pedidos a crédito aparecen aquí al confirmarlos.'}</td></tr>`;
    } else {
        cuerpotabla.innerHTML = lista.length
            ? lista.map(filacobrar).join('')
            : `<tr><td colspan="8" class="vacio">${filtrado ? 'No hay cuentas con esos filtros.' : 'Las clientas no deben nada.'}</td></tr>`;
    }
}

async function cargar() {
    const respuesta = await peticion('listar', {}, { todas: 1 });
    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const { totales } = respuesta.datos;
    document.getElementById('datocobrarentregado').textContent = montocorto(totales.porcobrarentregado);
    document.getElementById('datocobrarabierto').textContent = montocorto(totales.porcobrarabierto);
    document.getElementById('datopagar').textContent = montocorto(totales.porpagar);
    document.getElementById('notapagar').textContent = totales.cuentasvencidas > 0
        ? `${montocorto(totales.vencido)} vencido (${totales.cuentasvencidas} ${totales.cuentasvencidas === 1 ? 'pedido' : 'pedidos'})`
        : 'Nada vencido';
    document.getElementById('tarjetapagar').classList.toggle('tarjeta-alerta', totales.cuentasvencidas > 0);

    cuentas = { porpagar: respuesta.datos.porpagar, porcobrar: respuesta.datos.porcobrar };
    mostrarlista();
}

function limpiarerrores() {
    formularioabono.querySelectorAll('[data-error]').forEach((error) => {
        error.textContent = '';
    });
    formularioabono.querySelectorAll('[aria-invalid]').forEach((campo) => campo.removeAttribute('aria-invalid'));
    avisoabono.hidden = true;
}

// Un abono: los anulados salen tachados, con el motivo y quién lo anuló
function filaabono(abono) {
    const anulado = abono.estado === 0;
    const accion = anulado
        ? `<span class="estado estado-inactivo">Anulado</span>`
        : `<button type="button" class="enlace enlace-peligro" data-anular="${abono.id}">Anular</button>`;
    const motivo = anulado
        ? `<small class="texto-peligro motivo-anulacion">Anulado${abono.anuladopor ? ` por ${escapar(abono.anuladopor)}` : ''}${abono.fechaanulacion ? ` el ${formatearfecha(abono.fechaanulacion)}` : ''}: ${escapar(abono.motivo)}</small>`
        : '';
    return `
        <tr class="${anulado ? 'fila-anulada' : ''}">
            <td>${formatearfecha(abono.fecha)}</td>
            <td><span class="${anulado ? 'tachado' : ''}">${formatearmonto(abono.monto)}</span></td>
            <td>${escapar(abono.metodo)}</td>
            <td>${abono.nota ? escapar(abono.nota) : '<span class="texto-suave">—</span>'}${motivo}</td>
            <td>${abono.hechopor ? escapar(abono.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
            <td class="columna-acciones">${accion}</td>
        </tr>`;
}

async function abrircuenta(tipo, id) {
    const respuesta = await peticion('obtener', {}, { tipo, id });
    if (!respuesta.exito) {
        mostraraviso(respuesta.mensaje, 'error');
        return;
    }

    const cuenta = respuesta.datos;
    cuentaactual = cuenta;
    titulocuenta.textContent = tipo === 'pagar'
        ? `Pedido N.º ${cuenta.id} · ${cuenta.nombre}`
        : `Solicitud N.º ${cuenta.id} · ${cuenta.nombre}`;

    const filas = tipo === 'pagar'
        ? [
            ['Comprado a crédito', `${formatearfecha(cuenta.fechainicio)}, a ${cuenta.plazo} días`],
            ['Vence', formatearfecha(cuenta.vence)],
            ['Monto de la compra', formatearmonto(cuenta.monto)],
        ]
        : [
            ['Teléfono', cuenta.telefono],
            ['Solicitud', `${cuenta.detalle.split(' · ')[1] ?? ''} · entrega ${formatearfecha(cuenta.fechaentrega)}`],
            ['Total', formatearmonto(cuenta.monto)],
        ];
    filas.push(['Pagado', formatearmonto(cuenta.abonado)], ['Saldo', formatearmonto(cuenta.saldo)], ['Estado', etiquetaestado(cuenta)]);
    listacuenta.innerHTML = filas.map(([etiqueta, valor]) => `<dt>${escapar(etiqueta)}</dt><dd>${etiqueta === 'Estado' ? valor : escapar(valor)}</dd>`).join('');

    cuerpoabonos.innerHTML = cuenta.abonos.length
        ? cuenta.abonos.map(filaabono).join('')
        : '<tr><td colspan="6" class="vacio">Todavía no hay abonos.</td></tr>';

    // El formulario solo aparece si queda saldo
    formularioabono.reset();
    limpiarerrores();
    formularioabono.hidden = cuenta.saldo <= 0;
    botonabonar.hidden = cuenta.saldo <= 0;
    const metodos = formularioabono.elements.metodo;
    metodos.innerHTML = '<option value="">Seleccione…</option>'
        + cuenta.metodos.map((metodo) => `<option value="${escapar(metodo)}">${escapar(metodo)}</option>`).join('');
    formularioabono.elements.fecha.value = hoy();
    formularioabono.elements.fecha.max = hoy();
    formularioabono.elements.fecha.min = cuenta.fechainicio;

    dialogocuenta.showModal();
    if (cuenta.saldo > 0) {
        formularioabono.elements.monto.focus();
    }
}

cuerpotabla.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-tipo]');
    if (boton) {
        abrircuenta(boton.dataset.tipo, boton.dataset.id);
    }
});

document.getElementById('botontodo').addEventListener('click', () => {
    if (cuentaactual) {
        formularioabono.elements.monto.value = String(cuentaactual.saldo);
        formularioabono.elements.monto.dispatchEvent(new Event('input', { bubbles: true }));
    }
});

// El monto solo acepta números con hasta 2 decimales
formularioabono.addEventListener('input', (evento) => {
    const campo = evento.target;
    if (campo.name === 'monto') {
        const limpio = campo.value.replace(/,/g, '.').replace(/[^\d.]/g, '');
        const [entera, ...resto] = limpio.split('.');
        campo.value = resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
    }
    const error = formularioabono.querySelector(`[data-error="${campo.name}"]`);
    if (error) {
        error.textContent = '';
    }
    campo.removeAttribute('aria-invalid');
});

formularioabono.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    if (!cuentaactual) {
        return;
    }
    limpiarerrores();
    botonabonar.disabled = true;

    const datos = new FormData(formularioabono);
    datos.append('tipo', cuentaactual.tipo);
    datos.append('id', cuentaactual.id);
    const respuesta = await peticion('abonar', { method: 'POST', body: datos });
    botonabonar.disabled = false;

    if (respuesta.exito) {
        dialogocuenta.close();
        mostraraviso(respuesta.mensaje, 'exito');
        cargar();
        return;
    }

    const errores = (respuesta.datos && respuesta.datos.errores) || {};
    Object.entries(errores).forEach(([campo, mensaje]) => {
        const error = formularioabono.querySelector(`[data-error="${campo}"]`);
        if (error) {
            error.textContent = mensaje;
        }
        if (formularioabono.elements[campo]) {
            formularioabono.elements[campo].setAttribute('aria-invalid', 'true');
        }
    });
    avisoabono.textContent = respuesta.mensaje;
    avisoabono.hidden = false;
    const primero = formularioabono.querySelector('[aria-invalid="true"]');
    if (primero) {
        primero.focus();
    }
});

document.getElementById('botoncerrarcuenta').addEventListener('click', () => dialogocuenta.close());

/* ---------- Anular un abono ---------- */

function limpiarerroresanular() {
    formularioanular.querySelector('[data-error="motivo"]').textContent = '';
    formularioanular.elements.motivo.removeAttribute('aria-invalid');
    avisoanular.hidden = true;
}

cuerpoabonos.addEventListener('click', (evento) => {
    const boton = evento.target.closest('button[data-anular]');
    if (!boton || !cuentaactual) {
        return;
    }
    abonoaanular = cuentaactual.abonos.find((abono) => abono.id === Number(boton.dataset.anular));
    if (!abonoaanular) {
        return;
    }
    formularioanular.reset();
    limpiarerroresanular();
    mensajeanular.textContent = `El abono de ${formatearmonto(abonoaanular.monto)} del ${formatearfecha(abonoaanular.fecha)} `
        + `(${abonoaanular.metodo}) dejará de contar y el saldo subirá ${formatearmonto(abonoaanular.monto)}. `
        + 'No se borra: queda en la lista con el motivo. Si el monto estaba mal, registre después el abono correcto.';
    dialogoanular.showModal();
    formularioanular.elements.motivo.focus();
});

document.getElementById('botoncancelaranular').addEventListener('click', () => dialogoanular.close());

formularioanular.elements.motivo.addEventListener('input', limpiarerroresanular);

formularioanular.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    if (!cuentaactual || !abonoaanular) {
        return;
    }
    limpiarerroresanular();
    botonanular.disabled = true;

    const datos = new FormData(formularioanular);
    datos.append('tipo', cuentaactual.tipo);
    datos.append('id', cuentaactual.id);
    datos.append('idabono', abonoaanular.id);
    const respuesta = await peticion('anularabono', { method: 'POST', body: datos });
    botonanular.disabled = false;

    if (respuesta.exito) {
        dialogoanular.close();
        mostraraviso(respuesta.mensaje, 'exito');
        // Se vuelve a abrir la cuenta con el saldo nuevo y el abono tachado
        dialogocuenta.close();
        await abrircuenta(cuentaactual.tipo, cuentaactual.id);
        cargar();
        return;
    }

    const error = respuesta.datos && respuesta.datos.errores && respuesta.datos.errores.motivo;
    if (error) {
        formularioanular.querySelector('[data-error="motivo"]').textContent = error;
        formularioanular.elements.motivo.setAttribute('aria-invalid', 'true');
        formularioanular.elements.motivo.focus();
    }
    avisoanular.textContent = respuesta.mensaje;
    avisoanular.hidden = false;
});
filtroestado.addEventListener('change', mostrarlista);
filtrotexto.addEventListener('input', mostrarlista);
filtrosdesdeurl({ estado: filtroestado });

cargar();
