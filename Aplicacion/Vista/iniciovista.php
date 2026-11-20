<?php
require_once __DIR__ . '/../Controlador/iniciocontrolador.php';

$resumen = (new iniciocontrolador())->resumen($usuarioactual);
$espropietario = Seguridad::espropietario();

$meses = [
    'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'
];

$dias = [
    'domingo', 'lunes', 'martes', 'miércoles',
    'jueves', 'viernes', 'sábado'
];

function fechalarga($fecha, $dias, $meses)
{
    $tiempo = strtotime($fecha);

    return $dias[date('w', $tiempo)] . ' '
        . date('j', $tiempo) . ' de '
        . $meses[date('n', $tiempo) - 1];
}

function textoentrega($fecha, $hoy)
{
    if ($fecha < $hoy) {
        return ['Atrasado', 'estado-bajo'];
    }

    if ($fecha === $hoy) {
        return ['Hoy', 'estado-pendiente'];
    }

    return [
        date('d/m/Y', strtotime($fecha)),
        'estado-confirmado'
    ];
}

$primernombre = explode(' ', trim($usuarioactual['nombre']))[0];
$hoy = $resumen['hoy'];
$idusuario = (int) $usuarioactual['id'];

$enlaces = [
    'mios' => 'index.php?modulo=encargo&estado=abiertos&empleado=' . $idusuario,
    'sinasignar' => 'index.php?modulo=encargo&estado=abiertos&empleado=sinasignar',
    'atrasados' => 'index.php?modulo=encargo&estado=atrasados'
        . ($espropietario ? '' : '&empleado=' . $idusuario),
    'stockbajo' => 'index.php?modulo=inventario&estado=1&stock=bajo',
    'abiertos' => 'index.php?modulo=encargo&estado=abiertos'
        . ($espropietario ? '' : '&empleado=' . $idusuario),
    'hoy' => 'index.php?modulo=encargo&estado=abiertos&periodo=hoy'
        . ($espropietario ? '' : '&empleado=' . $idusuario),
];

$listaencargos = $espropietario
    ? $resumen['abiertos']
    : $resumen['mios'];

$encargoshoy = [];
$encargosatrasados = [];
$encargosproximos = [];

foreach ($listaencargos as $encargo) {
    $fechaentrega = $encargo['tbsolicitudencargofechaentrega'];

    if ($fechaentrega === $hoy) {
        $encargoshoy[] = $encargo;
    } elseif ($fechaentrega < $hoy) {
        $encargosatrasados[] = $encargo;
    } else {
        $encargosproximos[] = $encargo;
    }
}

usort($encargosproximos, function ($a, $b) {
    return strcmp(
        $a['tbsolicitudencargofechaentrega'],
        $b['tbsolicitudencargofechaentrega']
    );
});

$accesos = [
    ['Nuevo encargo', 'encargo', true],
    ['Ver encargos', 'encargo', false],
    ['Clientes', 'cliente', false],
    ['Catálogo', 'catalogo', false],
];

$totalhoy = count($encargoshoy);
$totalatrasados = count($encargosatrasados);
$totalsinasignar = count($resumen['sinasignar']);
$totalstockbajo = $espropietario
    ? count($resumen['stockbajo'])
    : 0;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Inicio | Floristica</title>

    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
</head>

