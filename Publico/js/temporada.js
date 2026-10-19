// Temporada alta: encenderla o apagarla, y lo que cobra el proveedor por cada flor en temporada alta.
// La venta en temporada alta sube lo mismo que subió el costo por unidad (misma ganancia en colones).
const rutabase = 'index.php?modulo=temporada';

const cuerpotabla = document.getElementById('cuerpotabla');
const busqueda = document.getElementById('busqueda');
const aviso = document.getElementById('aviso');
const bannertemporada = document.getElementById('bannertemporada');
const textoestado = document.getElementById('textoestado');
const notaestado = document.getElementById('notaestado');
const botonalternar = document.getElementById('botonalternar');
const botonguardar = document.getElementById('botonguardar');
const resumenprecios = document.getElementById('resumenprecios');
const dialogoconfirmacion = document.getElementById('dialogoconfirmacion');
const iconoconfirmacion = document.getElementById('iconoconfirmacion');
const tituloconfirmacion = document.getElementById('tituloconfirmacion');
const mensajeconfirmacion = document.getElementById('mensajeconfirmacion');
const botonaceptarconfirmacion = document.getElementById('botonaceptarconfirmacion');
const botoncancelarconfirmacion = document.getElementById('botoncancelarconfirmacion');

let estado = null;
let temporizadoraviso;

