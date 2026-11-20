const fetchoriginal = window.fetch.bind(window);
window.fetch = async (...argumentos) => {
    const respuesta = await fetchoriginal(...argumentos);
    if (respuesta.status === 401) {
        window.location.href = 'index.php';
    }
    return respuesta;
};
const botonmenu = document.getElementById('botonmenu');
const botoncerrarmenu = document.getElementById('botoncerrarmenu');
const menulateral = document.getElementById('menulateral');
const fondomenu = document.getElementById('fondomenu');
const gruposmenu = menulateral.querySelectorAll('.menu-grupo');

function abrirmenu() {
    menulateral.classList.add('abierto');
    fondomenu.hidden = false;
    botonmenu.setAttribute('aria-expanded', 'true');
    botoncerrarmenu.focus();
}

function cerrarmenu() {
    menulateral.classList.remove('abierto');
    fondomenu.hidden = true;
    botonmenu.setAttribute('aria-expanded', 'false');
    cerrargrupos(menulateral);
    botonmenu.focus();
}

function alternargrupo(grupo, abrir) {
    grupo.classList.toggle('abierto', abrir);
    grupo.querySelector(':scope > .menu-grupo-boton').setAttribute('aria-expanded', String(abrir));

    if (!abrir) {
        cerrargrupos(grupo);
    }
}

function cerrargrupos(contenedor) {
    contenedor.querySelectorAll('.menu-grupo.abierto').forEach((grupo) => {
        grupo.classList.remove('abierto');
        grupo.querySelector(':scope > .menu-grupo-boton').setAttribute('aria-expanded', 'false');
    });
}

const conmouse = window.matchMedia('(hover: hover) and (min-width: 768px)');

gruposmenu.forEach((grupo) => {
    const boton = grupo.querySelector(':scope > .menu-grupo-boton');

    boton.addEventListener('click', () => {
        const abrir = !grupo.classList.contains('abierto');

        grupo.parentElement.querySelectorAll(':scope > .menu-grupo.abierto').forEach((hermano) => {
            if (hermano !== grupo) {
                alternargrupo(hermano, false);
            }
        });

        alternargrupo(grupo, abrir);
    });

    grupo.addEventListener('mouseenter', () => {
        if (conmouse.matches) {
            grupo.parentElement.querySelectorAll(':scope > .menu-grupo.abierto').forEach((hermano) => {
                if (hermano !== grupo) {
                    alternargrupo(hermano, false);
                }
            });
            alternargrupo(grupo, true);
        }
    });

    grupo.addEventListener('mouseleave', () => {
        if (conmouse.matches) {
            alternargrupo(grupo, false);
        }
    });
});

botonmenu.addEventListener('click', abrirmenu);
botoncerrarmenu.addEventListener('click', cerrarmenu);
fondomenu.addEventListener('click', cerrarmenu);

document.addEventListener('keydown', (evento) => {
    if (evento.key !== 'Escape' || !menulateral.classList.contains('abierto')) {
        return;
    }

    const abiertos = menulateral.querySelectorAll('.menu-grupo.abierto');
    if (abiertos.length > 0) {
        const ultimo = abiertos[abiertos.length - 1];
        alternargrupo(ultimo, false);
        ultimo.querySelector(':scope > .menu-grupo-boton').focus();
    } else {
        cerrarmenu();
    }
});

document.querySelectorAll('.contenedor > .tabla-contenedor .tabla').forEach((tabla) => {
    const titulos = Array.from(tabla.querySelectorAll('thead th')).map((th) => th.textContent.trim());
    const cuerpo = tabla.tBodies[0];

    if (!cuerpo) {
        return;
    }

    const etiquetar = () => {
        Array.from(cuerpo.rows).forEach((fila) => {
            Array.from(fila.cells).forEach((celda) => {
                if (titulos[celda.cellIndex] && !celda.dataset.etiqueta) {
                    celda.dataset.etiqueta = titulos[celda.cellIndex];
                }
            });
        });
    };

    etiquetar();
    new MutationObserver(etiquetar).observe(cuerpo, { childList: true });
});

