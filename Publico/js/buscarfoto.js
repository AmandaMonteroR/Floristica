// Catálogo → "Buscar por foto": se sube la foto que mandó la clienta y el sistema muestra
// los arreglos del catálogo más parecidos (por colores y forma), con su precio.
// Usa verdetalle(), escapar() y formatearmonto() de arreglofloral.js.
// Cuando termina, avisa con el evento "fotobuscada" para que valorarramo.js ofrezca valorar el ramo.
(() => {
    const dialogo = document.getElementById('dialogobuscarfoto');
    const campo = document.getElementById('campobuscarfoto');
    const zona = document.getElementById('zonabuscarfoto');
    const vista = document.getElementById('fotobuscada');
    const botonotra = document.getElementById('botonotrafoto');
    const colores = document.getElementById('coloresbuscada');
    const error = document.getElementById('errorbuscarfoto');
    const resultados = document.getElementById('resultadosfoto');
    const LADOMAXIMO = 800;

    // Achica la foto en el navegador antes de subirla (las del celular pesan mucho)
    function achicar(archivo) {
        return new Promise((resolver, rechazar) => {
            const imagen = new Image();
            imagen.onload = () => {
                const escala = Math.min(1, LADOMAXIMO / Math.max(imagen.width, imagen.height));
                const lienzo = document.createElement('canvas');
                lienzo.width = Math.round(imagen.width * escala);
                lienzo.height = Math.round(imagen.height * escala);
                lienzo.getContext('2d').drawImage(imagen, 0, 0, lienzo.width, lienzo.height);
                URL.revokeObjectURL(imagen.src);
                lienzo.toBlob((blob) => (blob ? resolver(blob) : rechazar(new Error('sin imagen'))), 'image/jpeg', 0.85);
            };
            imagen.onerror = () => rechazar(new Error('no es imagen'));
            imagen.src = URL.createObjectURL(archivo);
        });
    }

    function nivel(parecido) {
        if (parecido >= 75) {
            return ['Muy parecido', 'nivel-alto'];
        }
        if (parecido >= 65) {
            return ['Parecido', 'nivel-medio'];
        }
        return ['Poco parecido', 'nivel-bajo'];
    }

    function chips(lista) {
        return lista.map((color) => `<span class="chip-color">${escapar(color.nombre)} ${color.porcentaje}%</span>`).join('');
    }

    function tarjeta(arreglo) {
        const [texto, clase] = nivel(arreglo.parecido);
        const precio = arreglo.altaencendida
            ? `${formatearmonto(arreglo.precioactual)} <small class="texto-suave">(temporada alta)</small>`
            : formatearmonto(arreglo.precioactual);

        return `
            <article class="resultado-foto">
                <img src="${escapar(arreglo.tbarreglofloralfoto)}" alt="${escapar(arreglo.tbarreglofloralnombre)}" loading="lazy" data-lupa>
                <div class="resultado-cuerpo">
                    <h3>${escapar(arreglo.tbarreglofloralnombre)}</h3>
                    <div class="barra-parecido ${clase}" role="meter" aria-valuemin="0" aria-valuemax="100"
                         aria-valuenow="${Math.round(arreglo.parecido)}" aria-label="Parecido">
                        <span style="width: ${Math.max(4, Math.min(100, arreglo.parecido))}%"></span>
                    </div>
                    <p class="texto-parecido"><strong>${Math.round(arreglo.parecido)}%</strong> · ${texto}</p>
                    <p class="precio-resultado">${precio}</p>
                    <div class="tarjeta-acciones">
                        <button type="button" class="enlace" data-ver="${arreglo.tbarreglofloralid}">Ver</button>
                        <a class="enlace" data-accion="editar" href="index.php?modulo=encargo&amp;arreglo=${arreglo.tbarreglofloralid}">Crear solicitud</a>
                    </div>
                </div>
            </article>`;
    }

    async function buscar() {
        const archivo = campo.files[0];
        campo.value = '';
        if (!archivo) {
            return;
        }

        error.textContent = '';
        colores.innerHTML = '';
        let foto;
        try {
            foto = await achicar(archivo);
        } catch (excepcion) {
            error.textContent = 'Ese archivo no es una imagen. Elija una foto JPG, PNG o WEBP.';
            return;
        }

        vista.src = URL.createObjectURL(foto);
        vista.hidden = false;
        zona.hidden = true;
        botonotra.hidden = false;
        resultados.innerHTML = '<p class="vacio-galeria">Comparando con las fotos del catálogo…</p>';

        const datos = new FormData();
        datos.append('foto', foto, 'foto.jpg');
        const respuesta = await enviar('buscarporfoto', datos);

        if (!respuesta.exito) {
            error.textContent = respuesta.mensaje;
            resultados.innerHTML = '';
            return;
        }

        const encontrados = respuesta.datos.resultados;
        colores.innerHTML = respuesta.datos.colores.length
            ? `<span class="texto-suave">Colores de la foto:</span> ${chips(respuesta.datos.colores)}` : '';
        resultados.innerHTML = encontrados.length
            ? `<p class="texto-suave resumen-foto">Se compararon ${respuesta.datos.revisados} arreglos. Los más parecidos:</p>
               <div class="lista-resultados">${encontrados.map(tarjeta).join('')}</div>`
            : '<p class="vacio-galeria">No hay arreglos activos con foto para comparar.</p>';

        document.dispatchEvent(new CustomEvent('fotobuscada', {
            detail: { foto, mejor: encontrados.length ? encontrados[0].parecido : 0 },
        }));
    }

    function abrir() {
        vista.hidden = true;
        vista.removeAttribute('src');
        zona.hidden = false;
        botonotra.hidden = true;
        colores.innerHTML = '';
        error.textContent = '';
        resultados.innerHTML = '';
        document.dispatchEvent(new CustomEvent('fotobuscada', { detail: null }));
        dialogo.showModal();
    }

    document.getElementById('botonbuscarfoto').addEventListener('click', abrir);
    document.getElementById('botoncerrarbuscarfoto').addEventListener('click', () => dialogo.close());
    botonotra.addEventListener('click', () => campo.click());
    campo.addEventListener('change', buscar);

    resultados.addEventListener('click', (evento) => {
        const boton = evento.target.closest('[data-ver]');
        if (boton) {
            verdetalle(boton.dataset.ver);
        }
    });

    // Arrastrar la foto sobre el cuadro
    zona.addEventListener('dragover', (evento) => {
        evento.preventDefault();
        zona.classList.add('zona-activa');
    });
    zona.addEventListener('dragleave', () => zona.classList.remove('zona-activa'));
    zona.addEventListener('drop', (evento) => {
        evento.preventDefault();
        zona.classList.remove('zona-activa');
        if (evento.dataTransfer.files.length) {
            campo.files = evento.dataTransfer.files;
            buscar();
        }
    });
})();
