<?php
// Cuentas por cobrar (lo que deben las clientas) y por pagar (pedidos comprados a crédito)
$vercuentas = ($_GET['ver'] ?? '') === 'pagar' ? 'pagar' : 'cobrar';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuentas por cobrar y por pagar | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor" data-ver="<?= e($vercuentas) ?>">
    <header class="encabezado">
        <div>
            <h1>Cuentas por cobrar y por pagar</h1>
            <p class="subtitulo">Lo que deben las clientas (adelantos y arreglos entregados sin pagar completos) y lo que se les debe
                a los proveedores por los pedidos comprados a crédito.</p>
        </div>
    </header>

    <nav class="pestanas" aria-label="Tipo de cuenta">
        <a href="index.php?modulo=credito&amp;ver=cobrar"<?= $vercuentas === 'cobrar' ? ' class="activa" aria-current="page"' : '' ?>>Por cobrar (clientas)</a>
        <a href="index.php?modulo=credito&amp;ver=pagar"<?= $vercuentas === 'pagar' ? ' class="activa" aria-current="page"' : '' ?>>Por pagar (proveedores)</a>
    </nav>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <section class="tarjetas-ventas" aria-live="polite">
        <article class="tarjeta-dato">
            <span>Por cobrar: entregadas sin pagar</span>
            <strong id="datocobrarentregado">—</strong>
            <small>Ventas a crédito</small>
        </article>
        <article class="tarjeta-dato">
            <span>Por cobrar: solicitudes abiertas</span>
            <strong id="datocobrarabierto">—</strong>
            <small>Lo que falta después de los adelantos</small>
        </article>
        <article class="tarjeta-dato" id="tarjetapagar">
            <span>Por pagar a proveedores</span>
            <strong id="datopagar">—</strong>
            <small id="notapagar"></small>
        </article>
    </section>

    <section class="filtros">
        <label class="campo campo-casilla">
            <input type="checkbox" id="filtrotodas">
            <span>Mostrar también las que ya están pagadas</span>
        </label>
    </section>

    <div class="tabla-contenedor">
        <table class="tabla tabla-cuentas">
            <thead>
                <?php if ($vercuentas === 'pagar'): ?>
                    <tr>
                        <th>Pedido</th>
                        <th>Proveedor</th>
                        <th>Comprado</th>
                        <th>Vence</th>
                        <th class="numero">Monto</th>
                        <th class="numero">Pagado</th>
                        <th class="numero">Saldo</th>
                        <th>Estado</th>
                        <th class="columna-acciones">Acciones</th>
                    </tr>
                <?php else: ?>
                    <tr>
                        <th>N.º</th>
                        <th>Clienta</th>
                        <th>Solicitud</th>
                        <th class="numero">Total</th>
                        <th class="numero">Pagado</th>
                        <th class="numero">Saldo</th>
                        <th>Estado</th>
                        <th class="columna-acciones">Acciones</th>
                    </tr>
                <?php endif; ?>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td class="vacio">Cargando cuentas…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogocuenta" class="dialogo dialogo-ancho">
    <h2 id="titulocuenta"></h2>
    <dl id="listacuenta" class="detalle"></dl>

    <h3 class="subtitulo-seccion">Abonos</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Cómo se pagó</th>
                    <th>Nota</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody id="cuerpoabonos"></tbody>
        </table>
    </div>

    <form id="formularioabono" class="formulario-abono" novalidate>
        <h3 class="subtitulo-seccion">Registrar un abono</h3>
        <p id="avisoabono" class="aviso aviso-error" role="alert" hidden></p>
        <div class="rejilla">
            <div class="campo">
                <label for="montoabono">Monto (₡) *</label>
                <div class="monto-abono">
                    <input type="text" id="montoabono" name="monto" inputmode="decimal" maxlength="11" autocomplete="off">
                    <button type="button" class="boton boton-pequeno" id="botontodo">Todo el saldo</button>
                </div>
                <small class="error" data-error="monto"></small>
            </div>
            <label class="campo">
                <span>Fecha *</span>
                <input type="date" name="fecha">
                <small class="error" data-error="fecha"></small>
            </label>
            <label class="campo">
                <span>Cómo se pagó *</span>
                <select name="metodo">
                    <option value="">Seleccione…</option>
                </select>
                <small class="error" data-error="metodo"></small>
            </label>
            <label class="campo">
                <span>Nota</span>
                <input type="text" name="nota" maxlength="100" placeholder="Opcional" autocomplete="off">
                <small class="error" data-error="nota"></small>
            </label>
        </div>
    </form>

    <div class="acciones-dialogo">
        <button type="button" class="boton" id="botoncerrarcuenta">Cerrar</button>
        <button type="submit" class="boton boton-principal" id="botonabonar" form="formularioabono">Registrar abono</button>
    </div>
</dialog>

<script src="js/menu.js"></script>
<script src="js/credito.js"></script>
</body>
</html>