function filtrosdesdeurl(filtros) {
    const parametros = new URLSearchParams(window.location.search);

    Object.entries(filtros).forEach(([nombre, select]) => {
        const valor = parametros.get(nombre);
        if (valor !== null && Array.from(select.options).some((opcion) => opcion.value === valor)) {
            select.value = valor;
        }

        if (!select.dataset.enurl) {
            select.dataset.enurl = '1';
            select.addEventListener('change', () => {
                const actuales = new URLSearchParams(window.location.search);
                if (select.value === '') {
                    actuales.delete(nombre);
                } else {
                    actuales.set(nombre, select.value);
                }
                window.history.replaceState(null, '', `${window.location.pathname}?${actuales}`);
            });
        }
    });
}

function abrirformulariosipide(abrirnuevo) {
    const parametros = new URLSearchParams(window.location.search);

    if (parametros.get('nuevo') !== '1') {
        return;
    }

    parametros.delete('nuevo');
    window.history.replaceState(null, '', `${window.location.pathname}?${parametros}`);
    abrirnuevo();
}

const dialogoclave = document.getElementById('dialogoclave');
const formularioclave = document.getElementById('formularioclave');
const avisoclave = document.getElementById('avisoclave');
const botonguardarclave = document.getElementById('botonguardarclave');

function limpiarerroresclave() {
    formularioclave.querySelectorAll('[data-error]').forEach((error) => {
        error.textContent = '';
    });
    formularioclave.querySelectorAll('[aria-invalid]').forEach((campo) => campo.removeAttribute('aria-invalid'));
    avisoclave.hidden = true;
}

function mostraravisoclave(mensaje, exito) {
    avisoclave.textContent = mensaje;
    avisoclave.className = `aviso ${exito ? 'aviso-exito' : 'aviso-error'}`;
    avisoclave.hidden = false;
}

document.getElementById('botoncambiarclave').addEventListener('click', () => {
    cerrarmenu();
    formularioclave.reset();
    limpiarerroresclave();
    botonguardarclave.disabled = false;
    dialogoclave.showModal();
    formularioclave.elements.claveactual.focus();
});

document.getElementById('botoncancelarclave').addEventListener('click', () => dialogoclave.close());

formularioclave.addEventListener('input', (evento) => {
    const error = formularioclave.querySelector(`[data-error="${evento.target.name}"]`);
    if (error) {
        error.textContent = '';
    }
    evento.target.removeAttribute('aria-invalid');
});

formularioclave.addEventListener('submit', async (evento) => {
    evento.preventDefault();
    limpiarerroresclave();
    botonguardarclave.disabled = true;

    let respuesta;
    try {
        const peticion = await fetch('index.php?modulo=cuenta&accion=cambiarclave', {
            method: 'POST',
            body: new FormData(formularioclave),
        });
        respuesta = await peticion.json();
    } catch (error) {
        respuesta = { exito: false, mensaje: 'No se pudo conectar con el servidor. Intente de nuevo.', datos: {} };
    }

    botonguardarclave.disabled = false;

    if (respuesta.exito) {
        formularioclave.reset();
        mostraravisoclave(respuesta.mensaje, true);
        setTimeout(() => dialogoclave.close(), 1800);
        return;
    }

    const errores = respuesta.datos && respuesta.datos.errores ? respuesta.datos.errores : {};
    Object.entries(errores).forEach(([nombre, mensaje]) => {
        const error = formularioclave.querySelector(`[data-error="${nombre}"]`);
        if (error) {
            error.textContent = mensaje;
            formularioclave.elements[nombre].setAttribute('aria-invalid', 'true');
        }
    });

    mostraravisoclave(respuesta.mensaje, false);
    const primero = formularioclave.querySelector('[aria-invalid="true"]');
    if (primero) {
        primero.focus();
    }
});

