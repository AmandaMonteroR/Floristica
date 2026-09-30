const botonmenu = document.getElementById('botonmenu');
const botoncerrarmenu = document.getElementById('botoncerrarmenu');
const menulateral = document.getElementById('menulateral');
const fondomenu = document.getElementById('fondomenu');

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
    botonmenu.focus();
}

botonmenu.addEventListener('click', abrirmenu);
botoncerrarmenu.addEventListener('click', cerrarmenu);
fondomenu.addEventListener('click', cerrarmenu);

document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && menulateral.classList.contains('abierto')) {
        cerrarmenu();
    }
});