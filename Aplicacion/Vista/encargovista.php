<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Solicitudes de pedido de arreglo | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Solicitudes de pedido de arreglo</h1>
            <p class="subtitulo">Pedidos de los clientes: arreglos del catálogo o personalizados, con o sin servicio express.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nueva solicitud</button>
    </header>

    <nav class="pestanas" aria-label="Secciones del catálogo">
        <a href="index.php?modulo=catalogo">Arreglos</a>
        <a href="index.php?modulo=encargo" class="activa" aria-current="page">Solicitudes de pedido de arreglo</a>
    </nav>

    <section class="filtros">
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="abiertos">Abiertas (pendientes y en proceso)</option>
                <option value="atrasados">Atrasadas</option>
                <option value="Pendiente">Pendientes</option>
                <option value="En proceso">En proceso</option>
                <option value="Terminado">Terminadas</option>
                <option value="Cancelado">Canceladas</option>
            </select>
        </label>
        <label class="campo">
            <span>Tipo</span>
            <select id="filtrotipo">
                <option value="">Todos</option>
                <option value="Predefinido">Predefinidos</option>
                <option value="Personalizado">Personalizados</option>
            </select>
        </label>
        <label class="campo campo-busqueda">
            <span>Asignado a</span>
            <select id="filtroempleado">
                <option value="">Todos</option>
                <option value="sinasignar">Sin asignar</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla tabla-encargos">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando solicitudes…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo dialogo-ancho">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nueva solicitud de pedido de arreglo</h2>
        <p class="nota">Los campos con * son obligatorios. Los precios salen del catálogo, del inventario y de las zonas de entrega.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <p id="avisotemporadaform" class="aviso aviso-temporada" hidden></p>
        <input type="hidden" name="tbsolicitudencargoid">

        <div class="rejilla">
            <label class="campo">
                <span>Cliente *</span>
                <select name="tbclienteid">
                    <option value="">Seleccione…</option>
                </select>
                <small class="nota-campo texto-suave">¿No aparece? Regístrelo en <a href="index.php?modulo=cliente&amp;nuevo=1" target="_blank" rel="noopener">Clientes</a>.</small>
                <small class="error" data-error="tbclienteid"></small>
            </label>
            <label class="campo">
                <span>Tipo de solicitud *</span>
                <select name="tbsolicitudencargotipo">
                    <option value="">Seleccione…</option>
                    <option value="Predefinido">Predefinido (del catálogo)</option>
                    <option value="Personalizado">Personalizado</option>
                </select>
                <small class="error" data-error="tbsolicitudencargotipo"></small>
            </label>
        </div>

        <fieldset class="seccion-categoria" id="seccionpredefinido" hidden>
            <legend>Arreglo del catálogo</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Arreglo *</span>
                    <select name="tbarreglofloralid">
                        <option value="">Seleccione…</option>
                    </select>
                    <small class="error" data-error="tbarreglofloralid"></small>
                </label>
                <div class="campo">
                    <span>Cantidad de arreglos *</span>
                    <div class="contador">
                        <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar un arreglo">−</button>
                        <input type="text" name="tbsolicitudencargocantidad" inputmode="numeric" maxlength="2" autocomplete="off" aria-label="Cantidad de arreglos">
                        <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar un arreglo">+</button>
                    </div>
                    <small class="error" data-error="tbsolicitudencargocantidad"></small>
                </div>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" id="seccionpersonalizado" hidden>
            <legend>Arreglo personalizado</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Modalidad *</span>
                    <select name="tbsolicitudencargomodalidad">
                        <option value="">Seleccione…</option>
                        <option value="Presupuesto">Con presupuesto</option>
                        <option value="Libre">Libre (cotizar el total)</option>
                    </select>
                    <small class="error" data-error="tbsolicitudencargomodalidad"></small>
                </label>
                <label class="campo" id="campopresupuesto" hidden>
                    <span>Presupuesto del cliente (₡) *</span>
                    <input type="text" name="tbsolicitudencargopresupuesto" inputmode="decimal" maxlength="11" placeholder="15000" autocomplete="off">
                    <small class="error" data-error="tbsolicitudencargopresupuesto"></small>
                </label>
            </div>
        </fieldset>

        <section id="seccionlineas" hidden>
            <h3 class="subtitulo-seccion" id="titulolineas">Adicionales</h3>
            <p class="nota" id="notalineas"></p>
            <div class="tabla-contenedor">
                <table class="tabla tabla-lineas">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio</th>
                            <th>Subtotal</th>
                            <th><span class="solo-lector">Quitar</span></th>
                        </tr>
                    </thead>
                    <tbody id="cuerpolineas"></tbody>
                </table>
            </div>
            <small class="error" data-error="insumos"></small>
            <div class="pie-lineas">
                <button type="button" class="boton" id="botonagregarlinea">+ Agregar producto</button>
                <span id="restopresupuesto" class="resto-presupuesto" hidden></span>
            </div>
        </section>

        <fieldset class="seccion-categoria">
            <legend>Entrega</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Fecha de entrega *</span>
                    <input type="date" name="tbsolicitudencargofechaentrega">
                    <small class="error" data-error="tbsolicitudencargofechaentrega"></small>
                </label>
                <div class="campo">
                    <span>Servicio express</span>
                    <label class="casilla">
                        <input type="checkbox" name="tbsolicitudencargoexpress" value="1">
                        <span>Llevarlo a domicilio</span>
                    </label>
                    <small class="nota-campo texto-suave" id="notaexpress">Si no, el cliente lo recoge en el local.</small>
                </div>
                <label class="campo" id="campozona" hidden>
                    <span>Zona de entrega *</span>
                    <select name="tbzonaentregaid">
                        <option value="">Seleccione…</option>
                    </select>
                    <small class="error" data-error="tbzonaentregaid"></small>
                </label>
                <label class="campo campo-completo" id="campodireccion" hidden>
                    <span>Dirección de entrega *</span>
                    <textarea name="tbsolicitudencargodireccionentrega" maxlength="200" placeholder="Cariari, barrio Palermo, casa 12…"></textarea>
                    <small class="error" data-error="tbsolicitudencargodireccionentrega"></small>
                </label>
            </div>
        </fieldset>

        <div class="rejilla">
            <label class="campo" id="campoasignar">
                <span>Asignar a</span>
                <select name="tbempleadoid">
                    <option value="">Sin asignar por ahora</option>
                </select>
                <small class="error" data-error="tbempleadoid"></small>
            </label>
            <label class="campo">
                <span>Nota</span>
                <textarea name="tbsolicitudencargonota" maxlength="255" placeholder="Texto de la tarjeta, colores, hora de entrega…"></textarea>
                <small class="error" data-error="tbsolicitudencargonota"></small>
            </label>
        </div>

        <div class="resumen-solicitud" aria-live="polite">
            <dl id="resumentotal"></dl>
            <button type="button" class="boton" id="botoncopiar">Copiar cotización para WhatsApp</button>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar solicitud</button>
        </div>
    </form>
