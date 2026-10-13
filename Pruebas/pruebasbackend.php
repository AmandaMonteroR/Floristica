<?php
/*
 * Pruebas automáticas del backend de Floristica.
 *
 * Hace peticiones reales al servidor (como lo haría el navegador) y revisa
 * las respuestas: validaciones, permisos por rol, cambios de estado, stock e históricos.
 *
 * Uso (desde la carpeta raíz del proyecto, con el servidor encendido):
 *     php Pruebas/pruebasbackend.php http://localhost:8000
 *
 * OJO: las pruebas crean y cambian datos. Antes de correrlas, cargue la base
 * con bdfloristica.sql y datosprueba.sql, y vuelva a cargarla al terminar.
 */

date_default_timezone_set('America/Costa_Rica');

$servidor = rtrim($argv[1] ?? 'http://localhost:8000', '/');
$hoy = date('Y-m-d');
$manana = date('Y-m-d', strtotime('+1 day'));
$ayer = date('Y-m-d', strtotime('-1 day'));
$fotoprueba = __DIR__ . '/../Publico/imagenes/muestras/arreglo_rosas_rojas.jpg';

/* ---------------------------------------------------------------------
 * Cliente HTTP con su propia sesión (cookie), sin necesitar la extensión curl
 * ------------------------------------------------------------------- */
class Cliente
{
    private $url;
    private $cookie = '';

    public function __construct($servidor)
    {
        $this->url = $servidor . '/index.php';
    }

    public function get($modulo, $accion = '', $parametros = [])
    {
        $consulta = http_build_query(['modulo' => $modulo] + ($accion !== '' ? ['accion' => $accion] : []) + $parametros);
        return $this->enviar('GET', $this->url . '?' . $consulta);
    }

    public function post($modulo, $accion, $datos = [], $archivos = [])
    {
        $url = $this->url . '?' . http_build_query(['modulo' => $modulo, 'accion' => $accion]);

        if (!$archivos) {
            return $this->enviar('POST', $url, http_build_query($datos), 'application/x-www-form-urlencoded');
        }

        // Formulario con archivo (multipart), como cuando se sube la foto de un arreglo
        $limite = '----floristica' . bin2hex(random_bytes(8));
        $cuerpo = '';
        foreach ($this->aplanar($datos) as $nombre => $valor) {
            $cuerpo .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$nombre}\"\r\n\r\n{$valor}\r\n";
        }
        foreach ($archivos as $nombre => $ruta) {
            $tipo = mime_content_type($ruta);
            $cuerpo .= "--{$limite}\r\nContent-Disposition: form-data; name=\"{$nombre}\"; filename=\"" . basename($ruta) . "\"\r\n"
                . "Content-Type: {$tipo}\r\n\r\n" . file_get_contents($ruta) . "\r\n";
        }
        $cuerpo .= "--{$limite}--\r\n";

        return $this->enviar('POST', $url, $cuerpo, 'multipart/form-data; boundary=' . $limite);
    }

    public function ingresar($usuario, $clave)
    {
        return $this->post('sesion', 'ingresar', ['usuario' => $usuario, 'clave' => $clave]);
    }

    // ['detalles' => [['a' => 1]]] -> ['detalles[0][a]' => 1]
    private function aplanar($datos, $prefijo = '')
    {
        $plano = [];
        foreach ($datos as $clave => $valor) {
            $nombre = $prefijo === '' ? $clave : "{$prefijo}[{$clave}]";
            if (is_array($valor)) {
                $plano += $this->aplanar($valor, $nombre);
            } else {
                $plano[$nombre] = $valor;
            }
        }
        return $plano;
    }

    private function enviar($metodo, $url, $cuerpo = null, $tipo = null)
    {
        $cabeceras = [];
        if ($this->cookie !== '') {
            $cabeceras[] = 'Cookie: ' . $this->cookie;
        }
        if ($tipo !== null) {
            $cabeceras[] = 'Content-Type: ' . $tipo;
        }

        $contexto = stream_context_create(['http' => [
            'method'          => $metodo,
            'header'          => implode("\r\n", $cabeceras),
            'content'         => $cuerpo ?? '',
            'ignore_errors'   => true,
            'follow_location' => 0,
            'timeout'         => 20,
        ]]);

        $texto = @file_get_contents($url, false, $contexto);
        if ($texto === false) {
            fwrite(STDERR, "\nNo se pudo conectar con {$url}. ¿Está encendido el servidor (php -S) y MySQL?\n");
            exit(2);
        }

        $codigo = 0;
        foreach ($http_response_header as $linea) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $linea, $coincidencia)) {
                $codigo = (int) $coincidencia[1];
            }
            if (stripos($linea, 'Set-Cookie: floristica=') === 0) {
                $this->cookie = explode(';', substr($linea, 12))[0];
            }
        }

        return ['codigo' => $codigo, 'json' => json_decode($texto, true), 'texto' => $texto];
    }
}

