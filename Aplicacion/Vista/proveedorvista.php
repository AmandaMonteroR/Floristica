<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Proveedores | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Proveedores</h1>
            <p class="subtitulo">Empresas que abastecen de flores e insumos a la floristería.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo proveedor</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Empresa o contacto">
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
                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="5" class="vacio">Cargando proveedores…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo proveedor</h2>
        <p class="nota">Los campos con * son obligatorios.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbproveedorid">

        <div class="rejilla">
            <label class="campo">
                <span>Nombre de la empresa *</span>
                <input type="text" name="tbproveedornombreempresa" maxlength="100">
                <small class="error" data-error="tbproveedornombreempresa"></small>
            </label>
            <label class="campo">
                <span>Nombre del contacto *</span>
                <input type="text" name="tbproveedornombrecontacto" maxlength="100">
                <small class="error" data-error="tbproveedornombrecontacto"></small>
            </label>
            <label class="campo">
                <span>Teléfono *</span>
                <input type="tel" name="tbproveedortelefono" inputmode="numeric" maxlength="9" placeholder="8888-8888" autocomplete="off">
                <small class="error" data-error="tbproveedortelefono"></small>
            </label>
            <label class="campo">
                <span>Correo</span>
                <input type="email" name="tbproveedorcorreo" maxlength="100">
                <small class="error" data-error="tbproveedorcorreo"></small>
            </label>
            <div class="campo-completo rejilla-ubicacion">
                <label class="campo">
                    <span>Provincia *</span>
                    <select name="tbproveedorprovincia">
                        <option value="">Seleccione…</option>
                    </select>
                    <small class="error" data-error="tbproveedorprovincia"></small>
                </label>
                <label class="campo">
                    <span>Cantón *</span>
                    <select name="tbproveedorcanton" disabled>
                        <option value="">Seleccione la provincia</option>
                    </select>
                    <small class="error" data-error="tbproveedorcanton"></small>
                </label>
                <label class="campo">
                    <span>Distrito *</span>
                    <select name="tbproveedordistrito" disabled>
                        <option value="">Seleccione el cantón</option>
                    </select>
                    <small class="error" data-error="tbproveedordistrito"></small>
                </label>
            </div>
            <label class="campo campo-completo">
                <span>Dirección exacta</span>
                <textarea name="tbproveedordireccion" maxlength="200" placeholder="200 m norte de la iglesia…"></textarea>
                <small class="error" data-error="tbproveedordireccion"></small>
            </label>
            <label class="campo campo-completo">
                <span>Productos que ofrece</span>
                <textarea name="tbproveedordescripcion" maxlength="255" placeholder="Rosas, follaje, papel de envolver…"></textarea>
                <small class="error" data-error="tbproveedordescripcion"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar proveedor</button>
        </div>
    </form>
</dialog>

<dialog id="dialogodetalle" class="dialogo">
    <h2 id="titulodetalle"></h2>
    <dl id="listadetalle" class="detalle"></dl>
    <div class="acciones-dialogo">
        <button type="button" class="boton" id="botonverhistorial">Ver historial</button>
        <button type="button" class="boton boton-principal" id="botoncerrardetalle">Cerrar</button>
    </div>
</dialog>

<dialog id="dialogohistorial" class="dialogo dialogo-ancho">
    <h2 id="titulohistorial"></h2>
    <p class="nota">Cambios en los datos principales del proveedor, del más reciente al más antiguo.</p>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dato</th>
                    <th>Valor anterior</th>
                    <th>Valor nuevo</th>
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
<script src="js/proveedor.js"></script>
</body>
</html>