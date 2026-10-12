const ventanahistorial = (() => {
    const dialogo = document.getElementById('dialogohistorial');
    const titulo = document.getElementById('titulohistorial');
    const cuerpo = document.getElementById('cuerpohistorial');

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

    function valorhistorial(valor) {
        return valor === null ? '<span class="texto-suave">Registro inicial</span>' : escaparhistorial(valor);
    }

    async function mostrar(modulo, id, nombre) {
        let respuesta;
        try {
            const resultado = await fetch(`index.php?modulo=${modulo}&accion=historial&id=${encodeURIComponent(id)}`);
            respuesta = await resultado.json();
        } catch (error) {
            respuesta = { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
        }

        titulo.textContent = `Historial de ${nombre}`;

        if (!respuesta.exito) {
            cuerpo.innerHTML = `<tr><td colspan="5" class="vacio">${escaparhistorial(respuesta.mensaje)}</td></tr>`;
        } else if (respuesta.datos.length === 0) {
            cuerpo.innerHTML = '<tr><td colspan="5" class="vacio">Todavía no hay cambios registrados.</td></tr>';
        } else {
            cuerpo.innerHTML = respuesta.datos.map((cambio) => `
                <tr>
                    <td>${fechahistorial(cambio.fecha)}</td>
                    <td>${escaparhistorial(cambio.etiqueta)}</td>
                    <td>${valorhistorial(cambio.anterior)}</td>
                    <td>${valorhistorial(cambio.nuevo)}</td>
                    <td>${cambio.hechopor ? escaparhistorial(cambio.hechopor) : '<span class="texto-suave">No registrado</span>'}</td>
                </tr>`).join('');
        }

        dialogo.showModal();
    }

    document.getElementById('botoncerrarhistorial').addEventListener('click', () => dialogo.close());

    return { mostrar };
})();