function paginar(lista, contenedor, esregistro, minimofilas) {
    const controles = document.createElement('nav');
    controles.className = 'paginacion';
    controles.setAttribute('aria-label', 'Páginas de la lista');
    controles.hidden = true;
    contenedor.after(controles);

    let pagina = 1;

    const registros = () => Array.from(lista.children).filter(esregistro);

    function porpagina(todos) {
        todos.forEach((registro) => {
            registro.hidden = false;
        });

        const cajas = todos.map((registro) => registro.getBoundingClientRect());
        const porfila = cajas.filter((caja) => Math.abs(caja.top - cajas[0].top) < 2).length || 1;
        const espacio = parseFloat(getComputedStyle(lista).rowGap) || 0;
        const alto = Math.max(...todos.map((registro, indice) =>
            cajas[indice].height + (parseFloat(getComputedStyle(registro).marginBottom) || 0))) + espacio;

        const tabla = lista.closest('table');
        const encabezado = tabla && tabla.tHead ? tabla.tHead.getBoundingClientRect().height : 0;
        const inicio = contenedor.getBoundingClientRect().top + window.scrollY + encabezado;
        const relleno = parseFloat(getComputedStyle(contenedor.closest('main')).paddingBottom) || 0;
        const disponible = window.innerHeight - inicio - relleno - 68;

        const filas = Math.max(minimofilas, Math.floor((disponible + espacio) / alto));
        return { cantidad: filas * porfila, porfila, minimo: minimofilas * porfila };
    }

    function sobrepasa() {
        return document.documentElement.scrollHeight > window.innerHeight + 1;
    }

    function boton(texto, destino, etiqueta, actual = false) {
        return `<button type="button" class="pagina${actual ? ' pagina-actual' : ''}" data-pagina="${destino}"`
            + ` aria-label="${etiqueta}"${actual ? ' aria-current="page"' : ''}>${texto}</button>`;
    }

    function numeros(total) {
        const salida = [];
        for (let numero = 1; numero <= total; numero++) {
            if (numero === 1 || numero === total || Math.abs(numero - pagina) <= 1) {
                salida.push(numero);
            } else if (salida[salida.length - 1] !== '…') {
                salida.push('…');
            }
        }
        return salida;
    }
    function pintarcontroles(total, desde, hasta, cantidadtotal) {

        const botones = numeros(total).map((numero) => (numero === '…'
            ? '<span class="pagina-puntos" aria-hidden="true">…</span>'
            : boton(numero, numero, `Página ${numero}`, numero === pagina)));

        controles.innerHTML = `
            <span class="paginacion-resumen">${desde + 1}–${hasta} de ${cantidadtotal}</span>
            <div class="paginacion-botones">
                ${boton('‹', pagina - 1, 'Página anterior')}
                ${botones.join('')}
                ${boton('›', pagina + 1, 'Página siguiente')}
            </div>`;
        controles.querySelector('[aria-label="Página anterior"]').disabled = pagina === 1;
        controles.querySelector('[aria-label="Página siguiente"]').disabled = pagina === total;
        controles.hidden = false;
    }

    function dibujar() {
        const todos = registros();

        if (todos.length === 0) {
            controles.hidden = true;
            return;
        }

        quitarestirado();
        const medida = porpagina(todos);
        const tope = Math.ceil(todos.length / medida.porfila) * medida.porfila;
        let cantidad = Math.min(Math.max(medida.cantidad, medida.minimo), tope);

        const probar = (prueba) => {
            todos.forEach((registro, indice) => {
                registro.hidden = indice >= prueba;
            });
            pintarcontroles(Math.ceil(todos.length / prueba), 0, Math.min(prueba, todos.length), todos.length);
            return !sobrepasa();
        };

        if (probar(cantidad)) {
            while (cantidad < tope && probar(cantidad + medida.porfila)) {
                cantidad += medida.porfila;
            }
        }

        const mostrarpagina = (numero, tamano) => {
            const total = Math.ceil(todos.length / tamano);
            const desde = (numero - 1) * tamano;
            const hasta = Math.min(desde + tamano, todos.length);

            todos.forEach((registro, indice) => {
                registro.hidden = indice < desde || indice >= hasta;
            });
            pintarcontroles(total, desde, hasta, todos.length);
        };

        const cabentodas = (tamano) => {
            const total = Math.ceil(todos.length / tamano);
            for (let numero = 1; numero <= total; numero++) {
                mostrarpagina(numero, tamano);
                if (sobrepasa()) {
                    return false;
                }
            }
            return true;
        };

        while (cantidad - medida.porfila >= medida.minimo && !cabentodas(cantidad)) {
            cantidad -= medida.porfila;
        }

        const total = Math.ceil(todos.length / cantidad);
        pagina = Math.min(Math.max(1, pagina), total);
        mostrarpagina(pagina, cantidad);

        estirarfotos(medida.porfila);
    }

    function quitarestirado() {
        lista.querySelectorAll('img[data-estirada]').forEach((foto) => {
            foto.style.height = '';
            delete foto.dataset.estirada;
        });
    }

    function estirarfotos(porfila) {
        if (!lista.classList.contains('galeria')) {
            return;
        }

        const visibles = registros().filter((registro) => !registro.hidden);
        const relleno = parseFloat(getComputedStyle(lista.closest('main')).paddingBottom) || 0;
        const sobra = window.innerHeight - (controles.getBoundingClientRect().bottom + relleno) - 4;
        const filas = Math.ceil(visibles.length / porfila);

        if (sobra < 8 || filas === 0) {
            return;
        }

        visibles.forEach((tarjeta) => {
            const foto = tarjeta.querySelector('img');
            if (!foto) {
                return;
            }
            const caja = foto.getBoundingClientRect();
            const alto = Math.min(caja.height + sobra / filas, caja.width * 1.15);
            if (alto > caja.height) {
                foto.style.height = `${Math.floor(alto)}px`;
                foto.dataset.estirada = '1';
            }
        });

        if (sobrepasa()) {
            quitarestirado();
        }
    }

    controles.addEventListener('click', (evento) => {
        const destino = evento.target.closest('[data-pagina]');
        if (destino && !destino.disabled) {
            pagina = Number(destino.dataset.pagina);
            dibujar();
        }
    });

    new MutationObserver(dibujar).observe(lista, { childList: true });

    const filtros = document.querySelector('.filtros');
    if (filtros) {
        ['input', 'change'].forEach((tipo) => filtros.addEventListener(tipo, () => {
            pagina = 1;
        }));
    }

    let temporizador;
    window.addEventListener('resize', () => {
        clearTimeout(temporizador);
        temporizador = setTimeout(dibujar, 150);
    });

    dibujar();
}

