// Si la sesión terminó (por ejemplo, la dueña inactivó la cuenta), vuelve al login
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

// Abre o cierra un submenú
function alternargrupo(grupo, abrir) {
    grupo.classList.toggle('abierto', abrir);
    grupo.querySelector(':scope > .menu-grupo-boton').setAttribute('aria-expanded', String(abrir));

    if (!abrir) {
        cerrargrupos(grupo);
    }
}

// Cierra todos los submenús dentro de un elemento
function cerrargrupos(contenedor) {
    contenedor.querySelectorAll('.menu-grupo.abierto').forEach((grupo) => {
        grupo.classList.remove('abierto');
        grupo.querySelector(':scope > .menu-grupo-boton').setAttribute('aria-expanded', 'false');
    });
}

// Con mouse: el submenú se abre al pasar por encima (solo en pantallas grandes)
const conmouse = window.matchMedia('(hover: hover) and (min-width: 768px)');

gruposmenu.forEach((grupo) => {
    const boton = grupo.querySelector(':scope > .menu-grupo-boton');

    boton.addEventListener('click', () => {
        const abrir = !grupo.classList.contains('abierto');

        // Al abrir uno, se cierran sus hermanos
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

    // Escape cierra primero el submenú abierto más profundo; si no hay, cierra el menú
    const abiertos = menulateral.querySelectorAll('.menu-grupo.abierto');
    if (abiertos.length > 0) {
        const ultimo = abiertos[abiertos.length - 1];
        alternargrupo(ultimo, false);
        ultimo.querySelector(':scope > .menu-grupo-boton').focus();
    } else {
        cerrarmenu();
    }
});

// "Agregar nuevo ..." del menú: abre el formulario de la página automáticamente
function abrirformulariosipide(abrirnuevo) {
    const parametros = new URLSearchParams(window.location.search);

    if (parametros.get('nuevo') !== '1') {
        return;
    }

    parametros.delete('nuevo');
    window.history.replaceState(null, '', `${window.location.pathname}?${parametros}`);
    abrirnuevo();
}