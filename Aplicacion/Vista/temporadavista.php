<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporada alta | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Temporada alta</h1>
            <p class="subtitulo">Cuando suben las flores (San Valentín, Día de la Madre…). Se enciende y se apaga a mano.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonhistorial">Historial</button>
    </header>

    <section class="banner-temporada" id="bannertemporada" aria-live="polite">
        <div>
            <strong id="textoestado">Cargando…</strong>
            <p class="texto-suave" id="notaestado"></p>
        </div>
        <button type="button" class="boton boton-principal" id="botonalternar" hidden></button>
    </section>

    <section class="filtros filtros-temporada">
        <label class="campo campo-busqueda">
            <span>Buscar flor</span>
            <input type="search" id="busqueda" placeholder="Rosa, girasol, eucalipto…">
        </label>
        <div class="acciones-temporada">
            <span class="texto-suave" id="resumenprecios"></span>
            <button type="button" class="boton boton-principal" id="botonguardar" disabled>Guardar precios</button>
        </div>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla tabla-temporada">
            <thead>
                <tr>
                    <th>Flor o follaje</th>
                    <th>Paquete normal</th>
                    <th>Paquete en temporada alta</th>
                    <th>Se vende la unidad</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="4" class="vacio">Cargando flores…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogohistorial" class="dialogo dialogo-ancho">
    <h2 id="titulohistorial"></h2>
    <p class="nota">Cuándo se encendió y se apagó, del más antiguo al más reciente.</p>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dato</th>
                    <th>Antes</th>
                    <th>Después</th>
                    <th>Hecho por</th>
                </tr>
            </thead>
            <tbody id="cuerpohistorial"></tbody>
        </table>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton boton-principal" id="botoncerrarhistorial">Cerrar</button>
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
<script src="js/historial.js"></script>
<script src="js/temporada.js"></script>
</body>
</html>