document.querySelectorAll('.contenedor > .tabla-contenedor .tabla').forEach((tabla) => {
    if (tabla.tBodies[0]) {
        paginar(tabla.tBodies[0], tabla.closest('.tabla-contenedor'), (fila) => !fila.querySelector('.vacio'), 3);
    }
});

const galeriapaginada = document.querySelector('.contenedor > .galeria');
if (galeriapaginada) {
    paginar(galeriapaginada, galeriapaginada, (tarjeta) => !tarjeta.classList.contains('vacio-galeria'), 1);
}

(() => {
    const cerrarexistente = '.cerrar-modal-inventario, .cerrar-modal-encargo, .cerrar-dialogo';

    function visible(boton) {
        return !boton.disabled && !boton.hidden && boton.getClientRects().length > 0;
    }

    function cerrardialogo(dialogo) {
        const cancelar = [...dialogo.querySelectorAll('button')].find((boton) =>
            !boton.dataset.accion
            && ['Cancelar', 'Cerrar'].includes(boton.textContent.trim())
            && boton.closest('dialog') === dialogo
            && visible(boton));

        if (cancelar) {
            cancelar.click();
            return;
        }

        if (dialogo.dispatchEvent(new Event('cancel', { cancelable: true }))) {
            dialogo.close();
        }
    }

    function agregarcerrar(dialogo) {
        const yatiene = dialogo.querySelector(cerrarexistente)
            || [...dialogo.querySelectorAll('button')].some((boton) =>
                boton.textContent.trim() === '×' && boton.closest('dialog') === dialogo);
        if (yatiene) {
            return;
        }

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'cerrar-dialogo';
        boton.setAttribute('aria-label', 'Cerrar');
        boton.textContent = '×';
        boton.addEventListener('click', () => cerrardialogo(dialogo));
        dialogo.prepend(boton);
    }

    ['showModal', 'show'].forEach((metodo) => {
        const original = HTMLDialogElement.prototype[metodo];
        HTMLDialogElement.prototype[metodo] = function (...argumentos) {
            agregarcerrar(this);
            return original.apply(this, argumentos);
        };
    });

    document.querySelectorAll('dialog').forEach(agregarcerrar);
})();