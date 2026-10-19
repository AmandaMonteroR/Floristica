<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Temporadas | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Temporadas</h1>
            <p class="subtitulo">Precios especiales para San Valentín, Día de la Madre y otras fechas. Se activan a mano.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nueva temporada</button>
    </header>

    <section class="banner-temporada" id="bannertemporada" aria-live="polite">
        <div>
            <strong id="textovigente">Cargando…</strong>
            <p class="texto-suave" id="notavigente"></p>
        </div>
        <button type="button" class="boton" id="botondesactivar" hidden>Volver a precios normales</button>
    </section>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre o descripción de la temporada">
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todas</option>
                <option value="1">Activas</option>
                <option value="0">Inactivas</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Temporada</th>
                    <th>Descripción</th>
                    <th>Con precio del proveedor</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="5" class="vacio">Cargando temporadas…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nueva temporada</h2>
        <p class="nota">Los campos con * son obligatorios. Después, en el Inventario, escriba el precio del proveedor de cada flor en esta temporada; los arreglos se calculan solos.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbtemporadaid">

        <div class="rejilla">
            <label class="campo campo-completo">
                <span>Nombre de la temporada *</span>
                <input type="text" name="tbtemporadanombre" maxlength="100" placeholder="San Valentín">
                <small class="error" data-error="tbtemporadanombre"></small>
            </label>
            <label class="campo campo-completo">
                <span>Descripción</span>
                <textarea name="tbtemporadadescripcion" maxlength="200" placeholder="Fecha o detalles de la temporada"></textarea>
                <small class="error" data-error="tbtemporadadescripcion"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar temporada</button>
        </div>
    </form>
</dialog>

<dialog id="dialogohistorial" class="dialogo dialogo-ancho">
    <h2 id="titulohistorial"></h2>
    <p class="nota">Cambios en los datos principales, del más antiguo al más reciente.</p>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dato</th>
                    <th>Valor anterior</th>
                    <th>Valor nuevo</th>
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
