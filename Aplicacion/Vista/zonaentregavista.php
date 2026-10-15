<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Zonas de entrega | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Zonas de entrega</h1>
            <p class="subtitulo">Precios del servicio express desde el local en El Humo de Roxana, Cariari.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nueva zona</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre o descripción de la zona">
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
                    <th>Zona</th>
                    <th>Descripción</th>
                    <th>Precio del express</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="5" class="vacio">Cargando zonas…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nueva zona</h2>
        <p class="nota">Los campos con * son obligatorios.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbzonaentregaid">

        <div class="rejilla">
            <label class="campo">
                <span>Nombre de la zona *</span>
                <input type="text" name="tbzonaentreganombre" maxlength="100" placeholder="Guápiles">
                <small class="error" data-error="tbzonaentreganombre"></small>
            </label>
            <label class="campo">
                <span>Precio del express (₡) *</span>
                <input type="text" name="tbzonaentregaprecio" inputmode="decimal" maxlength="11" placeholder="2500" autocomplete="off">
                <small class="error" data-error="tbzonaentregaprecio"></small>
            </label>
            <label class="campo campo-completo">
                <span>Descripción</span>
                <textarea name="tbzonaentregadescripcion" maxlength="200" placeholder="Barrios o lugares que incluye la zona"></textarea>
                <small class="error" data-error="tbzonaentregadescripcion"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar zona</button>
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
<script src="js/zonaentrega.js"></script>
</body>
</html>
