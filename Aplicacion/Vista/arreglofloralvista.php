<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Catálogo</h1>
            <p class="subtitulo">Arreglos florales que ofrece la floristería, armados con productos del inventario.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo arreglo</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre del arreglo">
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

    <div id="galeria" class="galeria">
        <p class="vacio-galeria">Cargando catálogo…</p>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo dialogo-ancho">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo arreglo</h2>
        <p class="nota">Los campos con * son obligatorios. Las cantidades de productos son en unidades.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado (la foto debe elegirse de nuevo).</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbarreglofloralid">

        <div class="formulario-arreglo">
            <div class="campo-foto">
                <span class="etiqueta-foto" id="etiquetafoto">Foto *</span>
                <label class="vista-foto" for="campofoto">
                    <img id="vistafoto" alt="" hidden>
                    <span id="textofoto">Toque para elegir una foto<br><small>JPG, PNG o WEBP, máximo 2 MB</small></span>
                </label>
                <input type="file" id="campofoto" name="tbarreglofloralfoto" accept="image/jpeg,image/png,image/webp" class="solo-lector">
                <small class="error" data-error="tbarreglofloralfoto"></small>
            </div>

            <div class="rejilla">
                <label class="campo campo-completo">
                    <span>Nombre del arreglo *</span>
                    <input type="text" name="tbarreglofloralnombre" maxlength="100">
                    <small class="error" data-error="tbarreglofloralnombre"></small>
                </label>
                <label class="campo campo-completo">
                    <span>Descripción</span>
                    <textarea name="tbarreglofloraldescripcion" maxlength="255" placeholder="Ideal para cumpleaños, incluye tarjeta…"></textarea>
                    <small class="error" data-error="tbarreglofloraldescripcion"></small>
                </label>
                <label class="campo">
                    <span>Precio de venta (₡) *</span>
                    <input type="text" name="tbarreglofloralprecio" inputmode="decimal" maxlength="11" autocomplete="off" placeholder="25000">
                    <small class="error" data-error="tbarreglofloralprecio"></small>
                </label>
                <div class="resumen-costo">
                    <span>Costo de productos: <strong id="costoarreglo">₡0,00</strong></span>
                    <span>Ganancia: <strong id="gananciaarreglo">₡0,00</strong></span>
                </div>
            </div>
        </div>

        <h3 class="subtitulo-seccion">Productos del arreglo</h3>
        <div class="tabla-contenedor">
            <table class="tabla tabla-lineas">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades</th>
                        <th>Costo unidad</th>
                        <th>Subtotal</th>
                        <th><span class="solo-lector">Quitar</span></th>
                    </tr>
                </thead>
                <tbody id="cuerpolineas"></tbody>
            </table>
        </div>
        <small class="error" data-error="detalles"></small>

        <div class="pie-lineas">
            <button type="button" class="boton" id="botonagregarlinea">+ Agregar producto</button>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar arreglo</button>
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
    <h3 class="subtitulo-seccion">Productos</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Unidades</th>
                    <th>Costo unidad</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="cuerpodetalle"></tbody>
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
<script src="js/arreglofloral.js"></script>
</body>
</html>