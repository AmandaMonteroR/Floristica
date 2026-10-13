<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encargos | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Encargos</h1>
            <p class="subtitulo">Arreglos que hay que preparar: a quién se le asignan y cómo van.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo encargo</button>
    </header>

    <nav class="pestanas" aria-label="Secciones del catálogo">
        <a href="index.php?modulo=catalogo">Arreglos</a>
        <a href="index.php?modulo=encargo" class="activa" aria-current="page">Encargos</a>
    </nav>

    <section class="filtros">
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="abiertos">Abiertos (pendientes y en proceso)</option>
                <option value="atrasados">Atrasados</option>
                <option value="Pendiente">Pendientes</option>
                <option value="En proceso">En proceso</option>
                <option value="Terminado">Terminados</option>
                <option value="Cancelado">Cancelados</option>
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
                    <th>Arreglo</th>
                    <th>Cant.</th>
                    <th>Entrega</th>
                    <th>Asignado a</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando encargos…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo encargo</h2>
        <p class="nota">Los campos con * son obligatorios.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbencargoid">

        <div class="rejilla">
            <label class="campo campo-completo">
                <span>Arreglo *</span>
                <select name="tbarreglofloralid">
                    <option value="">Seleccione…</option>
                </select>
                <small class="error" data-error="tbarreglofloralid"></small>
            </label>
            <div class="campo">
                <span>Cantidad de arreglos *</span>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar uno">−</button>
                    <input type="text" name="tbencargocantidad" inputmode="numeric" maxlength="2" autocomplete="off" aria-label="Cantidad de arreglos">
                    <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar uno">+</button>
                </div>
                <small class="error" data-error="tbencargocantidad"></small>
            </div>
            <label class="campo">
                <span>Fecha de entrega *</span>
                <input type="date" name="tbencargofechaentrega">
                <small class="error" data-error="tbencargofechaentrega"></small>
            </label>
            <label class="campo campo-completo" id="campoasignar">
                <span>Asignar a</span>
                <select name="tbempleadoid">
                    <option value="">Sin asignar por ahora</option>
                </select>
                <small class="error" data-error="tbempleadoid"></small>
            </label>
            <label class="campo campo-completo">
                <span>Nota</span>
                <textarea name="tbencargonota" maxlength="255" placeholder="Para la señora Rodríguez, tarjeta de cumpleaños…"></textarea>
                <small class="error" data-error="tbencargonota"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar encargo</button>
        </div>
    </form>
</dialog>

<dialog id="dialogoasignar" class="dialogo dialogo-angosto">
    <form id="formularioasignar" novalidate>
        <h2>Asignar encargo</h2>
        <p class="nota" id="resumenasignar"></p>
        <p id="avisoasignar" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbencargoid">

        <label class="campo">
            <span>¿Quién lo prepara? *</span>
            <select name="tbempleadoid">
                <option value="">Seleccione…</option>
            </select>
            <small class="error" data-error="tbempleadoid"></small>
        </label>

        <div class="acciones-dialogo acciones-separadas">
            <button type="button" class="boton" id="botonasignarme">Asignármelo a mí</button>
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
    <h3 class="subtitulo-seccion">Productos que se necesitan</h3>
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