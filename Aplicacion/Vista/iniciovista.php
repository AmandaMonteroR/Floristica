<?php
require_once __DIR__ . '/../Controlador/iniciocontrolador.php';

$resumen = (new iniciocontrolador())->resumen($usuarioactual);
$espropietario = Seguridad::espropietario();

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

// Cada tarjeta abre la lista con el filtro que corresponde a lo que cuenta
$idusuario = (int) $usuarioactual['id'];
$enlaces = [
    'mios'       => 'index.php?modulo=encargo&estado=abiertos&empleado=' . $idusuario,
    'sinasignar' => 'index.php?modulo=encargo&estado=abiertos&empleado=sinasignar',
    'atrasados'  => 'index.php?modulo=encargo&estado=atrasados' . ($espropietario ? '' : '&empleado=' . $idusuario),
    'stockbajo'  => 'index.php?modulo=inventario&estado=1&stock=bajo',
    'sinrecibir' => 'index.php?modulo=pedido&estado=sinrecibir',
    'abiertos'   => 'index.php?modulo=encargo&estado=abiertos' . ($espropietario ? '' : '&empleado=' . $idusuario),
];
$listaencargos = $espropietario ? $resumen['abiertos'] : $resumen['mios'];

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
        <a class="tarjeta-resumen" href="<?= e($enlaces['mios']) ?>">
            <span class="numero"><?= count($resumen['mios']) ?></span>
            <span>Mis encargos abiertos</span>
        </a>
        <a class="tarjeta-resumen" href="<?= e($enlaces['sinasignar']) ?>">
            <span class="numero"><?= count($resumen['sinasignar']) ?></span>
            <span>Encargos sin asignar</span>
        </a>
        <a class="tarjeta-resumen<?= $resumen['atrasados'] ? ' tarjeta-alerta' : '' ?>" href="<?= e($enlaces['atrasados']) ?>">
            <span class="numero"><?= count($resumen['atrasados']) ?></span>
            <span><?= $espropietario ? 'Encargos atrasados' : 'Mis encargos atrasados' ?></span>
        </a>
        <?php if ($espropietario): ?>
            <a class="tarjeta-resumen<?= $resumen['stockbajo'] ? ' tarjeta-alerta' : '' ?>" href="<?= e($enlaces['stockbajo']) ?>">
                <span class="numero"><?= count($resumen['stockbajo']) ?></span>
                <span>Productos con stock bajo</span>
            </a>
            <a class="tarjeta-resumen" href="<?= e($enlaces['sinrecibir']) ?>">
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
            <h2><?= $espropietario ? 'Encargos por entregar' : 'Mis encargos por entregar' ?></h2>
            <?php if (!$listaencargos): ?>
                <p class="texto-suave"><?= $espropietario ? 'No hay encargos abiertos.' : 'No tiene encargos asignados.' ?></p>
            <?php else: ?>
                <ul class="lista-inicio">
                    <?php foreach (array_slice($listaencargos, 0, 6) as $encargo): ?>
                        <?php [$entrega, $claseentrega] = textoentrega($encargo['tbsolicitudencargofechaentrega'], $resumen['hoy']); ?>
                        <li>
                            <span>
                                <strong><?= (int) $encargo['tbsolicitudencargocantidad'] ?> × <?= e($encargo['tbarreglofloralnombre']) ?></strong>
                                <small class="texto-suave">
                                    <?= e($encargo['tbsolicitudencargoestado']) ?>
                                    <?php if ($espropietario): ?>
                                        · <?= $encargo['tbempleadonombre'] !== '' ? e($encargo['tbempleadonombre']) : 'Sin asignar' ?>
                                    <?php endif; ?>
                                </small>
                            </span>
                            <span class="estado <?= $claseentrega ?>"><?= e($entrega) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($listaencargos) > 6): ?>
                    <a class="enlace-panel" href="<?= e($enlaces['abiertos']) ?>">Ver los <?= count($listaencargos) ?> encargos</a>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if (!$espropietario): ?>
            <section class="panel-inicio">
                <h2>Encargos que puede tomar</h2>
                <?php if (!$resumen['sinasignar']): ?>
                    <p class="texto-suave">No hay encargos sin asignar.</p>
                <?php else: ?>
                    <ul class="lista-inicio">
                        <?php foreach (array_slice($resumen['sinasignar'], 0, 6) as $encargo): ?>
                            <?php [$entrega, $claseentrega] = textoentrega($encargo['tbsolicitudencargofechaentrega'], $resumen['hoy']); ?>
                            <li>
                                <strong><?= (int) $encargo['tbsolicitudencargocantidad'] ?> × <?= e($encargo['tbarreglofloralnombre']) ?></strong>
                                <span class="estado <?= $claseentrega ?>"><?= e($entrega) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="enlace-panel" href="<?= e($enlaces['sinasignar']) ?>">Ir a encargos para asignárselo</a>
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
                    <a class="enlace-panel" href="<?= e($enlaces['stockbajo']) ?>">Ver los productos con stock bajo</a>
                    <a class="enlace-panel" href="index.php?modulo=pedido&amp;nuevo=1">Hacer un pedido</a>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<script src="js/menu.js"></script>
</body>
</html>