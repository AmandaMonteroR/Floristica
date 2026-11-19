<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ventas y ganancias | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Ventas y ganancias</h1>
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
                <button type="button" class="chip" data-periodo="anio">Este año</button>
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
        <div class="campo">
            <span>Ver el balance por</span>
            <div class="chips-periodo" id="chipsagrupar">
                <button type="button" class="chip" data-agrupar="dia">Día</button>
                <button type="button" class="chip" data-agrupar="semana">Semana</button>
                <button type="button" class="chip" data-agrupar="mes">Mes</button>
            </div>
        </div>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <section class="tarjetas-ventas" aria-live="polite">
        <article class="tarjeta-dato">
            <span>Vendido</span>
            <strong id="datovendido">—</strong>
            <small id="notavendido"></small>
        </article>
        <article class="tarjeta-dato tarjeta-gastos">
            <span>Gastos</span>
            <strong id="datogastos">—</strong>
            <small id="notagastos"></small>
        </article>
        <article class="tarjeta-dato" id="tarjetabalance">
            <span>Balance</span>
            <strong id="datobalance">—</strong>
            <small>Vendido − gastos</small>
        </article>
    </section>

    <section class="seccion-balance">
    <h2 class="subtitulo-seccion" id="titulobalance">Balance por día</h2>
    <div class="tabla-contenedor">
        <table class="tabla tabla-balance">
            <thead>
                <tr>
                    <th id="columnaperiodo">Día</th>
                    <th class="numero">Ventas</th>
                    <th class="numero">Vendido</th>
                    <th class="numero">Gastos</th>
                    <th class="numero">Balance</th>
                    <th class="columna-acciones"><span class="solo-lector">Ver detalle</span></th>
                </tr>
            </thead>
            <tbody id="cuerpobalance">
                <tr><td colspan="6" class="vacio">Cargando…</td></tr>
            </tbody>
            <tfoot id="piebalance"></tfoot>
        </table>
    </div>
    <nav class="paginacion" id="paginacionbalance" aria-label="Páginas del balance" hidden></nav>
    </section>

    <section class="detalle-ventas">
        <h2 class="subtitulo-seccion">Lo que se vendió</h2>
        <div class="tabla-contenedor">
            <table class="tabla tabla-ventas">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Cliente</th>
                        <th>Qué se vendió</th>
                        <th class="numero">Total</th>
                    </tr>
                </thead>
                <tbody id="cuerpoventas"></tbody>
            </table>
        </div>
        <nav class="paginacion" id="paginacionventas" aria-label="Páginas de lo que se vendió" hidden></nav>
    </section>
</main>

<script src="<?= recurso('js/menu.js') ?>"></script>
<script src="<?= recurso('js/venta.js') ?>"></script>
</body>
</html>