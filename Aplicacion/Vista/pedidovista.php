<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pedidos | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Pedidos</h1>
            <p class="subtitulo">Compras a proveedores: se registran, se confirman y al recibirlas se suman al inventario.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo pedido</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Proveedor</span>
            <select id="filtroproveedor">
                <option value="">Todos</option>
            </select>
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="Pendiente">Pendientes</option>
                <option value="Confirmado">Confirmados</option>
                <option value="Recibido">Recibidos</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Fecha</th>
                    <th>Proveedor</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="6" class="vacio">Cargando pedidos…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo dialogo-ancho">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo pedido</h2>
        <p class="nota">Los campos con * son obligatorios. Las cantidades se piden por paquete.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbpedidoid">

        <div class="rejilla">
            <label class="campo">
                <span>Proveedor *</span>
                <select name="tbproveedorid">
                    <option value="">Seleccione…</option>
                </select>
                <small class="error" data-error="tbproveedorid"></small>
            </label>
            <label class="campo">
                <span>Fecha del pedido *</span>
                <input type="date" name="tbpedidofecha">
                <small class="error" data-error="tbpedidofecha"></small>
            </label>
        </div>

        <h3 class="subtitulo-seccion">Productos</h3>
        <div class="tabla-contenedor">
            <table class="tabla tabla-lineas">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Paquetes</th>
                        <th>Precio por paquete (₡)</th>
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
            <p class="total-pedido">Total: <strong id="totalpedido">₡0,00</strong></p>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar pedido</button>
        </div>
    </form>
</dialog>

<dialog id="dialogoconfirmarcompra" class="dialogo">
    <form id="formularioconfirmar" novalidate>
        <h2>Confirmar compra</h2>
        <p class="nota" id="resumenconfirmar"></p>

        <p id="avisoconfirmar" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbpedidoid">

        <div class="rejilla">
            <label class="campo">
                <span>Tipo de pago *</span>
                <select name="tbconfirmacioncompratipopago">
                    <option value="">Seleccione…</option>
                    <option value="Contado">Contado</option>
                    <option value="Crédito">Crédito</option>
                </select>
                <small class="error" data-error="tbconfirmacioncompratipopago"></small>
            </label>
            <label class="campo" id="campoplazo" hidden>
                <span>Plazo (días) *</span>
                <input type="text" name="tbconfirmacioncompraplazodias" inputmode="numeric" maxlength="3" autocomplete="off" placeholder="30">
                <small class="error" data-error="tbconfirmacioncompraplazodias"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelarconfirmar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardarconfirmar">Confirmar compra</button>
        </div>
    </form>
</dialog>

<dialog id="dialogodetalle" class="dialogo dialogo-ancho">
    <h2 id="titulodetalle"></h2>
    <dl id="listadetalle" class="detalle"></dl>
    <h3 class="subtitulo-seccion">Productos</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Paquetes</th>
                    <th>Unidades</th>
                    <th>Precio por paquete</th>
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
<script src="js/pedido.js"></script>
</body>
</html>