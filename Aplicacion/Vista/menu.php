<?php
$moduloactual = $_GET['modulo'] ?? 'proveedor';
$opcionesmenu = [
    'inicio'     => 'Inicio',
    'cliente'    => 'Clientes',
    'inventario' => 'Inventario',
    'catalogo'   => 'Catálogo',
    'cotizacion' => 'Cotizaciones',
    'pedido'     => 'Pedidos',
    'proveedor'  => 'Proveedores',
    'reporte'    => 'Reportes',
    'usuario'    => 'Usuarios',
];
?>
<header class="barra-superior">
    <button type="button" class="boton-menu" id="botonmenu" aria-label="Abrir menú" aria-expanded="false" aria-controls="menulateral">
        <span></span><span></span><span></span>
    </button>
    <a class="marca" href="index.php">
        <img class="logo" src="imagenes/logo.png" alt="">
        <span>Floristica</span>
    </a>
</header>

<div class="fondo-menu" id="fondomenu" hidden></div>

<nav class="menu-lateral" id="menulateral" aria-label="Menú principal">
    <div class="menu-lateral-encabezado">
        <span class="marca">
            <img class="logo" src="imagenes/logo.png" alt="">
            <span>Floristica</span>
        </span>
        <button type="button" class="boton-cerrar-menu" id="botoncerrarmenu" aria-label="Cerrar menú">&times;</button>
    </div>
    <ul>
        <?php foreach ($opcionesmenu as $clave => $nombre): ?>
            <li>
                <a href="index.php?modulo=<?= $clave ?>" class="<?= $clave === $moduloactual ? 'activo' : '' ?>"><?= $nombre ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>