<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">

    <header class="encabezado">
        <div>
            <h1>Hola, <?= e($primernombre) ?></h1>
            <p class="subtitulo">
                <?= e(fechalarga($hoy, $dias, $meses)) ?>
            </p>
        </div>
    </header>

    <section class="resumen-inicio" aria-label="Resumen de hoy">

        <a class="tarjeta-resumen"
           href="<?= e($enlaces['hoy']) ?>">

            <span class="numero"><?= $totalhoy ?></span>
            <span>Encargos para hoy</span>
        </a>

        <?php if ($totalatrasados > 0): ?>
            <a class="tarjeta-resumen tarjeta-alerta"
               href="<?= e($enlaces['atrasados']) ?>">

                <span class="numero"><?= $totalatrasados ?></span>
                <span>Encargos atrasados</span>
            </a>
        <?php endif; ?>

        <?php if (!$espropietario && $totalsinasignar > 0): ?>
            <a class="tarjeta-resumen"
               href="<?= e($enlaces['sinasignar']) ?>">

                <span class="numero"><?= $totalsinasignar ?></span>
                <span>Sin asignar</span>
            </a>
        <?php endif; ?>

        <?php if ($espropietario && $totalstockbajo > 0): ?>
            <a class="tarjeta-resumen tarjeta-alerta"
               href="<?= e($enlaces['stockbajo']) ?>">

                <span class="numero"><?= $totalstockbajo ?></span>
                <span>Stock bajo</span>
            </a>
        <?php endif; ?>

    </section>

    <section class="accesos-inicio" aria-label="Accesos rápidos">

        <?php foreach ($accesos as [$texto, $destino, $nuevo]): ?>
            <?php if (Seguridad::permitido($destino)): ?>

                <a class="boton <?= $nuevo ? 'boton-principal' : '' ?>"
                   href="index.php?modulo=<?= e($destino) ?><?= $nuevo ? '&amp;nuevo=1' : '' ?>">

                    <?= e($texto) ?>
                </a>

            <?php endif; ?>
        <?php endforeach; ?>

    </section>

    <div class="paneles-inicio">

        <section class="panel-inicio">

            <h2>
                <?= $espropietario
                    ? 'Entregas de hoy'
                    : 'Mis entregas de hoy' ?>
            </h2>

            <?php if (!$encargoshoy): ?>

                <p class="texto-suave">
                    No hay entregas pendientes para hoy.
                </p>

            <?php else: ?>

                <ul class="lista-inicio">

                    <?php foreach (array_slice($encargoshoy, 0, 5) as $encargo): ?>

                        <li>
                            <a class="enlace-inicio"
                               href="<?= e($enlaces['abiertos'] . '&ver=' . (int) $encargo['tbsolicitudencargoid']) ?>">

                                <span>
                                    <strong>
                                        <?= e($encargo['descripcion']) ?>
                                    </strong>

                                    <small class="texto-suave">
                                        <?= e($encargo['tbclientenombre']) ?>
                                        ·
                                        <?= e($encargo['tbsolicitudencargoestado']) ?>

                                        <?php if ($espropietario): ?>
                                            ·
                                            <?= $encargo['tbempleadonombre'] !== ''
                                                ? e($encargo['tbempleadonombre'])
                                                : 'Sin asignar' ?>
                                        <?php endif; ?>
                                    </small>
                                </span>

                                <span class="estado estado-pendiente">
                                    Hoy
                                </span>

                            </a>
                        </li>

                    <?php endforeach; ?>

                </ul>

                <?php if ($totalhoy > 5): ?>
                    <a class="enlace-panel"
                       href="<?= e($enlaces['hoy']) ?>">

                        Ver todos los encargos de hoy
                    </a>
                <?php endif; ?>

            <?php endif; ?>

        </section>

        <section class="panel-inicio">

            <h2>Próximas entregas</h2>

            <?php if (!$encargosproximos): ?>

                <p class="texto-suave">
                    No hay próximas entregas pendientes.
                </p>

            <?php else: ?>

                <ul class="lista-inicio">

                    <?php foreach (array_slice($encargosproximos, 0, 3) as $encargo): ?>

                        <?php
                        [$entrega, $claseentrega] = textoentrega(
                            $encargo['tbsolicitudencargofechaentrega'],
                            $hoy
                        );
                        ?>

                        <li>
                            <a class="enlace-inicio"
                               href="<?= e($enlaces['abiertos'] . '&ver=' . (int) $encargo['tbsolicitudencargoid']) ?>">

                                <span>
                                    <strong>
                                        <?= e($encargo['descripcion']) ?>
                                    </strong>

                                    <small class="texto-suave">
                                        <?= e($encargo['tbclientenombre']) ?>
                                    </small>
                                </span>

                                <span class="estado <?= e($claseentrega) ?>">
                                    <?= e($entrega) ?>
                                </span>

                            </a>
                        </li>

                    <?php endforeach; ?>

                </ul>

                <a class="enlace-panel"
                   href="<?= e($enlaces['abiertos']) ?>">
                    Ver encargos
                </a>

            <?php endif; ?>

        </section>

    </div>

</main>

<script src="<?= recurso('js/menu.js') ?>"></script>
</body>
</html>