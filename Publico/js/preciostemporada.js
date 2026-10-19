// Precios por temporada del inventario: lo que cobra el proveedor por el paquete en cada temporada.
// El precio de venta y el de los arreglos se calculan solos (mismo % de ganancia, redondeado a ₡100).
// - preciostemporada.dibujar(): la sección del formulario del producto.
// - preciostemporada.ventas(): muestra en cada temporada a cuánto se vendería la unidad.
// - preciostemporada.aviso(): el aviso flotante cuando hay una temporada en uso y faltan precios.
const preciostemporada = (() => {
    function escapartemporada(texto) {
        const div = document.createElement('div');
        div.textContent = texto ?? '';
        return div.innerHTML;
    }

    function montotemporada(monto) {
        return '₡' + Number(monto || 0).toLocaleString('es-CR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function decimaltemporada(valor) {
        const limpio = String(valor ?? '').replace(/,/g, '.').replace(/[^\d.]/g, '');
        const [entera, ...resto] = limpio.split('.');
        return resto.length > 0 ? `${entera}.${resto.join('').slice(0, 2)}` : entera;
    }

    // Igual que en el servidor: hacia arriba a ₡100
    function redondear(monto) {
        return Math.ceil(Math.round(monto * 100) / 100 / 100) * 100;
    }

    // Una fila por temporada: nombre, precio del paquete (vacío = el normal) y a cuánto se vendería
    function dibujar(contenedor, lista) {
        if (!lista || lista.length === 0) {
            contenedor.innerHTML = '<p class="texto-suave">No hay temporadas registradas. Puede crearlas en "Temporadas y precios".</p>';
            return;
        }

        contenedor.innerHTML = lista.map((temporada) => `
            <label class="campo precio-temporada">
                <span>${escapartemporada(temporada.tbtemporadanombre)}
                    ${temporada.vigente ? '<span class="estado estado-vigente">En uso</span>' : ''}
                    ${temporada.activa ? '' : '<span class="estado estado-inactivo">Inactiva</span>'}</span>
                <input type="text" name="temporadas[${temporada.tbtemporadaid}]" data-nombre="${escapartemporada(temporada.tbtemporadanombre)}"
                       inputmode="decimal" maxlength="11" autocomplete="off" placeholder="Precio normal"
                       value="${temporada.precio !== null ? escapartemporada(String(temporada.precio)) : ''}">
                <small class="venta-temporada" data-venta></small>
            </label>`).join('');
    }

    // Debajo de cada temporada: a cuánto se vende la unidad con ese precio del proveedor
    function ventas(contenedor, paquete, venta) {
        contenedor.querySelectorAll('.precio-temporada').forEach((etiqueta) => {
            const campo = etiqueta.querySelector('input');
            const salida = etiqueta.querySelector('[data-venta]');
            const nuevo = parseFloat(campo.value);

            if (!(nuevo > 0) || !(paquete > 0) || !(venta > 0)) {
                salida.textContent = '';
                return;
            }

            const precio = Math.abs(nuevo - paquete) < 0.01 ? venta : redondear(venta * nuevo / paquete);
            salida.textContent = `Se vende a ${montotemporada(precio)} la unidad`;
        });
    }

    // Los campos de precio solo aceptan montos
    function filtrar(campo) {
        if (campo.name && campo.name.startsWith('temporadas[')) {
            campo.value = decimaltemporada(campo.value);
            return true;
        }
        return false;
    }

    // Mensaje de error de un precio, o '' si está bien (las mismas reglas que revisa el servidor)
    function errorprecio(valor, nombre) {
        const texto = String(valor ?? '').replace(/\.$/, '');
        if (texto === '') {
            return '';
        }
        if (!/^\d{1,8}(\.\d{1,2})?$/.test(texto) || !(Number(texto) > 0)) {
            return `El precio del paquete en ${nombre} debe ser un monto mayor que 0.`;
        }
        return '';
    }

    // Revisa los precios mientras se escriben: marca en rojo los que están mal y escribe el mensaje en "salida"
    function revisar(contenedor, salida) {
        const mensajes = [];
        contenedor.querySelectorAll('input[name^="temporadas["]').forEach((campo) => {
            const mensaje = errorprecio(campo.value, campo.dataset.nombre);
            if (mensaje) {
                campo.setAttribute('aria-invalid', 'true');
                mensajes.push(mensaje);
            } else {
                campo.removeAttribute('aria-invalid');
            }
        });

        if (salida) {
            salida.textContent = mensajes.join(' ');
        }
        return mensajes.length === 0;
    }

    // Agrega los precios de temporada al FormData que se envía
    function agregar(contenedor, datos) {
        contenedor.querySelectorAll('input[name^="temporadas["]').forEach((campo) => {
            datos.append(campo.name, campo.value);
        });
    }

    // Aviso flotante: "N flores no tienen precio del proveedor para San Valentín", con opción de agregarlos ya.
    // config: { modulo, singular, plural, alguardar }
    async function aviso(config) {
        let respuesta;
        try {
            respuesta = await (await fetch(`index.php?modulo=${config.modulo}&accion=pendientestemporada`)).json();
        } catch (error) {
            return;
        }

        if (!respuesta.exito || !respuesta.datos.temporada || respuesta.datos.pendientes.length === 0) {
            return;
        }

        const temporada = respuesta.datos.temporada;
        const pendientes = respuesta.datos.pendientes;
        const clavedescartado = `floristica.avisotemporada.${config.modulo}.${temporada.tbtemporadaid}.${pendientes.length}`;

        try {
            if (sessionStorage.getItem(clavedescartado)) {
                return;
            }
        } catch (error) {
            // Sin almacenamiento: el aviso se muestra igual
        }

        const cantidad = pendientes.length === 1 ? `1 ${config.singular} no tiene` : `${pendientes.length} ${config.plural} no tienen`;
        const flotante = document.createElement('div');
        flotante.className = 'aviso-flotante';
        flotante.setAttribute('role', 'status');
        flotante.innerHTML = `
            <p><strong>${cantidad} el precio del proveedor para ${escapartemporada(temporada.tbtemporadanombre)}.</strong>
            Mientras tanto se cobran al precio normal. ¿Desea escribir cuánto le cobran en esta temporada?</p>
            <div class="aviso-flotante-acciones">
                <button type="button" class="boton" data-flotante="cerrar">Ahora no</button>
                <button type="button" class="boton boton-principal" data-flotante="agregar">Agregar precios</button>
            </div>`;
        document.body.append(flotante);

        const dialogo = document.createElement('dialog');
        dialogo.className = 'dialogo dialogo-ancho';
        dialogo.innerHTML = `
            <form novalidate>
                <h2>Precios del proveedor en ${escapartemporada(temporada.tbtemporadanombre)}</h2>
                <p class="nota">Escriba cuánto le cobra el proveedor por el paquete en esta temporada. El precio de venta
                    y el de los arreglos que lo llevan suben solos. Los que deje vacíos se siguen cobrando al precio normal.</p>
                <p class="aviso aviso-error" role="alert" hidden></p>
                <div class="tabla-contenedor">
                    <table class="tabla tabla-lineas">
                        <thead>
                            <tr>
                                <th>${config.singular[0].toUpperCase() + config.singular.slice(1)}</th>
                                <th>Paquete normal</th>
                                <th>Paquete en ${escapartemporada(temporada.tbtemporadanombre)}</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pendientes.map((registro) => `
                                <tr>
                                    <td>${escapartemporada(registro.nombre)}${registro.categoria ? `<br><small class="texto-suave">${escapartemporada(registro.categoria)}</small>` : ''}</td>
                                    <td>${montotemporada(registro.precionormal)}</td>
                                    <td><input type="text" name="precios[${registro.id}]" inputmode="decimal" maxlength="11" autocomplete="off"
                                               placeholder="${String(registro.precionormal)}" aria-label="Precio del paquete de ${escapartemporada(registro.nombre)}"></td>
                                </tr>`).join('')}
                        </tbody>
                    </table>
                </div>
                <div class="acciones-dialogo">
                    <button type="button" class="boton" data-flotante="cancelar">Cancelar</button>
                    <button type="submit" class="boton boton-principal">Guardar precios</button>
                </div>
            </form>`;
        document.body.append(dialogo);

        const formulariovarios = dialogo.querySelector('form');
        const avisovarios = dialogo.querySelector('.aviso');

        function cerrarflotante() {
            flotante.remove();
            try {
                sessionStorage.setItem(clavedescartado, '1');
            } catch (error) {
                // Sin almacenamiento: no pasa nada
            }
        }

        flotante.addEventListener('click', (evento) => {
            const boton = evento.target.closest('[data-flotante]');
            if (!boton) {
                return;
            }
            if (boton.dataset.flotante === 'agregar') {
                dialogo.showModal();
            } else {
                cerrarflotante();
            }
        });

        dialogo.querySelector('[data-flotante="cancelar"]').addEventListener('click', () => dialogo.close());

        // Cada precio se revisa mientras se escribe
        formulariovarios.addEventListener('input', (evento) => {
            evento.target.value = decimaltemporada(evento.target.value);

            const mensajes = [];
            pendientes.forEach((registro) => {
                const campo = formulariovarios.elements[`precios[${registro.id}]`];
                const mensaje = errorprecio(campo.value, temporada.tbtemporadanombre);
                if (mensaje) {
                    campo.setAttribute('aria-invalid', 'true');
                    mensajes.push(`${registro.nombre}: ${mensaje}`);
                } else {
                    campo.removeAttribute('aria-invalid');
                }
            });
            avisovarios.textContent = mensajes.join(' ');
            avisovarios.hidden = mensajes.length === 0;
        });

        formulariovarios.addEventListener('submit', async (evento) => {
            evento.preventDefault();
            avisovarios.hidden = true;

            let resultado;
            try {
                resultado = await (await fetch(`index.php?modulo=${config.modulo}&accion=guardarpreciostemporada`, {
                    method: 'POST',
                    body: new FormData(formulariovarios),
                })).json();
            } catch (error) {
                resultado = { exito: false, mensaje: 'No se pudo conectar con el servidor.', datos: [] };
            }

            if (!resultado.exito) {
                const errores = resultado.datos && resultado.datos.errores ? resultado.datos.errores : {};
                Object.keys(errores).forEach((id) => {
                    const campo = formulariovarios.elements[`precios[${id}]`];
                    if (campo) {
                        campo.setAttribute('aria-invalid', 'true');
                    }
                });
                avisovarios.textContent = Object.values(errores).join(' ') || resultado.mensaje;
                avisovarios.hidden = false;
                return;
            }

            dialogo.close();
            flotante.remove();
            dialogo.remove();
            if (config.alguardar) {
                config.alguardar(resultado.mensaje);
            }
            // Si todavía faltan, el aviso vuelve a aparecer con los que quedan
            aviso(config);
        });
    }

    return { dibujar, ventas, filtrar, revisar, agregar, aviso, redondear };
})();
