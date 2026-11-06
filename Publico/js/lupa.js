// Lupa para las fotos: sirve para cualquier <img data-lupa>, también las que se dibujan después.
// - Con el mouse encima, la foto se ve más grande en un cuadro junto al puntero.
// - Con un clic (o tocándola en el celular), se abre en grande en toda la pantalla.
(() => {
    const ANCHO = 340;
    const MARGEN = 16;
    let lente = null;
    let visor = null;
    let actual = null;

    function crearlente() {
        lente = document.createElement('div');
        lente.className = 'lupa-lente';
        lente.setAttribute('aria-hidden', 'true');
        lente.innerHTML = '<img alt="">';
        return lente;
    }

    function crearvisor() {
        visor = document.createElement('dialog');
        visor.className = 'lupa-visor';
        visor.setAttribute('aria-label', 'Foto ampliada');
        visor.innerHTML = `
            <img alt="">
            <p class="lupa-pie"></p>
            <button type="button" class="lupa-cerrar" aria-label="Cerrar">×</button>`;
        // Se cierra con clic en cualquier parte o con Esc
        visor.addEventListener('click', () => visor.close());
        document.body.appendChild(visor);
        return visor;
    }

    function mostrarlente(imagen) {
        if (!imagen.currentSrc && !imagen.src) {
            return;
        }
        actual = imagen;
        const caja = lente || crearlente();
        // Dentro de una ventana abierta (por ejemplo "Buscar por foto"), la lente va dentro de ella
        // para que se vea por encima
        const contenedor = imagen.closest('dialog[open]') || document.body;
        if (caja.parentNode !== contenedor) {
            contenedor.appendChild(caja);
        }
        const foto = caja.querySelector('img');
        foto.src = imagen.currentSrc || imagen.src;
        const proporcion = imagen.naturalWidth && imagen.naturalHeight ? imagen.naturalHeight / imagen.naturalWidth : 0.75;
        const ancho = Math.min(ANCHO, window.innerWidth - 2 * MARGEN);
        caja.style.width = `${ancho}px`;
        caja.style.height = `${Math.min(Math.round(ancho * proporcion), window.innerHeight - 2 * MARGEN)}px`;
        caja.hidden = false;
    }

    function moverlente(evento) {
        if (!lente || lente.hidden) {
            return;
        }
        const ancho = lente.offsetWidth;
        const alto = lente.offsetHeight;
        // A la derecha del puntero; si no cabe, a la izquierda. Siempre dentro de la pantalla.
        let x = evento.clientX + MARGEN;
        if (x + ancho > window.innerWidth - MARGEN) {
            x = evento.clientX - MARGEN - ancho;
        }
        let y = evento.clientY - alto / 2;
        x = Math.max(MARGEN, x);
        y = Math.max(MARGEN, Math.min(y, window.innerHeight - alto - MARGEN));
        lente.style.left = `${Math.round(x)}px`;
        lente.style.top = `${Math.round(y)}px`;
    }

    function ocultarlente() {
        actual = null;
        if (lente) {
            lente.hidden = true;
        }
    }

    function ampliar(imagen) {
        ocultarlente();
        const ventana = visor || crearvisor();
        ventana.querySelector('img').src = imagen.currentSrc || imagen.src;
        ventana.querySelector('img').alt = imagen.alt || 'Foto ampliada';
        ventana.querySelector('.lupa-pie').textContent = imagen.alt || '';
        if (!ventana.open) {
            ventana.showModal();
        }
    }

    // Solo con mouse (en pantallas táctiles no hay "pasar por encima")
    document.addEventListener('pointermove', (evento) => {
        if (evento.pointerType !== 'mouse') {
            return;
        }
        const imagen = evento.target.closest && evento.target.closest('img[data-lupa]');
        if (imagen && imagen !== actual) {
            mostrarlente(imagen);
        }
        if (actual) {
            moverlente(evento);
        }
    });
    document.addEventListener('pointerout', (evento) => {
        const imagen = evento.target.closest && evento.target.closest('img[data-lupa]');
        if (imagen && imagen === actual && !imagen.contains(evento.relatedTarget)) {
            ocultarlente();
        }
    });
    document.addEventListener('click', (evento) => {
        const imagen = evento.target.closest && evento.target.closest('img[data-lupa]');
        if (imagen && imagen.getAttribute('src')) {
            evento.preventDefault();
            ampliar(imagen);
        }
    });
    // Si se cierra o se desplaza la ventana, la lente se esconde
    document.addEventListener('scroll', ocultarlente, true);
    document.addEventListener('close', ocultarlente, true);
})();
