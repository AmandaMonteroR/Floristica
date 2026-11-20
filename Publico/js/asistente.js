(() => {
    const caja = document.getElementById('asistente');
    const boton = document.getElementById('botonsugerir');
    const lista = document.getElementById('propuestas');
    const nota = document.getElementById('notaasistente');
    const campopresupuesto = document.getElementById('campopresupuesto');
    const seccionpersonalizado = document.getElementById('seccionpersonalizado');
    const titulo = document.getElementById('tituloasistente');
    const explicacion = document.getElementById('explicacionasistente');
    let propuestas = [];
    let modo = '';

    const eslibre = () => campopresupuesto.hidden;

    function revisarvisible() {
        caja.hidden = seccionpersonalizado.hidden;
        const nuevo = caja.hidden ? '' : (eslibre() ? 'libre' : 'presupuesto');
        if (nuevo !== modo) {
            limpiar();
            modo = nuevo;
        }
        titulo.textContent = eslibre() ? 'Asistente de arreglos' : 'Asistente de presupuesto';
        explicacion.textContent = eslibre()
            ? 'Propone arreglos con el stock disponible y este % de ganancia. Respeta los productos ya elegidos.'
            : 'Propone arreglos dentro del presupuesto con este % de ganancia. Respeta los productos ya elegidos.';
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
        const sinstock = propuesta.sobra === null && propuesta.sinstock.length
            ? `<p class="sobra-propuesta">No alcanza el stock para la receta completa de ${escapar(propuesta.sinstock.join(', '))}: van las que hay.</p>`
            : '';
        const sobra = propuesta.sobra !== null && propuesta.sobra >= 100
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
                ${sobra}${sinstock}
                <button type="button" class="boton boton-principal" data-usar="${indice}">Usar esta propuesta</button>
            </article>`;
    }

    async function sugerir() {
        const libre = eslibre();
        const tope = presupuesto();
        if (!libre && (tope === null || !(tope > 0))) {
            nota.textContent = 'Primero escriba el presupuesto de la clienta.';
            return;
        }

        const datos = new FormData();
        datos.append('modalidad', libre ? 'Libre' : 'Presupuesto');
        datos.append('presupuesto', libre ? '' : tope);
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
        const elegidos = lineas.some((linea) => linea.tbinventarioid);
        nota.textContent = (respuesta.datos.libre
            ? `Propuestas del tamaño de las recetas del catálogo, con ${respuesta.datos.porcentaje}% de ganancia`
            : `Propuestas para ${formatearmonto(respuesta.datos.presupuesto)} con ${respuesta.datos.porcentaje}% de ganancia`)
            + (elegidos ? ', con los productos que ya eligió.' : '.');
        lista.innerHTML = propuestas.map(tarjeta).join('');
    }

    function usar(indice) {
        const propuesta = propuestas[indice];
        if (!propuesta) {
            return;
        }

        lineas = propuesta.lineas.map((linea) => ({
            tbinventarioid: String(linea.tbinventarioid),
            cantidad: String(linea.cantidad),
            nombre: linea.nombre,
            precioguardado: null,
        }));
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

    [campopresupuesto, seccionpersonalizado].forEach((elemento) => new MutationObserver(revisarvisible)
        .observe(elemento, { attributes: true, attributeFilter: ['hidden'] }));
    document.getElementById('dialogoformulario').addEventListener('close', limpiar);
    revisarvisible();

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

    const arreglopedido = parametros.get('arreglo');
    if (arreglopedido) {
        history.replaceState(null, '', 'index.php?modulo=encargo');
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