/* ---------------------------------------------------------------------
 * Ayudas para revisar resultados
 * ------------------------------------------------------------------- */
$total = 0;
$fallas = [];
$seccionactual = '';

function seccion($titulo)
{
    global $seccionactual;
    $seccionactual = $titulo;
    echo "\n\033[1m== {$titulo} ==\033[0m\n";
}

function prueba($nombre, $condicion, $detalle = '')
{
    global $total, $fallas, $seccionactual;
    $total++;

    if ($condicion) {
        echo "  \033[32m✓\033[0m {$nombre}\n";
        return;
    }

    echo "  \033[31m✗ {$nombre}\033[0m" . ($detalle !== '' ? "\n      → {$detalle}" : '') . "\n";
    $fallas[] = "[{$seccionactual}] {$nombre}";
}

function exito($r)
{
    return ($r['json']['exito'] ?? false) === true;
}

function errores($r)
{
    return $r['json']['datos']['errores'] ?? [];
}

function mensaje($r)
{
    return $r['json']['mensaje'] ?? ('HTTP ' . $r['codigo'] . ' ' . substr(strip_tags($r['texto']), 0, 150));
}

function tieneerror($r, $campo)
{
    return !exito($r) && isset(errores($r)[$campo]);
}

function datos($r)
{
    return $r['json']['datos'] ?? [];
}

/* =====================================================================
 * 1. Sesión y seguridad
 * =================================================================== */
seccion('1. Sesión y seguridad');

$anonimo = new Cliente($servidor);
$r = $anonimo->get('proveedor', 'listar');
prueba('Sin sesión, pedir datos responde 401', $r['codigo'] === 401, 'HTTP ' . $r['codigo']);

$r = $anonimo->get('');
prueba('Sin sesión, la página principal muestra el login', $r['codigo'] === 200 && strpos($r['texto'], 'name="usuario"') !== false);

$r = $anonimo->ingresar('duena', 'malaclave1');
prueba('Contraseña incorrecta no deja entrar', !exito($r) && mensaje($r) === 'Usuario o contraseña incorrectos.', mensaje($r));

$r = $anonimo->ingresar('noexiste', 'cualquiera1');
prueba('Usuario inexistente da el mismo mensaje (no revela cuál falló)', mensaje($r) === 'Usuario o contraseña incorrectos.', mensaje($r));

$r = (new Cliente($servidor))->ingresar('', '');
prueba('Usuario y contraseña vacíos piden completar', isset(errores($r)['usuario'], errores($r)['clave']), mensaje($r));

$r = (new Cliente($servidor))->ingresar('empleado4', 'empleado4');
prueba('Empleada inactiva no puede entrar', !exito($r) && strpos(mensaje($r), 'inactiva') !== false, mensaje($r));

$bloqueo = new Cliente($servidor);
for ($i = 0; $i < 5; $i++) {
    $bloqueo->ingresar('duena', 'otraclave' . $i);
}
$r = $bloqueo->ingresar('duena', 'duena1234');
prueba('Después de 5 intentos fallidos se bloquea, aunque la clave sea correcta', !exito($r) && strpos(mensaje($r), 'Demasiados intentos') !== false, mensaje($r));

$duena = new Cliente($servidor);
$r = $duena->ingresar('duena', 'duena1234');
prueba('La dueña entra con duena / duena1234', exito($r), mensaje($r));

// Las pruebas cuentan con los datos de prueba recién cargados
if (exito($r) && count(datos($duena->get('proveedor', 'listar'))) !== 8) {
    fwrite(STDERR, "\nLa base ya tiene datos de una corrida anterior. Vuelva a cargarla:\n"
        . "  /opt/lampp/bin/mysql -u root --default-character-set=utf8mb4 < BaseDatos/ScriptsSQL/bdfloristica.sql\n"
        . "  /opt/lampp/bin/mysql -u root --default-character-set=utf8mb4 < BaseDatos/DatosIniciales/datosprueba.sql\n");
    exit(2);
}

$r = $duena->get('proveedor', 'crear');
prueba('Una acción que modifica datos no acepta GET (405)', $r['codigo'] === 405, 'HTTP ' . $r['codigo']);

$r = $duena->get('noexiste', 'listar');
prueba('Módulo inexistente responde con error', !exito($r), mensaje($r));

$r = $duena->get('inicio');
prueba('La página de inicio carga para la dueña', $r['codigo'] === 200 && strpos($r['texto'], 'Productos con stock bajo') !== false);

$lorena = new Cliente($servidor);
prueba('Lorena entra con empleado1 / empleado1', exito($lorena->ingresar('empleado1', 'empleado1')));
$carlos = new Cliente($servidor);
prueba('Carlos entra con empleado2 / empleado2', exito($carlos->ingresar('empleado2', 'empleado2')));

/* =====================================================================
 * 2. Permisos de los empleados
 * =================================================================== */
