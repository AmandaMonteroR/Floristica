// Ventana "Historial" que comparten los módulos (proveedores, inventario, catálogo, clientes...).
// La vista debe tener el <dialog id="dialogohistorial"> con su tabla.
// - El registro inicial (todos los datos con que se creó) sale en una sola fila.
// - Cada cambio se ve como "antes → ahora".
// - Arriba hay un filtro para ver solo los cambios de un dato (Teléfono, Estado...).
const ventanahistorial = (() => {
    const dialogo = document.getElementById('dialogohistorial');
    const titulo = document.getElementById('titulohistorial');
    const cuerpo = document.getElementById('cuerpohistorial');
    const tabla = cuerpo.closest('table');
    const nota = dialogo.querySelector('.nota');

    let cambios = [];
    let formatearvalor = (campo, valor) => valor;

    // Columnas: el cambio va en una sola columna
    tabla.querySelector('thead').innerHTML = '<tr><th>Fecha</th><th>Dato</th><th>Cambio</th><th>Hecho por</th></tr>';
    tabla.classList.add('tabla-historial');
    if (nota) {
        nota.textContent = 'Del más antiguo al más reciente. Elija un dato para ver solo sus cambios.';
    }

    // Filtro por dato, encima de la tabla
    const barra = document.createElement('div');
    barra.className = 'filtro-historial';
    barra.innerHTML = `
        <label class="campo-filtro-historial">
            <span>Ver</span>
            <select id="filtrohistorial" aria-label="Dato del historial"></select>
        </label>
        <span class="texto-suave" id="resumenhistorial" aria-live="polite"></span>`;
    tabla.closest('.tabla-contenedor').before(barra);
    const filtro = barra.querySelector('#filtrohistorial');
    const resumen = barra.querySelector('#resumenhistorial');

    function escaparhistorial(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    // "2026-10-11 15:25:00" -> "11/10/2026 3:25 p. m."
    function fechahistorial(fechahora) {
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

    // Estados con color; los demás valores como texto
    const estados = {
        Activo: 'estado-activo', Activa: 'estado-activo', Encendida: 'estado-vigente',
        Inactivo: 'estado-inactivo', Inactiva: 'estado-inactivo', Apagada: 'estado-inactivo',
    };

    function valor(cambio, texto) {
        if (texto === null || texto === undefined || texto === '') {
            return '<span class="texto-suave">Sin registrar</span>';
        }
        const formateado = String(formatearvalor(cambio.campo, texto));
        return estados[formateado]
            ? `<span class="estado ${estados[formateado]}">${escaparhistorial(formateado)}</span>`
            : escaparhistorial(formateado);
    }

    function hechopor(cambio) {
        return cambio.hechopor ? escaparhistorial(cambio.hechopor) : '<span class="texto-suave">No registrado</span>';
    }

    // Une en una sola fila los datos iniciales que se guardaron al mismo tiempo
    function agrupar(lista) {
        const filas = [];
        lista.forEach((cambio) => {
            const ultima = filas[filas.length - 1];
            if (cambio.anterior === null && ultima && ultima.inicial
                && ultima.fecha.slice(0, 16) === String(cambio.fecha).slice(0, 16) && ultima.hechopor === cambio.hechopor) {
                ultima.datos.push(cambio);
            } else if (cambio.anterior === null) {
                filas.push({ inicial: true, fecha: String(cambio.fecha), hechopor: cambio.hechopor, datos: [cambio] });
            } else {
                filas.push({ inicial: false, cambio });
            }
        });
        return filas;
    }

    function filainicial(fila, primera) {
        const datos = fila.datos.map((cambio) => `
            <li><span class="dato-inicial">${escaparhistorial(cambio.etiqueta)}</span> ${valor(cambio, cambio.nuevo)}</li>`).join('');
        return `
            <tr class="fila-registro">
                <td>${fechahistorial(fila.fecha)}</td>
                <td colspan="2">
                    <span class="etiqueta-registro">${primera ? 'Se registró' : 'Se agregó'}</span>
                    <ul class="datos-iniciales">${datos}</ul>
                </td>
                <td>${hechopor(fila.datos[0])}</td>
            </tr>`;
    }

    function filacambio(cambio) {
        const contenido = cambio.anterior === null
            ? `<span class="texto-suave">Valor inicial:</span> <span class="valor-nuevo">${valor(cambio, cambio.nuevo)}</span>`
            : `<span class="valor-anterior">${valor(cambio, cambio.anterior)}</span>
               <span class="flecha-cambio" aria-label="cambió a">→</span>
               <span class="valor-nuevo">${valor(cambio, cambio.nuevo)}</span>`;
        return `
            <tr>
                <td>${fechahistorial(cambio.fecha)}</td>
                <td>${escaparhistorial(cambio.etiqueta)}</td>
                <td class="celda-cambio">${contenido}</td>
                <td>${hechopor(cambio)}</td>
            </tr>`;
    }

    function dibujar() {
        const elegido = filtro.value;
        const lista = elegido === '' ? cambios : cambios.filter((cambio) => cambio.etiqueta === elegido);
        const modificaciones = lista.filter((cambio) => cambio.anterior !== null).length;

        resumen.textContent = modificaciones === 0
            ? 'Sin cambios después del registro'
            : `${modificaciones} ${modificaciones === 1 ? 'cambio' : 'cambios'} después del registro`;

        if (lista.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="4" class="vacio">No hay cambios de este dato.</td></tr>';
            return;
        }

        if (elegido !== '') {
            cuerpo.innerHTML = lista.map(filacambio).join('');
            return;
        }

        cuerpo.innerHTML = agrupar(lista)
            .map((fila, indice) => (fila.inicial ? filainicial(fila, indice === 0) : filacambio(fila.cambio)))
            .join('');
    }

    // opciones.formatear(campo, valor): para mostrar un valor de otra forma (por ejemplo un teléfono)
    async function mostrar(modulo, id, nombre, opciones = {}) {
        formatearvalor = opciones.formatear || ((campo, texto) => texto);

        let respuesta;
        try {
            const resultado = await fetch(`index.php?modulo=${modulo}&accion=historial&id=${encodeURIComponent(id)}`);
            respuesta = await resultado.json();
        } catch (error) {
            respuesta = { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
        }

        titulo.textContent = `Historial de ${nombre}`;
        cambios = respuesta.exito ? respuesta.datos : [];

        // Los datos que aparecen, en el orden en que salen
        const etiquetas = [...new Set(cambios.map((cambio) => cambio.etiqueta))];
        filtro.innerHTML = '<option value="">Todos los datos</option>'
            + etiquetas.map((etiqueta) => `<option value="${escaparhistorial(etiqueta)}">${escaparhistorial(etiqueta)}</option>`).join('');
        filtro.value = '';
        barra.hidden = etiquetas.length < 2;

        if (!respuesta.exito) {
            barra.hidden = true;
            cuerpo.innerHTML = `<tr><td colspan="4" class="vacio">${escaparhistorial(respuesta.mensaje)}</td></tr>`;
        } else if (cambios.length === 0) {
            barra.hidden = true;
            cuerpo.innerHTML = '<tr><td colspan="4" class="vacio">Todavía no hay cambios registrados.</td></tr>';
        } else {
            dibujar();
        }

        dialogo.showModal();
    }

    filtro.addEventListener('change', dibujar);
    document.getElementById('botoncerrarhistorial').addEventListener('click', () => dialogo.close());

    return { mostrar };
})();
