<?php
$verreporte = ($_GET['ver'] ?? '') === 'trazabilidad' ? 'trazabilidad' : 'ganancia';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor" data-ver="<?= e($verreporte) ?>">
    <header class="encabezado">
        <div>
            <h1>Reportes</h1>
        </div>
        <button type="button" class="boton boton-claro" id="botonimprimir">Imprimir</button>
    </header>

    <nav class="pestanas" aria-label="Reporte">
        <a href="index.php?modulo=reporte&amp;ver=ganancia"<?= $verreporte === 'ganancia' ? ' class="activa" aria-current="page"' : '' ?>>Balance</a>
        <a href="index.php?modulo=reporte&amp;ver=trazabilidad"<?= $verreporte === 'trazabilidad' ? ' class="activa" aria-current="page"' : '' ?>>Trazabilidad</a>
    </nav>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <?php if ($verreporte === 'ganancia'): ?>
        <section class="filtros filtros-ventas">
            <div class="campo">
                <span>Período</span>
                <div class="chips-periodo" id="chipsperiodo">
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
        </section>

        <section class="reporte-ganancia">
            <article class="estado-resultados" aria-live="polite">
                <h2 id="tituloperiodo">Balance</h2>
                <dl id="resultados" class="lineas-resultado"></dl>
            </article>
            <article class="meses-ganancia">
                <h2>Últimos meses</h2>
                <div class="tabla-contenedor">
                    <table class="tabla tabla-meses">
                        <thead>
                            <tr>
                                <th>Mes</th>
                                <th class="numero">Vendido</th>
                                <th class="numero">Gastos</th>
                                <th class="numero">Balance</th>
                            </tr>
                        </thead>
                        <tbody id="cuerpomeses"></tbody>
                    </table>
                </div>
                <p class="nota">Vendido: encargos terminados en el mes. Gastos: lo que salió de la caja (alquiler, luz,
                    salarios… y las compras a proveedores). Balance: vendido − gastos.</p>
            </article>
        </section>
    <?php else: ?>
        <section class="filtros">
            <label class="campo">
                <span>Qué quiere ver</span>
                <select id="tipotraza">
                    <option value="solicitud">Un encargo</option>
                    <option value="pedido">Un pedido al proveedor</option>
                </select>
            </label>
            <label class="campo campo-busqueda">
                <span id="etiquetatraza">Encargo</span>
                <select id="registrotraza" aria-labelledby="etiquetatraza">
                    <option value="">Cargando…</option>
                </select>
            </label>
        </section>

        <article id="traza" class="traza" aria-live="polite">
            <p class="texto-suave">Elija un encargo o un pedido para ver todo lo que pasó, cuándo y quién lo hizo.</p>
        </article>
    <?php endif; ?>
</main>

<script src="<?= recurso('js/menu.js') ?>"></script>
<script src="<?= recurso('js/reporte.js') ?>"></script>
</body>
</html>