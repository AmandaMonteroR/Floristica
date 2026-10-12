<?php
require_once __DIR__ . '/../Controlador/iniciocontrolador.php';

$resumen = (new iniciocontrolador())->resumen($usuarioactual);
$esduena = Seguridad::esduena();

$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
    'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];

function fechalarga($fecha, $dias, $meses)
{
    $tiempo = strtotime($fecha);
    return $dias[date('w', $tiempo)] . ' ' . date('j', $tiempo) . ' de ' . $meses[date('n', $tiempo) - 1];
}

function textoentrega($fecha, $hoy)
{
    $diferencia = (int) round((strtotime($fecha) - strtotime($hoy)) / 86400);

    if ($diferencia < 0) {
        return ['Atrasado ' . abs($diferencia) . ($diferencia === -1 ? ' día' : ' días'), 'estado-bajo'];
    }
    if ($diferencia === 0) {
        return ['Hoy', 'estado-pendiente'];
    }
    if ($diferencia === 1) {
        return ['Mañana', 'estado-confirmado'];
    }

    return [date('d/m/Y', strtotime($fecha)), 'estado-empleado'];
}

$primernombre = explode(' ', trim($usuarioactual['nombre']))[0];
$listaencargos = $esduena ? $resumen['abiertos'] : $resumen['mios'];

// Accesos rápidos: solo se muestran los que el rol puede abrir
$accesos = [
    ['Nuevo encargo', 'encargo', true],
    ['Ver encargos', 'encargo', false],
    ['Catálogo', 'catalogo', false],
    ['Nuevo pedido', 'pedido', true],
    ['Inventario', 'inventario', false],
    ['Empleados', 'empleado', false],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inicio | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Hola, <?= e($primernombre) ?></h1>
            <p class="subtitulo">Hoy es <?= e(fechalarga($resumen['hoy'], $dias, $meses)) ?>.</p>
        </div>
    </header>

    <section class="resumen-inicio" aria-label="Resumen">
        <a class="tarjeta-resumen" href="index.php?modulo=encargo">
            <span class="numero"><?= count($resumen['mios']) ?></span>
            <span>Mis encargos abiertos</span>
        </a>
        <a class="tarjeta-resumen" href="index.php?modulo=encargo">
            <span class="numero"><?= count($resumen['sinasignar']) ?></span>
            <span>Encargos sin asignar</span>
        </a>
        <a class="tarjeta-resumen<?= $resumen['atrasados'] ? ' tarjeta-alerta' : '' ?>" href="index.php?modulo=encargo">
            <span class="numero"><?= count($resumen['atrasados']) ?></span>
            <span><?= $esduena ? 'Encargos atrasados' : 'Mis encargos atrasados' ?></span>
        </a>
        <?php if ($esduena): ?>
            <a class="tarjeta-resumen<?= $resumen['stockbajo'] ? ' tarjeta-alerta' : '' ?>" href="index.php?modulo=inventario">
                <span class="numero"><?= count($resumen['stockbajo']) ?></span>
                <span>Productos con stock bajo</span>
            </a>
            <a class="tarjeta-resumen" href="index.php?modulo=pedido">
                <span class="numero"><?= count($resumen['pedidospendientes']) + count($resumen['pedidosporrecibir']) ?></span>
                <span>Pedidos sin recibir</span>
            </a>
        <?php endif; ?>
    </section>

    <section class="accesos-inicio" aria-label="Accesos rápidos">
        <?php foreach ($accesos as [$texto, $destino, $nuevo]): ?>
            <?php if (Seguridad::permitido($destino)): ?>
                <a class="boton <?= $nuevo ? 'boton-principal' : '' ?>" href="index.php?modulo=<?= e($destino) ?><?= $nuevo ? '&amp;nuevo=1' : '' ?>"><?= e($texto) ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </section>

    <div class="paneles-inicio">
        <section class="panel-inicio">
            <h2><?= $esduena ? 'Encargos por entregar' : 'Mis encargos por entregar' ?></h2>
            <?php if (!$listaencargos): ?>
                <p class="texto-suave"><?= $esduena ? 'No hay encargos abiertos.' : 'No tiene encargos asignados.' ?></p>
            <?php else: ?>
                <ul class="lista-inicio">
                    <?php foreach (array_slice($listaencargos, 0, 6) as $encargo): ?>
                        <?php [$entrega, $claseentrega] = textoentrega($encargo['tbencargofechaentrega'], $resumen['hoy']); ?>
                        <li>
                            <span>
                                <strong><?= (int) $encargo['tbencargocantidad'] ?> × <?= e($encargo['tbarreglofloralnombre']) ?></strong>
                                <small class="texto-suave">
                                    <?= e($encargo['tbencargoestado']) ?>
                                    <?php if ($esduena): ?>
                                        · <?= $encargo['tbempleadonombre'] !== '' ? e($encargo['tbempleadonombre']) : 'Sin asignar' ?>
                                    <?php endif; ?>
                                </small>
                            </span>
                            <span class="estado <?= $claseentrega ?>"><?= e($entrega) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <?php if (!$esduena): ?>
            <section class="panel-inicio">
                <h2>Encargos que puede tomar</h2>
                <?php if (!$resumen['sinasignar']): ?>
                    <p class="texto-suave">No hay encargos sin asignar.</p>
                <?php else: ?>
                    <ul class="lista-inicio">
                        <?php foreach (array_slice($resumen['sinasignar'], 0, 6) as $encargo): ?>
                            <?php [$entrega, $claseentrega] = textoentrega($encargo['tbencargofechaentrega'], $resumen['hoy']); ?>
                            <li>
                                <strong><?= (int) $encargo['tbencargocantidad'] ?> × <?= e($encargo['tbarreglofloralnombre']) ?></strong>
                                <span class="estado <?= $claseentrega ?>"><?= e($entrega) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="enlace-panel" href="index.php?modulo=encargo">Ir a encargos para asignárselo</a>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="panel-inicio">
                <h2>Productos con stock bajo</h2>
                <?php if (!$resumen['stockbajo']): ?>
                    <p class="texto-suave">Todo el inventario está por encima del mínimo.</p>
                <?php else: ?>
                    <ul class="lista-inicio">
                        <?php foreach (array_slice($resumen['stockbajo'], 0, 6) as $producto): ?>
                            <li>
                                <span>
                                    <strong><?= e($producto['nombre']) ?></strong>
                                    <small class="texto-suave">Mínimo <?= $producto['minimo'] ?></small>
                                </span>
                                <span class="stock stock-bajo"><?= $producto['stock'] ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="enlace-panel" href="index.php?modulo=pedido&amp;nuevo=1">Hacer un pedido</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<script src="js/menu.js"></script>
</body>
</html>