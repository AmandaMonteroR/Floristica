<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ventas y ganancias | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Ventas y ganancias</h1>
            <p class="subtitulo">Solicitudes terminadas: lo que se vendió y lo que queda después de pagar las flores y productos.</p>
        </div>
    </header>

    <section class="filtros filtros-ventas">
        <div class="campo">
            <span>Período</span>
            <div class="chips-periodo" id="chipsperiodo">
                <button type="button" class="chip" data-periodo="hoy">Hoy</button>
                <button type="button" class="chip" data-periodo="semana">Esta semana</button>
                <button type="button" class="chip" data-periodo="mes">Este mes</button>
                <button type="button" class="chip" data-periodo="mespasado">Mes pasado</button>
                <button type="button" class="chip" data-periodo="todo">Todo</button>
            </div>
        </div>
        <label class="campo">
            <span>Desde</span>
            <input type="date" id="desde">
        </label>
        <label class="campo">
            <span>Hasta</span>
            <input type="date" id="hasta">
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <section class="tarjetas-ventas" aria-live="polite">
        <article class="tarjeta-dato">
            <span>Vendido</span>
            <strong id="datovendido">—</strong>
            <small id="notavendido"></small>
        </article>
        <article class="tarjeta-dato">
            <span>Costo de flores y productos</span>
            <strong id="datocosto">—</strong>
            <small>Lo que se le pagó al proveedor</small>
        </article>
        <article class="tarjeta-dato tarjeta-ganancia">
            <span>Ganancia</span>
            <strong id="datoganancia">—</strong>
            <small id="notaganancia"></small>
        </article>
    </section>

    <div class="tabla-contenedor">
        <table class="tabla tabla-ventas">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Pedido</th>
                    <th>Total</th>
                    <th>Costo</th>
                    <th>Ganancia</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando ventas…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<script src="js/menu.js"></script>
<script src="js/venta.js"></script>
</body>
</html>