</dialog>

<dialog id="dialogoasignar" class="dialogo dialogo-angosto">
    <form id="formularioasignar" novalidate>
        <h2>Asignar solicitud</h2>
        <p class="nota" id="resumenasignar"></p>
        <p id="avisoasignar" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbsolicitudencargoid">

        <label class="campo">
            <span>¿Quién la prepara? *</span>
            <select name="tbempleadoid">
                <option value="">Seleccione…</option>
            </select>
            <small class="error" data-error="tbempleadoid"></small>
        </label>

        <div class="acciones-dialogo acciones-separadas">
            <button type="button" class="boton" id="botonasignarme">Asignármela a mí</button>
            <div class="acciones-grupo">
                <button type="button" class="boton" id="botoncancelarasignar">Cancelar</button>
                <button type="submit" class="boton boton-principal" id="botonguardarasignar">Asignar</button>
            </div>
        </div>
    </form>
</dialog>

<dialog id="dialogodetalle" class="dialogo dialogo-ancho">
    <div class="detalle-arreglo">
        <img id="fotodetalle" class="foto-detalle" alt="">
        <div>
            <h2 id="titulodetalle"></h2>
            <dl id="listadetalle" class="detalle"></dl>
        </div>
    </div>
    <h3 class="subtitulo-seccion">Cobro</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="cuerpocobro"></tbody>
        </table>
    </div>
    <h3 class="subtitulo-seccion">Productos que se sacan del inventario</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Necesarias</th>
                    <th>En inventario</th>
                    <th>¿Alcanza?</th>
                </tr>
            </thead>
            <tbody id="cuerpodetalle"></tbody>
        </table>
    </div>
    <h3 class="subtitulo-seccion">Historial</h3>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Qué pasó</th>
                    <th>Hecho por</th>
                </tr>
            </thead>
            <tbody id="cuerpohistorial"></tbody>
        </table>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton boton-principal" id="botoncerrardetalle">Cerrar</button>
    </div>
</dialog>

<dialog id="dialogoconfirmacion" class="dialogo dialogo-confirmacion">
    <div class="icono-confirmacion" id="iconoconfirmacion" aria-hidden="true"></div>
    <h2 id="tituloconfirmacion"></h2>
    <p id="mensajeconfirmacion" class="mensaje-confirmacion"></p>
    <div class="acciones-dialogo acciones-centradas">
        <button type="button" class="boton" id="botoncancelarconfirmacion">Cancelar</button>
        <button type="button" class="boton boton-principal" id="botonaceptarconfirmacion"></button>
    </div>
</dialog>

<script src="js/menu.js"></script>
<script src="js/encargo.js"></script>
</body>
</html>