seccion('2. Permisos de los empleados');

foreach ([['proveedor', 'listar'], ['inventario', 'listar'], ['pedido', 'listar'], ['empleado', 'listar'],
          ['catalogo', 'productos'], ['catalogo', 'historial']] as [$modulo, $accion]) {
    $r = $lorena->get($modulo, $accion, ['id' => 1]);
    prueba("Empleado no puede usar {$modulo} → {$accion} (403)", $r['codigo'] === 403, 'HTTP ' . $r['codigo']);
}

foreach ([['catalogo', 'crear'], ['catalogo', 'inactivar'], ['encargo', 'cancelar'], ['encargo', 'asignar'], ['encargo', 'actualizar']] as [$modulo, $accion]) {
    $r = $lorena->post($modulo, $accion, ['id' => 1]);
    prueba("Empleado no puede hacer {$modulo} → {$accion} (403)", $r['codigo'] === 403, 'HTTP ' . $r['codigo']);
}

$r = $lorena->get('inventario');
prueba('Empleado que abre la página de inventario ve "sin permiso"', $r['codigo'] === 403);

$r = $lorena->get('catalogo', 'listar');
$primero = datos($r)[0] ?? [];
prueba('Empleado sí ve el catálogo', exito($r) && count(datos($r)) > 0);
prueba('…pero sin el costo de los arreglos', !array_key_exists('costo', $primero), 'Llegó el costo: ' . json_encode($primero['costo'] ?? null));

$r = $lorena->get('catalogo', 'obtener', ['id' => 1]);
$detalle = datos($r)['detalles'][0] ?? [];
prueba('…ni el precio por unidad de los productos', !array_key_exists('tbinventariopreciounidad', $detalle));

$r = $duena->get('catalogo', 'obtener', ['id' => 1]);
prueba('La dueña sí ve el costo', isset(datos($r)['costo']));

/* =====================================================================
 * 3. Proveedores
 * =================================================================== */
seccion('3. Proveedores');

$proveedorvalido = [
    'tbproveedornombreempresa'  => 'Prueba Backend',
    'tbproveedornombrecontacto' => 'Ana Prueba',
    'tbproveedortelefono'       => '8777-1111',
    'tbproveedorcorreo'         => 'prueba@backend.cr',
    'tbproveedorprovincia'      => 'Heredia',
    'tbproveedorcanton'         => 'Barva',
    'tbproveedordistrito'       => 'San Pedro',
    'tbproveedordireccion'      => '',
    'tbproveedordescripcion'    => '',
];

$r = $duena->get('proveedor', 'listar');
prueba('Lista los 8 proveedores de prueba', count(datos($r)) === 8, 'Hay ' . count(datos($r)));

$r = $duena->post('proveedor', 'crear', []);
$faltan = array_keys(errores($r));
prueba('Formulario vacío marca los obligatorios',
    !array_diff(['tbproveedornombreempresa', 'tbproveedornombrecontacto', 'tbproveedortelefono', 'tbproveedorprovincia'], $faltan),
    'Errores: ' . implode(', ', $faltan));

$r = $duena->post('proveedor', 'crear', ['tbproveedornombreempresa' => 'Flores del Valle'] + $proveedorvalido);
prueba('No deja repetir el nombre de la empresa', tieneerror($r, 'tbproveedornombreempresa'), mensaje($r));

$r = $duena->post('proveedor', 'crear', ['tbproveedortelefono' => '8888-1001'] + $proveedorvalido);
prueba('No deja repetir el teléfono', tieneerror($r, 'tbproveedortelefono'), mensaje($r));

$r = $duena->post('proveedor', 'crear', ['tbproveedortelefono' => '1234'] + $proveedorvalido);
prueba('Teléfono nacional debe tener 8 dígitos', tieneerror($r, 'tbproveedortelefono'));

$r = $duena->post('proveedor', 'crear', ['tbproveedorcorreo' => 'correo-malo'] + $proveedorvalido);
prueba('Correo con formato inválido', tieneerror($r, 'tbproveedorcorreo'));

$r = $duena->post('proveedor', 'crear', ['tbproveedornombrecontacto' => 'Ana 123'] + $proveedorvalido);
prueba('Nombre del contacto no acepta números', tieneerror($r, 'tbproveedornombrecontacto'));

$r = $duena->post('proveedor', 'crear', ['tbproveedorcanton' => 'Liberia'] + $proveedorvalido);
prueba('El cantón debe pertenecer a la provincia', tieneerror($r, 'tbproveedorcanton'));

$r = $duena->post('proveedor', 'crear', $proveedorvalido);
$idproveedor = datos($r)['tbproveedorid'] ?? 0;
prueba('Crea un proveedor válido', exito($r) && $idproveedor > 0, mensaje($r));