function escapar(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

function formatearmonto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function montocorto(monto) {
    return '₡' + Number(monto).toLocaleString('es-CR', { maximumFractionDigits: 2 });
}

function filtrardecimal(valor) {
    const limpio = valor.replace(/,/g, '.').replace(/[^\d.]/g, '');
    const [entera, ...resto] = limpio.split('.');
    return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
}

// Igual que en el servidor: hacia arriba a ₡100
function redondear(monto) {
    return Math.ceil(Math.round(monto * 100) / 100 / 100) * 100;
}

async function peticion(accion, opciones = {}) {
    try {
        const respuesta = await fetch(`${rutabase}&accion=${accion}`, opciones);
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
    }, 6000);
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

/* ---------- Estado: encendida o apagada ---------- */

function mostrarestado() {
    const encendida = estado.encendida;
    bannertemporada.classList.toggle('banner-vigente', encendida);
    textoestado.innerHTML = encendida
        ? '✿ Temporada alta <span class="estado estado-vigente">Encendida</span>'
        : 'Temporada alta <span class="estado estado-inactivo">Apagada</span>';
    notaestado.textContent = encendida
        ? 'Las flores se cobran con el precio del proveedor en temporada alta y los arreglos suben solos según sus flores.'
        : 'Se usan los precios normales. Escriba abajo lo que cobra el proveedor en temporada alta y enciéndala cuando empiece.';
    botonalternar.textContent = encendida ? 'Apagar y volver a precios normales' : 'Encender temporada alta';
    botonalternar.className = encendida ? 'boton' : 'boton boton-principal';
    botonalternar.hidden = false;
    resumenprecios.textContent = `${estado.conprecio} de ${estado.flores.length} con precio de temporada alta`;
}

/* ---------- Tabla de flores ---------- */

// A cuánto se vende la unidad con ese precio del paquete
function ventaalta(flor, paquete) {
    if (!(paquete > 0) || Math.abs(paquete - flor.paquetenormal) < 0.01) {
        return flor.ventanormal;
    }
    const costonormal = flor.paquetenormal / flor.unidades;
    const costoalto = Math.round(paquete / flor.unidades * 100) / 100;
    return redondear(flor.ventanormal + costoalto - costonormal);
}

function textoventa(flor, valor) {
    const paquete = parseFloat(valor);
    if (valor === '' || !(paquete > 0)) {
        return `${montocorto(flor.ventanormal)} <small class="texto-suave">(sin cambio)</small>`;
    }
    const venta = ventaalta(flor, paquete);
    return `${montocorto(flor.ventanormal)} → <strong>${montocorto(venta)}</strong>`;
}

function dibujarflores() {
    const texto = busqueda.value.trim().toLowerCase();
    const flores = estado.flores.filter((flor) => flor.nombre.toLowerCase().includes(texto));

    if (flores.length === 0) {
        cuerpotabla.innerHTML = '<tr><td colspan="4" class="vacio">No hay flores que coincidan con la búsqueda.</td></tr>';
        return;
    }

    cuerpotabla.innerHTML = flores.map((flor) => {
        const valor = flor.editado ?? (flor.paquetealto !== null ? String(flor.paquetealto) : '');
        return `
        <tr data-id="${flor.id}">
            <td>${escapar(flor.nombre)}<br><small class="texto-suave">Paquete de ${flor.unidades}</small></td>
            <td>${formatearmonto(flor.paquetenormal)}</td>
            <td>
                <input type="text" name="precios[${flor.id}]" value="${escapar(valor)}" inputmode="decimal" maxlength="11"
                       autocomplete="off" placeholder="${flor.paquetenormal}" aria-label="Paquete de ${escapar(flor.nombre)} en temporada alta">
                <small class="error" data-error="${flor.id}"></small>
            </td>
            <td class="venta-alta">${textoventa(flor, valor)}</td>
        </tr>`;
    }).join('');
}

async function cargar() {
    const respuesta = await peticion('estado');

    if (!respuesta.exito) {
        textoestado.textContent = respuesta.mensaje;
        cuerpotabla.innerHTML = `<tr><td colspan="4" class="vacio">${escapar(respuesta.mensaje)}</td></tr>`;
        return;
    }

    estado = respuesta.datos;
    mostrarestado();
    dibujarflores();
    botonguardar.disabled = true;
}

/* ---------- Eventos ---------- */

cuerpotabla.addEventListener('input', (evento) => {
    const campo = evento.target;
    const fila = campo.closest('tr[data-id]');
    if (!fila) {
        return;
    }

    campo.value = filtrardecimal(campo.value);
    const flor = estado.flores.find((item) => String(item.id) === fila.dataset.id);
    flor.editado = campo.value;
    fila.querySelector('.venta-alta').innerHTML = textoventa(flor, campo.value);

    // Se revisa al momento: el precio debe ser mayor que 0
    const error = fila.querySelector('[data-error]');
    const malo = campo.value !== '' && !(parseFloat(campo.value) > 0);
    error.textContent = malo ? 'Debe ser un monto mayor que 0.' : '';
    campo.toggleAttribute('aria-invalid', malo);
    botonguardar.disabled = false;
});

busqueda.addEventListener('input', dibujarflores);

botonalternar.addEventListener('click', async () => {
    const encender = !estado.encendida;
    const aceptado = await confirmar(encender
        ? {
            titulo: '¿Encender la temporada alta?',
            mensaje: `Las flores con precio de temporada alta (${estado.conprecio}) y los arreglos que las llevan subirán de precio. Las solicitudes ya registradas no cambian.`,
            textoaceptar: 'Encender',
        }
        : {
            titulo: '¿Apagar la temporada alta?',
            mensaje: 'Se vuelve a los precios normales. Las solicitudes ya registradas no cambian.',
            textoaceptar: 'Apagar',
            peligro: true,
        });

    if (!aceptado) {
        return;
    }

    const respuesta = await peticion(encender ? 'activar' : 'desactivar', { method: 'POST', body: new FormData() });
    mostraraviso(respuesta.mensaje, respuesta.exito ? 'exito' : 'error');

    if (respuesta.exito) {
        // La barra de arriba muestra si está encendida, así que se recarga la página
        setTimeout(() => window.location.reload(), 900);
    }
});

botonguardar.addEventListener('click', async () => {
    // Se envían todas las flores, también las que no se ven por el filtro o la página
    const datos = new FormData();
    estado.flores.forEach((flor) => {
        const valor = flor.editado ?? (flor.paquetealto !== null ? String(flor.paquetealto) : '');
        datos.append(`precios[${flor.id}]`, valor.replace(/\.$/, ''));
    });

    botonguardar.disabled = true;
    const respuesta = await peticion('guardarprecios', { method: 'POST', body: datos });

    if (!respuesta.exito) {
        botonguardar.disabled = false;
        const errores = respuesta.datos && respuesta.datos.errores ? respuesta.datos.errores : {};
        Object.entries(errores).forEach(([id, mensaje]) => {
            const error = cuerpotabla.querySelector(`[data-error="${id}"]`);
            if (error) {
                error.textContent = mensaje;
            }
        });
        mostraraviso(Object.values(errores).join(' ') || respuesta.mensaje, 'error');
        return;
    }

    mostraraviso(respuesta.mensaje, 'exito');
    cargar();
});

document.getElementById('botonhistorial').addEventListener('click', () => {
    ventanahistorial.mostrar('temporada', estado ? estado.tbtemporadaid : 1, 'la temporada alta');
});

// Si quedan cambios sin guardar, se avisa antes de salir
window.addEventListener('beforeunload', (evento) => {
    if (!botonguardar.disabled) {
        evento.preventDefault();
        evento.returnValue = '';
    }
});

cargar();
