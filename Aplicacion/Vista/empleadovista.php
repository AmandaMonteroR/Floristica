<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Empleados | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Empleados</h1>
            <p class="subtitulo">Personas de la floristería a las que se les asignan los encargos de arreglos.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo empleado</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre">
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Rol</th>
                    <th>Usuario</th>
                    <th>Encargos abiertos</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando empleados…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo empleado</h2>
        <p class="nota">Los campos con * son obligatorios. Solo puede haber una dueña activa.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbempleadoid">

        <div class="rejilla">
            <label class="campo campo-completo">
                <span>Nombre completo *</span>
                <input type="text" name="tbempleadonombre" maxlength="100">
                <small class="error" data-error="tbempleadonombre"></small>
            </label>
            <label class="campo">
                <span>Teléfono *</span>
                <input type="tel" name="tbempleadotelefono" inputmode="numeric" maxlength="9" placeholder="8888-8888" autocomplete="off">
                <small class="error" data-error="tbempleadotelefono"></small>
            </label>
            <label class="campo">
                <span>Rol *</span>
                <select name="tbempleadorol">
                    <option value="">Seleccione…</option>
                    <option value="Empleado">Empleado</option>
                    <option value="Dueña">Dueña</option>
                </select>
                      <small class="error" data-error="tbempleadorol"></small>
            </label>
        </div>

        <fieldset class="seccion-categoria">
            <legend>Acceso al sistema</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Usuario *</span>
                    <input type="text" name="tbempleadousuario" maxlength="30" autocomplete="off" autocapitalize="none" spellcheck="false" placeholder="ana.vargas">
                    <small class="error" data-error="tbempleadousuario"></small>
                </label>
                <label class="campo">
                    <span id="etiquetaclave">Contraseña *</span>
                    <input type="password" name="tbempleadoclave" maxlength="72" autocomplete="new-password">
                    <small class="nota-campo" id="notaclave">Mínimo 8 caracteres, con al menos una letra y un número.</small>
                    <small class="error" data-error="tbempleadoclave"></small>
                </label>
            </div>
        </fieldset>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar empleado</button>
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
<script src="js/empleado.js"></script>
</body>
</html>