$r = $duena->get('proveedor', 'historial', ['id' => $idproveedor]);
$historial = datos($r);
prueba('El historial guarda los 6 datos iniciales', count($historial) === 6, 'Hay ' . count($historial));
prueba('…con quién lo hizo (Patricia)', ($historial[0]['hechopor'] ?? '') === 'Patricia Mora Solano', $historial[0]['hechopor'] ?? 'vacío');

$r = $duena->get('proveedor', 'obtener', ['id' => $idproveedor]);
$actual = datos($r);
$r = $duena->post('proveedor', 'actualizar', array_merge($proveedorvalido, ['tbproveedorid' => $idproveedor, 'tbproveedortelefono' => '8777-2222']));
prueba('Actualiza el teléfono', exito($r), mensaje($r));
$historial = datos($duena->get('proveedor', 'historial', ['id' => $idproveedor]));
$ultimo = end($historial);
prueba('El cambio de teléfono queda en el historial', count($historial) === 7 && ($ultimo['anterior'] ?? '') === '8777-1111' && ($ultimo['nuevo'] ?? '') === '8777-2222',
    json_encode($ultimo, JSON_UNESCAPED_UNICODE));
prueba('El historial va del más antiguo al más reciente', ($historial[0]['fecha'] ?? '') <= ($ultimo['fecha'] ?? ''));

$r = $duena->post('proveedor', 'actualizar', array_merge($proveedorvalido, ['tbproveedorid' => $idproveedor, 'tbproveedortelefono' => '8777-2222']));
$historial = datos($duena->get('proveedor', 'historial', ['id' => $idproveedor]));
prueba('Guardar sin cambios no agrega nada al historial', count($historial) === 7, 'Hay ' . count($historial));

prueba('Inactiva el proveedor', exito($duena->post('proveedor', 'inactivar', ['id' => $idproveedor])));
$r = $duena->post('proveedor', 'inactivar', ['id' => $idproveedor]);
prueba('No deja inactivarlo dos veces', !exito($r), mensaje($r));
prueba('Lo reactiva', exito($duena->post('proveedor', 'reactivar', ['id' => $idproveedor])));

/* =====================================================================
 * 4. Inventario
 * =================================================================== */
seccion('4. Inventario');

$florvalida = [
    'tbproveedorid'                         => 1,
    'tbinventarionombre'                    => 'Tulipán de prueba',
    'tbinventariocategoria'                 => 'Flor',
    'tbinventariopreciopaquete'             => '10000',
    'tbinventariounidadespaquete'           => 20,
    'tbinventariostock'                     => 40,
    'tbinventariostockminimo'               => 10,
    'tbinventariocategoriaflortipo'         => 'Tulipán',
    'tbinventariocategoriaflorcolor'        => 'Amarillo',
    'tbinventariocategoriaflorlargotallo'   => 40,
    'tbinventariocategoriaflordiasduracion' => 7,
];

$r = $duena->post('inventario', 'crear', ['tbinventariocategoria' => ''] + $florvalida);
prueba('La categoría es obligatoria', tieneerror($r, 'tbinventariocategoria'));

$r = $duena->post('inventario', 'crear', ['tbinventarionombre' => 'Rosa roja'] + $florvalida);
prueba('No deja repetir el nombre con el mismo proveedor', tieneerror($r, 'tbinventarionombre'));

$r = $duena->post('inventario', 'crear', ['tbproveedorid' => 4] + $florvalida);
prueba('No deja usar un proveedor inactivo', tieneerror($r, 'tbproveedorid'));

$r = $duena->post('inventario', 'crear', ['tbinventariopreciopaquete' => '100.555'] + $florvalida);
prueba('El precio solo acepta 2 decimales', tieneerror($r, 'tbinventariopreciopaquete'));

$r = $duena->post('inventario', 'crear', ['tbinventariopreciopaquete' => '0'] + $florvalida);
prueba('El precio debe ser mayor que 0', tieneerror($r, 'tbinventariopreciopaquete'));

$r = $duena->post('inventario', 'crear', [
    'tbproveedorid' => 6, 'tbinventarionombre' => 'Caja prueba', 'tbinventariocategoria' => 'Base',
    'tbinventariopreciopaquete' => '5000', 'tbinventariounidadespaquete' => 5, 'tbinventariostock' => 5, 'tbinventariostockminimo' => 1,
    'tbinventariocategoriabasetipo' => 'Caja', 'tbinventariocategoriabasematerial' => 'Madera', 'tbinventariocategoriabasecolor' => 'Café',
    'tbinventariocategoriabaseforma' => 'Cuadrada', 'tbinventariocategoriabasemedida' => 'grande',
]);
prueba('La medida debe ir en centímetros (ej. 20 x 30)', tieneerror($r, 'tbinventariocategoriabasemedida'));

