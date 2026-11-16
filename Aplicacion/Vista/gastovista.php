<?php require_once __DIR__ . '/../Controlador/gastocontrolador.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gastos | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Gastos del negocio</h1>
            <p class="subtitulo">Lo que se paga para que la floristería funcione y no es mercadería: alquiler, luz, agua, salarios…
                Se restan en el reporte de ganancia neta.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Registrar gasto</button>
    </header>

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
        <label class="campo">
            <span>Categoría</span>
            <select id="filtrocategoria">
                <option value="">Todas</option>
                <?php foreach (gastocontrolador::CATEGORIAS as $categoria): ?>
                    <option value="<?= e($categoria) ?>"><?= e($categoria) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="1">Cuentan</option>
                <option value="0">Anulados</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <section class="resumen-gastos" aria-live="polite">
        <article class="tarjeta-dato">
            <span>Total de gastos</span>
            <strong id="datototal">—</strong>
            <small id="notatotal"></small>
        </article>
        <article class="tarjeta-dato tarjeta-categorias">
            <span>Por categoría</span>
            <ul id="listacategorias" class="barras-categoria"></ul>
        </article>
    </section>

    <div class="tabla-contenedor">
        <table class="tabla tabla-gastos">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Categoría</th>
                    <th>Descripción</th>
                    <th class="numero">Monto</th>
                    <th>Registrado por</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando gastos…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Registrar gasto</h2>
        <p class="nota">Los campos con * son obligatorios.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbgastoid">

        <div class="rejilla">
            <label class="campo">
                <span>Categoría *</span>
                <select name="tbgastocategoria">
                    <option value="">Seleccione…</option>
                    <?php foreach (gastocontrolador::CATEGORIAS as $categoria): ?>
                        <option value="<?= e($categoria) ?>"><?= e($categoria) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="error" data-error="tbgastocategoria"></small>
            </label>
            <label class="campo">
                <span>Monto (₡) *</span>
                <input type="text" name="tbgastomonto" inputmode="decimal" maxlength="11" placeholder="45000" autocomplete="off">
                <small class="error" data-error="tbgastomonto"></small>
            </label>
            <label class="campo campo-completo">
                <span>Descripción *</span>
                <input type="text" name="tbgastodescripcion" maxlength="100" placeholder="Recibo de luz de noviembre" autocomplete="off">
                <small class="error" data-error="tbgastodescripcion"></small>
            </label>
            <label class="campo">
                <span>Fecha del pago *</span>
                <input type="date" name="tbgastofecha">
                <small class="error" data-error="tbgastofecha"></small>
            </label>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar gasto</button>
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

<script src="<?= recurso('js/menu.js') ?>"></script>
<script src="<?= recurso('js/historial.js') ?>"></script>
<script src="<?= recurso('js/gasto.js') ?>"></script>
</body>
</html>
