// Catálogo → "Buscar por foto" → "Valorar este ramo".
// Si ningún arreglo del catálogo se parece a la foto de la clienta, el sistema calcula cuánto
// costaría hacer ese ramo con lo que hay en el inventario: la empleada dice el tamaño (cuántas
// flores ve en la foto) y el servidor reparte las flores según los colores de la foto, agrega
// follaje, papel y cinta, y cobra cada producto con el % de ganancia.
// Usa escapar(), formatearmonto(), enviar() y espropietario de arreglofloral.js.
(() => {
    const resultados = document.getElementById('resultadosfoto');
    const TAMANOS = [['Pequeño', 6], ['Mediano', 12], ['Grande', 24]];
    const PORCENTAJES = [50, 80, 100, 150];
    const PARECIDOALTO = 75;

    let foto = null;          // la foto achicada que se subió en la búsqueda
    let referencia = '';      // dónde la guardó el servidor (para volver a calcular sin subirla otra vez)
    let flores = 12;
    let porcentaje = 100;
    let valoracion = null;
    let espera = null;
    let pedido = 0;

    const caja = document.createElement('section');
    caja.className = 'valorar-ramo';
    caja.setAttribute('aria-labelledby', 'titulovalorar');

    function chips(lista, actual, atributo, texto) {
        return lista.map((valor) => {
            const numero = Array.isArray(valor) ? valor[1] : valor;
            return `<button type="button" class="chip ${numero === actual ? 'chip-activo' : ''}" data-${atributo}="${numero}">${texto(valor)}</button>`;
        }).join('');
    }

    function dibujar(destacado) {
        caja.classList.toggle('valorar-destacado', destacado);
        caja.innerHTML = `
            <div class="valorar-encabezado">
                <strong id="titulovalorar"><span aria-hidden="true">&#10047;</span> Valorar este ramo</strong>
                <p class="nota">${destacado
                    ? 'Ningún arreglo del catálogo se parece mucho. Calcule cuánto costaría hacer el ramo de la foto con lo que hay en el inventario.'
                    : '¿No es ninguno de estos? Calcule cuánto costaría hacer el ramo de la foto con lo que hay en el inventario.'}</p>
            </div>
            <div class="valorar-campos">
                <div class="campo">
                    <span id="etiquetaflores">¿Cuántas flores tiene el ramo?</span>
                    <div class="chips-valorar" role="group" aria-labelledby="etiquetaflores">
                        ${chips(TAMANOS, flores, 'flores', ([nombre, numero]) => `${nombre} · ${numero}`)}
                        <input type="text" class="campo-otro" id="otroflores" inputmode="numeric" maxlength="3"
                               placeholder="Otro" aria-label="Otra cantidad de flores"
                               value="${TAMANOS.some(([, numero]) => numero === flores) ? '' : flores}">
                    </div>
                    <small class="nota-campo texto-suave">El sistema no cuenta las flores: elija el tamaño o escriba cuántas ve en la foto.</small>
                </div>
                <div class="campo">
                    <span id="etiquetaganancia">Ganancia sobre el costo</span>
                    <div class="chips-valorar" role="group" aria-labelledby="etiquetaganancia">
                        ${chips(PORCENTAJES, porcentaje, 'porcentaje', (valor) => `${valor}%`)}
                        <input type="text" class="campo-otro" id="otroporcentaje" inputmode="numeric" maxlength="3"
                               placeholder="Otro %" aria-label="Otro % de ganancia"
                               value="${PORCENTAJES.includes(porcentaje) ? '' : porcentaje}">
                    </div>
                </div>
            </div>
            <div id="resultadovaloracion" aria-live="polite"></div>`;
        if (valoracion) {
            mostrar(valoracion);
        } else {
            caja.querySelector('#resultadovaloracion').innerHTML = `
                <button type="button" class="boton boton-principal" data-calcular>Calcular precio</button>`;
        }
    }

    function marcar() {
        caja.querySelectorAll('[data-flores]').forEach((chip) => chip.classList.toggle('chip-activo', Number(chip.dataset.flores) === flores));
        caja.querySelectorAll('[data-porcentaje]').forEach((chip) => chip.classList.toggle('chip-activo', Number(chip.dataset.porcentaje) === porcentaje));
    }

    function mostrar(datos) {
        const destino = caja.querySelector('#resultadovaloracion');
        const filas = datos.lineas.map((linea) => `
            <tr>
                <td>${escapar(linea.nombre)}${linea.tipo === 'Flor' && linea.color ? ` <small class="texto-suave">(${escapar(linea.color.toLowerCase())} en la foto)</small>` : ''}</td>
                <td class="numero">${linea.cantidad}</td>
                <td class="numero">${formatearmonto(linea.precio)}</td>
                <td class="numero">${formatearmonto(linea.subtotal)}</td>
            </tr>`).join('');
        const notas = datos.notas.length
            ? `<ul class="notas-valoracion">${datos.notas.map((nota) => `<li>${escapar(nota)}</li>`).join('')}</ul>` : '';

        destino.innerHTML = datos.lineas.length ? `
            <div class="valoracion">
                <div class="total-valoracion">
                    <span>Costaría aproximadamente</span>
                    <strong>${formatearmonto(datos.total)}</strong>
                    ${espropietario ? `<small class="texto-suave">Costo ${formatearmonto(datos.costo)} · Ganancia ${formatearmonto(datos.ganancia)}</small>` : ''}
                </div>
                <div class="tabla-contenedor">
                    <table class="tabla tabla-valoracion">
                        <thead><tr><th>Producto</th><th class="numero">Cant.</th><th class="numero">Precio</th><th class="numero">Subtotal</th></tr></thead>
                        <tbody>${filas}</tbody>
                    </table>
                </div>
                ${notas}
                <p class="nota">Es un aproximado según los colores de la foto y el tamaño elegido (${datos.porcentaje}% de ganancia).
                    En la solicitud puede cambiar las flores y las cantidades.</p>
                <button type="button" class="boton boton-principal" data-crear>Crear solicitud con esta valoración</button>
            </div>`
            : `${notas}<p class="nota">No se pudo armar el ramo con el inventario actual.</p>`;
    }

    async function calcular() {
        clearTimeout(espera);
        if (!foto && !referencia) {
            return;
        }
        const numero = ++pedido;
        const destino = caja.querySelector('#resultadovaloracion');
        destino.innerHTML = '<p class="texto-suave">Calculando…</p>';

        const datos = new FormData();
        datos.append('flores', flores);
        datos.append('porcentaje', porcentaje);
        if (referencia) {
            datos.append('referencia', referencia);
        } else {
            datos.append('foto', foto, 'foto.jpg');
        }
        let respuesta = await enviar('valorar', datos);

        // Si la foto guardada ya no está, se sube otra vez
        if (!respuesta.exito && referencia && foto && respuesta.datos && respuesta.datos.errores && respuesta.datos.errores.foto) {
            referencia = '';
            const otra = new FormData();
            otra.append('flores', flores);
            otra.append('porcentaje', porcentaje);
            otra.append('foto', foto, 'foto.jpg');
            respuesta = await enviar('valorar', otra);
        }
        // Si mientras tanto se pidió otro cálculo, este ya no sirve
        if (numero !== pedido) {
            return;
        }
        if (!respuesta.exito) {
            valoracion = null;
            destino.innerHTML = `<p class="error">${escapar(respuesta.mensaje)}</p>
                <button type="button" class="boton" data-calcular>Volver a intentar</button>`;
            return;
        }

        valoracion = respuesta.datos;
        referencia = valoracion.referencia;
        mostrar(valoracion);
    }

    function crearsolicitud() {
        if (!valoracion) {
            return;
        }
        try {
            sessionStorage.setItem('floristica.valoracion', JSON.stringify({
                porcentaje: valoracion.porcentaje,
                referencia: valoracion.referencia,
                lineas: valoracion.lineas.map((linea) => ({
                    tbinventarioid: linea.tbinventarioid,
                    cantidad: linea.cantidad,
                    nombre: linea.nombre,
                })),
            }));
        } catch (error) {
            caja.querySelector('#resultadovaloracion').insertAdjacentHTML('afterbegin',
                '<p class="error">El navegador no dejó guardar la valoración. Revise que no esté en modo privado.</p>');
            return;
        }
        window.location.href = 'index.php?modulo=encargo&valoracion=1';
    }

    caja.addEventListener('click', (evento) => {
        const boton = evento.target.closest('button');
        if (!boton) {
            return;
        }
        if (boton.dataset.flores) {
            flores = Number(boton.dataset.flores);
            caja.querySelector('#otroflores').value = '';
            marcar();
            calcular();
        } else if (boton.dataset.porcentaje) {
            porcentaje = Number(boton.dataset.porcentaje);
            caja.querySelector('#otroporcentaje').value = '';
            marcar();
            if (valoracion) {
                calcular();
            }
        } else if (boton.hasAttribute('data-calcular')) {
            calcular();
        } else if (boton.hasAttribute('data-crear')) {
            crearsolicitud();
        }
    });

    // "Otro": se calcula un momento después de que deja de escribir
    caja.addEventListener('input', (evento) => {
        const campo = evento.target;
        campo.value = campo.value.replace(/\D/g, '').slice(0, 3);
        const valor = Number(campo.value);
        if (campo.id === 'otroflores' && valor >= 1 && valor <= 100) {
            flores = valor;
        } else if (campo.id === 'otroporcentaje' && campo.value !== '' && valor <= 500) {
            porcentaje = valor;
        } else {
            return;
        }
        marcar();
        clearTimeout(espera);
        espera = setTimeout(calcular, 600);
    });

    // Lo avisa buscarfoto.js: con la foto y el parecido del mejor resultado, o null al empezar otra vez
    document.addEventListener('fotobuscada', (evento) => {
        clearTimeout(espera);
        pedido++;
        valoracion = null;
        referencia = '';
        foto = evento.detail ? evento.detail.foto : null;
        if (!foto) {
            caja.remove();
            return;
        }
        // Si ninguno se parece mucho, la valoración va primero y resaltada; si no, al final
        const destacado = evento.detail.mejor < PARECIDOALTO;
        dibujar(destacado);
        if (destacado) {
            resultados.prepend(caja);
        } else {
            resultados.append(caja);
        }
    });
})();