$r = $duena->post('inventario', 'crear', [
    'tbproveedorid' => 6, 'tbinventarionombre' => 'Chocolate prueba', 'tbinventariocategoria' => 'Extra',
    'tbinventariopreciopaquete' => '5000', 'tbinventariounidadespaquete' => 5, 'tbinventariostock' => 5, 'tbinventariostockminimo' => 1,
    'tbinventariocategoriaextratipo' => 'Chocolate', 'tbinventariocategoriaextramarca' => 'Prueba', 'tbinventariocategoriaextratamano' => 'Mediano',
    'tbinventariocategoriaextracolor' => 'Café', 'tbinventariocategoriaextracontenido' => '10 unidades', 'tbinventariocategoriaextrafechavencimiento' => '',
]);
prueba('Los chocolates piden fecha de vencimiento', tieneerror($r, 'tbinventariocategoriaextrafechavencimiento'));

$r = $duena->post('inventario', 'crear', $florvalida);
$idproducto = datos($r)['tbinventarioid'] ?? 0;
prueba('Crea un producto válido', exito($r) && $idproducto > 0, mensaje($r));

$producto = datos($duena->get('inventario', 'obtener', ['id' => $idproducto]));
prueba('Calcula el precio por unidad (10000 / 20 = 500)', (float) ($producto['tbinventariopreciounidad'] ?? 0) === 500.0, json_encode($producto['tbinventariopreciounidad'] ?? null));
prueba('Registra el movimiento "Inicial" de 40', ($producto['movimientos'][0]['tipo'] ?? '') === 'Inicial' && ($producto['movimientos'][0]['cantidad'] ?? 0) === 40);
prueba('El historial tiene los 5 datos iniciales', count(datos($duena->get('inventario', 'historial', ['id' => $idproducto]))) === 5);

$cambio = $florvalida + ['tbinventarioid' => $idproducto];
$cambio['tbinventariostock'] = 35;
$cambio['tbinventariopreciopaquete'] = '12000';
$r = $duena->post('inventario', 'actualizar', $cambio);
prueba('Actualiza stock y precio', exito($r), mensaje($r));

$producto = datos($duena->get('inventario', 'obtener', ['id' => $idproducto]));
$ultimomovimiento = end($producto['movimientos']);
prueba('Corregir el stock a mano queda como "Ajuste" de −5', ($ultimomovimiento['tipo'] ?? '') === 'Ajuste' && ($ultimomovimiento['cantidad'] ?? 0) === -5 && ($ultimomovimiento['stockfinal'] ?? 0) === 35,
    json_encode($ultimomovimiento, JSON_UNESCAPED_UNICODE));
$historial = datos($duena->get('inventario', 'historial', ['id' => $idproducto]));
$ultimo = end($historial);
prueba('El cambio de precio queda en el historial', ($ultimo['campo'] ?? '') === 'precio' && strpos($ultimo['nuevo'] ?? '', '12 000') !== false, json_encode($ultimo, JSON_UNESCAPED_UNICODE));

prueba('Inactiva el producto', exito($duena->post('inventario', 'inactivar', ['id' => $idproducto])));
prueba('Lo reactiva', exito($duena->post('inventario', 'reactivar', ['id' => $idproducto])));

/* =====================================================================
 * 5. Pedidos
 * =================================================================== */
seccion('5. Pedidos');

$stockantes = (int) datos($duena->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'];

$r = $duena->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $hoy]);
prueba('Un pedido sin productos no se guarda', tieneerror($r, 'detalles'));

$detalle = ['detalles' => [['tbinventarioid' => 1, 'tbpedidodetallecantidad' => 2, 'tbpedidodetalleprecio' => '12000']]];
$r = $duena->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $manana] + $detalle);
prueba('La fecha del pedido no puede ser futura', tieneerror($r, 'tbpedidofecha'));

$r = $duena->post('pedido', 'crear', ['tbproveedorid' => 4, 'tbpedidofecha' => $hoy] + $detalle);
prueba('No deja pedir a un proveedor inactivo', tieneerror($r, 'tbproveedorid'));

$r = $duena->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $hoy] + $detalle);
$idpedido = datos($r)['tbpedidoid'] ?? 0;
prueba('Crea un pedido válido (2 paquetes de rosa roja)', exito($r) && $idpedido > 0, mensaje($r));

$r = $duena->post('pedido', 'recibir', ['id' => $idpedido]);
prueba('No deja recibir un pedido sin confirmar', !exito($r), mensaje($r));

$r = $duena->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Crédito']);
prueba('A crédito pide el plazo', tieneerror($r, 'tbconfirmacioncompraplazodias'));

$r = $duena->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Crédito', 'tbconfirmacioncompraplazodias' => 400]);
prueba('El plazo no puede pasar de 365 días', tieneerror($r, 'tbconfirmacioncompraplazodias'));

