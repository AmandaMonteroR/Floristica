// Asistente de presupuesto (solicitud personalizada con presupuesto).
// Pide al servidor hasta 3 propuestas que caben en el presupuesto con el % de ganancia,
// a partir de las recetas del catálogo. Los productos que ya se eligieron se mantienen.
// Usa formulario, lineas, dibujarlineas(), actualizarsecciones(), calcular(), presupuesto(), porcentaje(),
// espropietario, escapar(), formatearmonto(), montocorto(), guardarborrador() y enviar() de encargo.js.
// La foto de cada propuesta se agranda con la lupa (lupa.js) y queda como foto de referencia al usarla.
(() => {
    const caja = document.getElementById('asistente');
    const boton = document.getElementById('botonsugerir');
    const lista = document.getElementById('propuestas');
    const nota = document.getElementById('notaasistente');
    const campopresupuesto = document.getElementById('campopresupuesto');
    let propuestas = [];

    // Se muestra solo cuando el personalizado es "Con presupuesto" (igual que el campo del presupuesto)
    function revisarvisible() {
        caja.hidden = campopresupuesto.hidden;
        if (caja.hidden) {
            limpiar();
        }
    }

    function limpiar() {
        propuestas = [];
        lista.innerHTML = '';
        nota.textContent = '';
    }

    function textolinea(linea) {
        const pedidas = linea.pedidas > 0
            ? ` <small class="texto-suave">(${linea.pedidas === linea.cantidad ? 'las que pidió' : `incluye ${linea.pedidas} que pidió`})</small>` : '';
        return `<li><strong>${linea.cantidad}</strong> ${escapar(linea.nombre)}${pedidas}</li>`;
    }

    function tarjeta(propuesta, indice) {
        const flores = propuesta.lineas.filter((linea) => linea.categoria === 'Flor');
        const otros = propuesta.lineas.filter((linea) => linea.categoria !== 'Flor');
        const sobra = propuesta.sobra >= 100
            ? `<p class="sobra-propuesta">Sobran ${formatearmonto(propuesta.sobra)} del presupuesto.`
                + (propuesta.sinstock.length ? ` No hay más ${escapar(propuesta.sinstock.join(', '))} en inventario.` : '')
                + (propuesta.extras.length
                    ? ` Con eso puede agregar: ${propuesta.extras.map((extra) => `${escapar(extra.nombre)} (${montocorto(extra.precio)})`).join(', ')}.`
                    : '')
                + '</p>'
            : '';

        return `
            <article class="propuesta">
                <img class="foto-propuesta" src="${escapar(propuesta.tbarreglofloralfoto)}" alt="${escapar(propuesta.nombre)}"
                     loading="lazy" data-lupa title="Pase el mouse para ver más grande; clic para ampliar">
                <header class="propuesta-encabezado">
                    <h4>${escapar(propuesta.nombre)}</h4>
                    <p class="total-propuesta">${formatearmonto(propuesta.total)}</p>
                    ${espropietario ? `<p class="texto-suave">Ganancia ${formatearmonto(propuesta.ganancia)}</p>` : ''}
                </header>
                <p class="subtitulo-propuesta">Flores y follajes (${propuesta.flores})</p>
                <ul class="lineas-propuesta">${flores.map(textolinea).join('')}</ul>
                ${otros.length ? `<p class="subtitulo-propuesta">Presentación y adicionales</p>
                <ul class="lineas-propuesta">${otros.map(textolinea).join('')}</ul>` : ''}
                ${sobra}
                <button type="button" class="boton boton-principal" data-usar="${indice}">Usar esta propuesta</button>
            </article>`;
    }

    async function sugerir() {
        const tope = presupuesto();
        if (tope === null || !(tope > 0)) {
            nota.textContent = 'Primero escriba el presupuesto de la clienta.';
            return;
        }

        const datos = new FormData();
        datos.append('presupuesto', tope);
        datos.append('porcentaje', porcentaje());
        datos.append('reservado', calcular().pintura);
        lineas.filter((linea) => linea.tbinventarioid && Number(linea.cantidad) > 0).forEach((linea, posicion) => {
            datos.append(`insumos[${posicion}][tbinventarioid]`, linea.tbinventarioid);
            datos.append(`insumos[${posicion}][cantidad]`, linea.cantidad);
        });

        boton.disabled = true;
        boton.textContent = 'Buscando…';
        const respuesta = await enviar('sugerir', datos);
        boton.disabled = false;
        boton.textContent = 'Sugerir arreglos';

        if (!respuesta.exito) {
            limpiar();
            nota.textContent = respuesta.mensaje;
            return;
        }

        propuestas = respuesta.datos.propuestas;
        nota.textContent = `Propuestas para ${formatearmonto(respuesta.datos.presupuesto)} con ${respuesta.datos.porcentaje}% de ganancia`
            + (lineas.some((linea) => linea.tbinventarioid) ? ', con los productos que ya eligió.' : '.');
        lista.innerHTML = propuestas.map(tarjeta).join('');
    }

    function usar(indice) {
        const propuesta = propuestas[indice];
        if (!propuesta) {
            return;
        }

        // Los productos de la propuesta reemplazan a los de la tabla (incluye los que ya se habían elegido)
        lineas = propuesta.lineas.map((linea) => ({
            tbinventarioid: String(linea.tbinventarioid),
            cantidad: String(linea.cantidad),
            nombre: linea.nombre,
            precioguardado: null,
        }));
        // La foto del arreglo del catálogo queda como foto de referencia de la solicitud
        formulario.elements.tbsolicitudencargoreferencia.value = propuesta.tbarreglofloralfoto || '';
        actualizarsecciones();
        guardarborrador();
        limpiar();
        nota.textContent = `Se usó la propuesta "${propuesta.nombre}" y su foto quedó como foto de referencia. Puede ajustar las cantidades en la tabla.`;
        document.getElementById('seccionlineas').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    boton.addEventListener('click', sugerir);
    lista.addEventListener('click', (evento) => {
        const elegido = evento.target.closest('[data-usar]');
        if (elegido) {
            usar(Number(elegido.dataset.usar));
        }
    });

    new MutationObserver(revisarvisible).observe(campopresupuesto, { attributes: true, attributeFilter: ['hidden'] });
    document.getElementById('dialogoformulario').addEventListener('close', limpiar);
    revisarvisible();

    // Desde "Valorar este ramo" del catálogo: abre una solicitud personalizada (libre) con los
    // productos de la valoración, su % de ganancia y la foto de la clienta como referencia
    const parametros = new URLSearchParams(window.location.search);
    let valoracion = null;
    try {
        valoracion = parametros.get('valoracion') ? JSON.parse(sessionStorage.getItem('floristica.valoracion')) : null;
        sessionStorage.removeItem('floristica.valoracion');
    } catch (error) {
        valoracion = null;
    }
    if (parametros.get('valoracion')) {
        history.replaceState(null, '', 'index.php?modulo=encargo');
    }
    if (valoracion && Array.isArray(valoracion.lineas)) {
        borrarborrador();
        abrirnuevo().then(() => {
            const tipo = formulario.elements.tbsolicitudencargotipo;
            tipo.value = 'Personalizado';
            tipo.dispatchEvent(new Event('change', { bubbles: true }));
            const modalidad = formulario.elements.tbsolicitudencargomodalidad;
            modalidad.value = 'Libre';
            modalidad.dispatchEvent(new Event('change', { bubbles: true }));
            formulario.elements.tbsolicitudencargoporcentaje.value = String(valoracion.porcentaje ?? 100);
            formulario.elements.tbsolicitudencargoreferencia.value = valoracion.referencia || '';
            lineas = valoracion.lineas.map((linea) => ({
                tbinventarioid: String(linea.tbinventarioid),
                cantidad: String(linea.cantidad),
                nombre: linea.nombre,
                precioguardado: null,
            }));
            marcarporcentaje();
            actualizarsecciones();
            guardarborrador();
        });
    }

    // Desde "Buscar por foto" del catálogo: abre una solicitud nueva con ese arreglo ya elegido
    const arreglopedido = parametros.get('arreglo');
    if (arreglopedido) {
        history.replaceState(null, '', 'index.php?modulo=encargo');
        // Una solicitud nueva y limpia: no se recupera un borrador viejo
        borrarborrador();
        abrirnuevo().then(() => {
            const tipo = formulario.elements.tbsolicitudencargotipo;
            tipo.value = 'Predefinido';
            tipo.dispatchEvent(new Event('change', { bubbles: true }));
            const arreglo = formulario.elements.tbarreglofloralid;
            arreglo.value = arreglopedido;
            arreglo.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
})();
