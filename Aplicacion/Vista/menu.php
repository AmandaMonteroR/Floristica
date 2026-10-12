<?php
$moduloactual = $modulo;

// Estructura del menú: una opción tiene 'modulo' (enlace) o 'hijos' (submenú)
$opcionesmenu = [
    ['texto' => 'Inicio', 'modulo' => 'inicio'],
    ['texto' => 'Usuarios', 'hijos' => [
        ['texto' => 'Clientes', 'hijos' => [
            ['texto' => 'Agregar nuevo cliente', 'modulo' => 'cliente', 'nuevo' => true],
            ['texto' => 'Lista de clientes', 'modulo' => 'cliente'],
        ]],
        ['texto' => 'Empleados', 'hijos' => [
            ['texto' => 'Agregar nuevo empleado', 'modulo' => 'empleado', 'nuevo' => true],
            ['texto' => 'Lista de empleados', 'modulo' => 'empleado'],
        ]],
    ]],
    ['texto' => 'Ventas', 'hijos' => [
        ['texto' => 'Cotizaciones', 'modulo' => 'cotizacion'],
        ['texto' => 'Catálogo de arreglos', 'modulo' => 'catalogo'],
        ['texto' => 'Encargos', 'modulo' => 'encargo'],
    ]],
    ['texto' => 'Compras', 'hijos' => [
        ['texto' => 'Proveedores', 'hijos' => [
            ['texto' => 'Agregar nuevo proveedor', 'modulo' => 'proveedor', 'nuevo' => true],
            ['texto' => 'Lista de proveedores', 'modulo' => 'proveedor'],
        ]],
        ['texto' => 'Pedidos', 'modulo' => 'pedido'],
    ]],
    ['texto' => 'Inventario', 'hijos' => [
        ['texto' => 'Agregar nuevo producto', 'modulo' => 'inventario', 'nuevo' => true],
        ['texto' => 'Lista de productos', 'modulo' => 'inventario'],
    ]],
    ['texto' => 'Reportes', 'modulo' => 'reporte'],
];

// Quita las opciones que el rol del usuario no puede abrir (y los grupos que quedan vacíos)
function filtrarmenu($opciones)
{
    $permitidas = [];

    foreach ($opciones as $opcion) {
        if (isset($opcion['hijos'])) {
            $opcion['hijos'] = filtrarmenu($opcion['hijos']);
            if ($opcion['hijos']) {
                $permitidas[] = $opcion;
            }
        } elseif (Seguridad::permitido($opcion['modulo'])) {
            $permitidas[] = $opcion;
        }
    }

    return $permitidas;
}

$opcionesmenu = filtrarmenu($opcionesmenu);

// Indica si una opción (o alguno de sus hijos) corresponde a la página actual
function menucontieneactual($opcion, $moduloactual)
{
    if (isset($opcion['modulo'])) {
        return $opcion['modulo'] === $moduloactual;
    }

    foreach ($opcion['hijos'] as $hijo) {
        if (menucontieneactual($hijo, $moduloactual)) {
            return true;
        }
    }

    return false;
}

function dibujarmenu($opciones, $moduloactual, $nivel = 1)
{
    echo '<ul class="menu-nivel menu-nivel-' . $nivel . '">';

    foreach ($opciones as $indice => $opcion) {
        $actual = menucontieneactual($opcion, $moduloactual);

        if (isset($opcion['hijos'])) {
            $id = 'submenu-' . $nivel . '-' . $indice . '-' . md5($opcion['texto']);
            echo '<li class="menu-grupo' . ($actual ? ' contiene-actual' : '') . '">';
            echo '<button type="button" class="menu-grupo-boton" aria-expanded="false" aria-controls="' . $id . '">'
                . e($opcion['texto']) . '<span class="flecha" aria-hidden="true"></span></button>';
            echo '<div class="submenu" id="' . $id . '">';
            dibujarmenu($opcion['hijos'], $moduloactual, $nivel + 1);
            echo '</div>';
            echo '</li>';
        } else {
            $url = 'index.php?modulo=' . $opcion['modulo'] . (!empty($opcion['nuevo']) ? '&nuevo=1' : '');
            $clase = $actual && empty($opcion['nuevo']) ? ' class="activo" aria-current="page"' : '';
            echo '<li><a href="' . e($url) . '"' . $clase . '>' . e($opcion['texto']) . '</a></li>';
        }
    }

    echo '</ul>';
}
?>
<header class="barra-superior">
    <button type="button" class="boton-menu" id="botonmenu" aria-label="Abrir menú" aria-expanded="false" aria-controls="menulateral">
        <span></span><span></span><span></span>
    </button>
    <a class="marca" href="index.php">
        <img class="logo" src="imagenes/logo.png" alt="">
        <span>Floristica</span>
    </a>
    <div class="usuario-sesion">
        <span class="usuario-datos">
            <strong><?= e($usuarioactual['nombre']) ?></strong>
            <small><?= e($usuarioactual['rol']) ?></small>
        </span>
        <a class="boton-salir" href="index.php?modulo=sesion&amp;accion=cerrar">Cerrar sesión</a>
    </div>
</header>

<script>
    // Datos del usuario para ajustar la pantalla. Los permisos reales los revisa el servidor.
    const usuarioactual = <?= json_encode(['id' => $usuarioactual['id'], 'rol' => $usuarioactual['rol']], JSON_UNESCAPED_UNICODE) ?>;
</script>

<div class="fondo-menu" id="fondomenu" hidden></div>

<nav class="menu-lateral" id="menulateral" aria-label="Menú principal">
    <div class="menu-lateral-encabezado">
        <span class="marca">
            <img class="logo" src="imagenes/logo.png" alt="">
            <span>Floristica</span>
        </span>
        <button type="button" class="boton-cerrar-menu" id="botoncerrarmenu" aria-label="Cerrar menú">&times;</button>
    </div>
    <?php dibujarmenu($opcionesmenu, $moduloactual); ?>
</nav>