prueba('Confirma el pedido de contado', exito($duena->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Contado'])));
prueba('No deja confirmarlo dos veces', !exito($duena->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Contado'])));

prueba('Recibe el pedido', exito($duena->post('pedido', 'recibir', ['id' => $idpedido])));
$producto = datos($duena->get('inventario', 'obtener', ['id' => 1]));
prueba('El stock de rosa roja sube 48 (2 paquetes × 24)', (int) $producto['tbinventariostock'] === $stockantes + 48, "Antes {$stockantes}, ahora " . $producto['tbinventariostock']);
$ultimomovimiento = end($producto['movimientos']);
prueba('Queda el movimiento "Entrada" del pedido', ($ultimomovimiento['tipo'] ?? '') === 'Entrada' && strpos($ultimomovimiento['motivo'] ?? '', "N.º {$idpedido}") !== false);

$historial = datos($duena->get('pedido', 'historial', ['id' => $idpedido]));
prueba('Historial del pedido: Pendiente → Confirmado → Recibido', array_column($historial, 'nuevo') === ['Pendiente', 'Confirmado', 'Recibido'], json_encode(array_column($historial, 'nuevo')));
prueba('No deja recibirlo dos veces', !exito($duena->post('pedido', 'recibir', ['id' => $idpedido])));

/* =====================================================================
 * 6. Catálogo
 * =================================================================== */
seccion('6. Catálogo');

$arreglovalido = [
    'tbarreglofloralnombre'      => 'Arreglo de prueba',
    'tbarreglofloraldescripcion' => 'Creado por las pruebas',
    'tbarreglofloralprecio'      => '25000',
    'detalles'                   => [
        ['tbinventarioid' => 1, 'tbarreglofloraldetallecantidad' => 6],   // rosa roja 6 × 500
        ['tbinventarioid' => 11, 'tbarreglofloraldetallecantidad' => 2],  // eucalipto 2 × 200
    ],
];

$r = $duena->post('catalogo', 'crear', $arreglovalido);
prueba('La foto es obligatoria al crear', tieneerror($r, 'tbarreglofloralfoto'));

$r = $duena->post('catalogo', 'crear', ['detalles' => []] + $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
prueba('Debe llevar al menos un producto', tieneerror($r, 'detalles'));

$r = $duena->post('catalogo', 'crear', ['tbarreglofloralnombre' => 'Ramo silvestre'] + $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
prueba('No deja repetir el nombre', tieneerror($r, 'tbarreglofloralnombre'));

$r = $duena->post('catalogo', 'crear', ['tbarreglofloralfoto_falsa' => 1] + $arreglovalido, ['tbarreglofloralfoto' => __FILE__]);
prueba('Rechaza un archivo que no es imagen', tieneerror($r, 'tbarreglofloralfoto'), mensaje($r));

$r = $duena->post('catalogo', 'crear', $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
$idarreglo = datos($r)['tbarreglofloralid'] ?? 0;
prueba('Crea un arreglo válido con foto', exito($r) && $idarreglo > 0, mensaje($r));

$arreglo = datos($duena->get('catalogo', 'obtener', ['id' => $idarreglo]));
prueba('Calcula el costo (6 × 500 + 2 × 200 = 3400)', (float) ($arreglo['costo'] ?? 0) === 3400.0, json_encode($arreglo['costo'] ?? null));
prueba('Guarda la foto en imagenes/arreglos', strpos($arreglo['tbarreglofloralfoto'] ?? '', 'imagenes/arreglos/') === 0);

$r = $duena->post('catalogo', 'actualizar', ['tbarreglofloralid' => $idarreglo, 'tbarreglofloralprecio' => '27000'] + $arreglovalido);
prueba('Actualiza el precio sin cambiar la foto', exito($r), mensaje($r));
$historial = datos($duena->get('catalogo', 'historial', ['id' => $idarreglo]));
$ultimo = end($historial);
prueba('El cambio de precio queda en el historial', ($ultimo['campo'] ?? '') === 'precio' && strpos($ultimo['nuevo'] ?? '', '27 000') !== false, json_encode($ultimo, JSON_UNESCAPED_UNICODE));

/* =====================================================================
 * 7. Encargos
 * =================================================================== */
seccion('7. Encargos');

$encargovalido = [
    'tbarreglofloralid'     => $idarreglo,
    'tbempleadoid'          => '',
    'tbencargocantidad'     => 2,
    'tbencargofechaentrega' => $manana,
    'tbencargonota'         => 'Prueba',
];

$r = $duena->post('encargo', 'crear', ['tbencargofechaentrega' => $ayer] + $encargovalido);
prueba('La fecha de entrega no puede ser anterior a hoy', tieneerror($r, 'tbencargofechaentrega'));

$r = $duena->post('encargo', 'crear', ['tbencargocantidad' => 0] + $encargovalido);
prueba('La cantidad debe ser de 1 a 99', tieneerror($r, 'tbencargocantidad'));

$r = $duena->post('encargo', 'crear', ['tbempleadoid' => 5] + $encargovalido);
prueba('No deja asignarlo a una empleada inactiva', tieneerror($r, 'tbempleadoid'));

$r = $lorena->post('encargo', 'crear', ['tbempleadoid' => 3] + $encargovalido);
prueba('Un empleado no puede crear un encargo asignado a otro', tieneerror($r, 'tbempleadoid'));

$r = $duena->post('encargo', 'crear', $encargovalido);
$idencargo = datos($r)['tbencargoid'] ?? 0;
prueba('Crea un encargo sin asignar', exito($r) && $idencargo > 0, mensaje($r));

$r = $duena->post('encargo', 'iniciar', ['id' => $idencargo]);
prueba('No deja iniciarlo sin asignar', !exito($r), mensaje($r));

$r = $duena->post('encargo', 'asignar', ['tbencargoid' => $idencargo, 'tbempleadoid' => 2]);
prueba('La dueña se lo asigna a Lorena', exito($r), mensaje($r));
$r = $duena->post('encargo', 'asignar', ['tbencargoid' => $idencargo, 'tbempleadoid' => 2]);
prueba('Asignarlo a la misma persona no duplica el historial', exito($r) && strpos(mensaje($r), 'ya estaba') !== false, mensaje($r));

$r = $carlos->post('encargo', 'iniciar', ['id' => $idencargo]);
prueba('Carlos no puede iniciar un encargo de Lorena', !exito($r), mensaje($r));
$r = $carlos->post('encargo', 'asignarme', ['id' => $idencargo]);
prueba('Carlos no puede quitárselo a Lorena con "Asignármelo"', !exito($r), mensaje($r));

prueba('Lorena lo inicia', exito($lorena->post('encargo', 'iniciar', ['id' => $idencargo])));

$stockrosa = (int) datos($duena->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'];
$stockeucalipto = (int) datos($duena->get('inventario', 'obtener', ['id' => 11]))['tbinventariostock'];
prueba('Lorena lo termina', exito($lorena->post('encargo', 'terminar', ['id' => $idencargo])));
$rosa = datos($duena->get('inventario', 'obtener', ['id' => 1]));
$eucalipto = datos($duena->get('inventario', 'obtener', ['id' => 11]));
prueba('Descuenta 12 rosas (6 por arreglo × 2)', (int) $rosa['tbinventariostock'] === $stockrosa - 12, "Antes {$stockrosa}, ahora " . $rosa['tbinventariostock']);
prueba('Descuenta 4 de eucalipto (2 × 2)', (int) $eucalipto['tbinventariostock'] === $stockeucalipto - 4);
$ultimomovimiento = end($rosa['movimientos']);
prueba('Queda la "Salida" con el nombre de Lorena', ($ultimomovimiento['tipo'] ?? '') === 'Salida' && ($ultimomovimiento['hechopor'] ?? '') === 'Lorena Bermúdez Badilla',
    json_encode($ultimomovimiento, JSON_UNESCAPED_UNICODE));

$historial = datos($duena->get('encargo', 'obtener', ['id' => $idencargo]))['historial'] ?? [];
prueba('El historial del encargo tiene registro, asignación, inicio y fin', count($historial) === 4, json_encode(array_column($historial, 'texto'), JSON_UNESCAPED_UNICODE));
prueba('No deja terminarlo dos veces', !exito($lorena->post('encargo', 'terminar', ['id' => $idencargo])));

// Encargo N.º 4 de los datos de prueba: 3 ramos de 24 rosas rosadas (72) y hay 48 → no alcanza
$stockrosada = (int) datos($duena->get('inventario', 'obtener', ['id' => 2]))['tbinventariostock'];
$carlos->post('encargo', 'iniciar', ['id' => 4]);
$r = $carlos->post('encargo', 'terminar', ['id' => 4]);
prueba('Si no alcanza el stock, no deja terminar y dice qué falta', !exito($r) && strpos(mensaje($r), 'Rosa rosada') !== false, mensaje($r));
prueba('…y el stock no cambia', (int) datos($duena->get('inventario', 'obtener', ['id' => 2]))['tbinventariostock'] === $stockrosada);

$r = $duena->post('encargo', 'crear', $encargovalido);
$idencargo2 = datos($r)['tbencargoid'] ?? 0;
$fechanueva = date('Y-m-d', strtotime('+5 day'));
$r = $duena->post('encargo', 'actualizar', ['tbencargoid' => $idencargo2, 'tbencargofechaentrega' => $fechanueva] + $encargovalido);
prueba('Cambia la fecha de entrega', exito($r), mensaje($r));
$historial = datos($duena->get('encargo', 'obtener', ['id' => $idencargo2]))['historial'] ?? [];
prueba('El cambio de fecha queda en el historial', strpos(end($historial)['texto'] ?? '', 'La fecha de entrega cambió') !== false, json_encode(array_column($historial, 'texto'), JSON_UNESCAPED_UNICODE));
prueba('Cancela el encargo', exito($duena->post('encargo', 'cancelar', ['id' => $idencargo2])));
prueba('No deja cancelarlo dos veces', !exito($duena->post('encargo', 'cancelar', ['id' => $idencargo2])));

/* =====================================================================
 * 8. Empleados
 * =================================================================== */
seccion('8. Empleados');

$empleadovalido = [
    'tbempleadonombre'   => 'Sofía Prueba',
    'tbempleadotelefono' => '8700-5555',
    'tbempleadorol'      => 'Empleado',
    'tbempleadousuario'  => 'empleado5',
    'tbempleadoclave'    => 'empleado5',
];

$r = $duena->post('empleado', 'crear', ['tbempleadousuario' => 'empleado1'] + $empleadovalido);
prueba('No deja repetir el usuario', tieneerror($r, 'tbempleadousuario'));
$r = $duena->post('empleado', 'crear', ['tbempleadotelefono' => '8800-1001'] + $empleadovalido);
prueba('No deja repetir el teléfono', tieneerror($r, 'tbempleadotelefono'));
$r = $duena->post('empleado', 'crear', ['tbempleadoclave' => 'corta1'] + $empleadovalido);
prueba('La contraseña debe tener al menos 8 caracteres', tieneerror($r, 'tbempleadoclave'));
$r = $duena->post('empleado', 'crear', ['tbempleadoclave' => 'solamenteletras'] + $empleadovalido);
prueba('La contraseña debe tener letra y número', tieneerror($r, 'tbempleadoclave'));
$r = $duena->post('empleado', 'crear', ['tbempleadorol' => 'Dueña'] + $empleadovalido);
prueba('Solo puede haber una dueña activa', tieneerror($r, 'tbempleadorol'));

$r = $duena->post('empleado', 'crear', $empleadovalido);
$idempleado = datos($r)['tbempleadoid'] ?? 0;
prueba('Crea un empleado válido', exito($r) && $idempleado > 0, mensaje($r));

$r = $duena->get('empleado', 'listar');
$textolista = json_encode(datos($r));
prueba('La lista de empleados nunca envía contraseñas', strpos($textolista, 'clave') === false && strpos($textolista, '$2y$') === false);

$sofia = new Cliente($servidor);
prueba('El empleado nuevo puede entrar', exito($sofia->ingresar('empleado5', 'empleado5')));

$r = $duena->post('empleado', 'actualizar', ['tbempleadoid' => $idempleado, 'tbempleadoclave' => 'nuevaclave9'] + $empleadovalido);
prueba('La dueña le cambia la contraseña', exito($r), mensaje($r));
prueba('Ya no entra con la contraseña vieja', !exito((new Cliente($servidor))->ingresar('empleado5', 'empleado5')));
prueba('Sí entra con la nueva', exito((new Cliente($servidor))->ingresar('empleado5', 'nuevaclave9')));

$historial = datos($duena->get('empleado', 'historial', ['id' => $idempleado]));
$claves = array_values(array_filter($historial, function ($cambio) {
    return $cambio['campo'] === 'clave';
}));
prueba('El historial dice "Asignada" y "Cambiada", sin la contraseña', array_column($claves, 'nuevo') === ['Asignada', 'Cambiada']
    && strpos(json_encode($historial), 'nuevaclave9') === false, json_encode(array_column($claves, 'nuevo')));

$r = $duena->post('empleado', 'inactivar', ['id' => 1]);
prueba('La dueña no puede inactivarse a sí misma', !exito($r), mensaje($r));
$r = $duena->post('empleado', 'actualizar', ['tbempleadoid' => 1, 'tbempleadonombre' => 'Patricia Mora Solano', 'tbempleadotelefono' => '8800-0001',
    'tbempleadorol' => 'Empleado', 'tbempleadousuario' => 'duena', 'tbempleadoclave' => '']);
prueba('La dueña no puede quitarse el rol', tieneerror($r, 'tbempleadorol'));
$r = $duena->post('empleado', 'inactivar', ['id' => 3]);
prueba('No deja inactivar a alguien con encargos abiertos (Carlos)', !exito($r), mensaje($r));

prueba('Inactiva al empleado nuevo', exito($duena->post('empleado', 'inactivar', ['id' => $idempleado])));
$r = $sofia->get('encargo', 'listar');
prueba('Al inactivarlo, su sesión abierta se cierra (401)', $r['codigo'] === 401, 'HTTP ' . $r['codigo']);

/* =====================================================================
 * Resumen
 * =================================================================== */
$bien = $total - count($fallas);
echo "\n\033[1mResultado: {$bien} de {$total} pruebas correctas.\033[0m\n";

if ($fallas) {
    echo "\033[31mFallaron:\033[0m\n";
    foreach ($fallas as $falla) {
        echo "  - {$falla}\n";
    }
}

echo "\nRecuerde volver a cargar la base: las pruebas dejaron datos nuevos.\n";
exit($fallas ? 1 : 0);