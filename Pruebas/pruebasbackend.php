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

$r = $anonimo->ingresar('propietario', 'malaclave1');
prueba('Contraseña incorrecta no deja entrar', !exito($r) && mensaje($r) === 'Usuario o contraseña incorrectos.', mensaje($r));

$r = $anonimo->ingresar('noexiste', 'cualquiera1');
prueba('Usuario inexistente da el mismo mensaje (no revela cuál falló)', mensaje($r) === 'Usuario o contraseña incorrectos.', mensaje($r));

$r = (new Cliente($servidor))->ingresar('', '');
prueba('Usuario y contraseña vacíos piden completar', isset(errores($r)['usuario'], errores($r)['clave']), mensaje($r));

$r = (new Cliente($servidor))->ingresar('empleado4', 'empleado4');
prueba('Empleada inactiva no puede entrar', !exito($r) && strpos(mensaje($r), 'inactiva') !== false, mensaje($r));

$bloqueo = new Cliente($servidor);
for ($i = 0; $i < 5; $i++) {
    $bloqueo->ingresar('propietario', 'otraclave' . $i);
}
$r = $bloqueo->ingresar('propietario', 'propietario1');
prueba('Después de 5 intentos fallidos se bloquea, aunque la clave sea correcta', !exito($r) && strpos(mensaje($r), 'Demasiados intentos') !== false, mensaje($r));

$propietario = new Cliente($servidor);
$r = $propietario->ingresar('propietario', 'propietario1');
prueba('El propietario entra con propietario / propietario1', exito($r), mensaje($r));

// Las pruebas cuentan con los datos de prueba recién cargados
if (exito($r) && count(datos($propietario->get('proveedor', 'listar'))) !== 8) {
    fwrite(STDERR, "\nLa base ya tiene datos de una corrida anterior. Vuelva a cargarla:\n"
        . "  /opt/lampp/bin/mysql -u root --default-character-set=utf8mb4 < BaseDatos/ScriptsSQL/bdfloristica.sql\n"
        . "  /opt/lampp/bin/mysql -u root --default-character-set=utf8mb4 < BaseDatos/DatosIniciales/datosprueba.sql\n");
    exit(2);
}

$r = $propietario->get('proveedor', 'crear');
prueba('Una acción que modifica datos no acepta GET (405)', $r['codigo'] === 405, 'HTTP ' . $r['codigo']);

$r = $propietario->get('noexiste', 'listar');
prueba('Módulo inexistente responde con error', !exito($r), mensaje($r));

$r = $propietario->get('inicio');
prueba('La página de inicio carga para el propietario', $r['codigo'] === 200 && strpos($r['texto'], 'Productos con stock bajo') !== false);

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

$r = $propietario->get('catalogo', 'obtener', ['id' => 1]);
prueba('El propietario sí ve el costo', isset(datos($r)['costo']));

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

$r = $propietario->get('proveedor', 'listar');
prueba('Lista los 8 proveedores de prueba', count(datos($r)) === 8, 'Hay ' . count(datos($r)));

$r = $propietario->post('proveedor', 'crear', []);
$faltan = array_keys(errores($r));
prueba('Formulario vacío marca los obligatorios',
    !array_diff(['tbproveedornombreempresa', 'tbproveedornombrecontacto', 'tbproveedortelefono', 'tbproveedorprovincia'], $faltan),
    'Errores: ' . implode(', ', $faltan));

$r = $propietario->post('proveedor', 'crear', ['tbproveedornombreempresa' => 'Flores del Valle'] + $proveedorvalido);
prueba('No deja repetir el nombre de la empresa', tieneerror($r, 'tbproveedornombreempresa'), mensaje($r));

$r = $propietario->post('proveedor', 'crear', ['tbproveedortelefono' => '8888-1001'] + $proveedorvalido);
prueba('No deja repetir el teléfono', tieneerror($r, 'tbproveedortelefono'), mensaje($r));

$r = $propietario->post('proveedor', 'crear', ['tbproveedortelefono' => '1234'] + $proveedorvalido);
prueba('Teléfono nacional debe tener 8 dígitos', tieneerror($r, 'tbproveedortelefono'));

$r = $propietario->post('proveedor', 'crear', ['tbproveedorcorreo' => 'correo-malo'] + $proveedorvalido);
prueba('Correo con formato inválido', tieneerror($r, 'tbproveedorcorreo'));

$r = $propietario->post('proveedor', 'crear', ['tbproveedornombrecontacto' => 'Ana 123'] + $proveedorvalido);
prueba('Nombre del contacto no acepta números', tieneerror($r, 'tbproveedornombrecontacto'));

$r = $propietario->post('proveedor', 'crear', ['tbproveedorcanton' => 'Liberia'] + $proveedorvalido);
prueba('El cantón debe pertenecer a la provincia', tieneerror($r, 'tbproveedorcanton'));

$r = $propietario->post('proveedor', 'crear', $proveedorvalido);
$idproveedor = datos($r)['tbproveedorid'] ?? 0;
prueba('Crea un proveedor válido', exito($r) && $idproveedor > 0, mensaje($r));

$r = $propietario->get('proveedor', 'historial', ['id' => $idproveedor]);
$historial = datos($r);
prueba('El historial guarda los 6 datos iniciales', count($historial) === 6, 'Hay ' . count($historial));
prueba('…con quién lo hizo (Patricia)', ($historial[0]['hechopor'] ?? '') === 'Patricia Mora Solano', $historial[0]['hechopor'] ?? 'vacío');

$r = $propietario->get('proveedor', 'obtener', ['id' => $idproveedor]);
$actual = datos($r);
$r = $propietario->post('proveedor', 'actualizar', array_merge($proveedorvalido, ['tbproveedorid' => $idproveedor, 'tbproveedortelefono' => '8777-2222']));
prueba('Actualiza el teléfono', exito($r), mensaje($r));
$historial = datos($propietario->get('proveedor', 'historial', ['id' => $idproveedor]));
$ultimo = end($historial);
prueba('El cambio de teléfono queda en el historial', count($historial) === 7 && ($ultimo['anterior'] ?? '') === '8777-1111' && ($ultimo['nuevo'] ?? '') === '8777-2222',
    json_encode($ultimo, JSON_UNESCAPED_UNICODE));
prueba('El historial va del más antiguo al más reciente', ($historial[0]['fecha'] ?? '') <= ($ultimo['fecha'] ?? ''));

$r = $propietario->post('proveedor', 'actualizar', array_merge($proveedorvalido, ['tbproveedorid' => $idproveedor, 'tbproveedortelefono' => '8777-2222']));
$historial = datos($propietario->get('proveedor', 'historial', ['id' => $idproveedor]));
prueba('Guardar sin cambios no agrega nada al historial', count($historial) === 7, 'Hay ' . count($historial));

prueba('Inactiva el proveedor', exito($propietario->post('proveedor', 'inactivar', ['id' => $idproveedor])));
$r = $propietario->post('proveedor', 'inactivar', ['id' => $idproveedor]);
prueba('No deja inactivarlo dos veces', !exito($r), mensaje($r));
prueba('Lo reactiva', exito($propietario->post('proveedor', 'reactivar', ['id' => $idproveedor])));

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
    'tbinventarioprecioventa'               => '1000',
    'tbinventariostock'                     => 40,
    'tbinventariostockminimo'               => 10,
    'tbinventariocategoriaflortipo'         => 'Flor',
    'tbinventariocategoriaflorvariedad'     => 'Tulipán',
    'tbinventariocategoriaflorcolor'        => 'Amarillo',
    'tbinventariocategoriaflorlargotallo'   => 40,
    'tbinventariocategoriaflordiasduracion' => 7,
];

$r = $propietario->post('inventario', 'crear', ['tbinventariocategoria' => ''] + $florvalida);
prueba('La categoría es obligatoria', tieneerror($r, 'tbinventariocategoria'));

$r = $propietario->post('inventario', 'crear', ['tbinventarionombre' => 'Rosa roja'] + $florvalida);
prueba('No deja repetir el nombre con el mismo proveedor', tieneerror($r, 'tbinventarionombre'));

$r = $propietario->post('inventario', 'crear', ['tbproveedorid' => 4] + $florvalida);
prueba('No deja usar un proveedor inactivo', tieneerror($r, 'tbproveedorid'));

$r = $propietario->post('inventario', 'crear', ['tbinventariopreciopaquete' => '100.555'] + $florvalida);
prueba('El precio solo acepta 2 decimales', tieneerror($r, 'tbinventariopreciopaquete'));

$r = $propietario->post('inventario', 'crear', ['tbinventariopreciopaquete' => '0'] + $florvalida);
prueba('El precio debe ser mayor que 0', tieneerror($r, 'tbinventariopreciopaquete'));

$r = $propietario->post('inventario', 'crear', ['tbinventarioprecioventa' => '400'] + $florvalida);
prueba('El precio de venta no puede ser menor que el costo por unidad (500)', tieneerror($r, 'tbinventarioprecioventa'), mensaje($r));

$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaflortipo' => 'Árbol'] + $florvalida);
prueba('El tipo de flor solo puede ser Flor o Follaje', tieneerror($r, 'tbinventariocategoriaflortipo'));

$cintavalida = [
    'tbproveedorid' => 3, 'tbinventarionombre' => 'Cinta de prueba', 'tbinventariocategoria' => 'Envoltura',
    'tbinventariopreciopaquete' => '3000', 'tbinventariounidadespaquete' => 10, 'tbinventarioprecioventa' => '600',
    'tbinventariostock' => 10, 'tbinventariostockminimo' => 2,
    'tbinventariocategoriaenvolturaclase' => 'Cinta', 'tbinventariocategoriaenvolturatipo' => 'Satín',
    'tbinventariocategoriaenvolturacolor' => 'Azul', 'tbinventariocategoriaenvolturadiseno' => 'Lisa',
    'tbinventariocategoriaenvolturamedida' => '2.5', 'tbinventariocategoriaenvolturamaterial' => 'Tela',
    'tbinventariocategoriaenvolturaacabado' => 'Brillante', 'tbinventariocategoriaenvolturapersonalizable' => '1',
];
$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaenvolturapersonalizable' => ''] + $cintavalida);
prueba('La cinta pide si es personalizable', tieneerror($r, 'tbinventariocategoriaenvolturapersonalizable'));

$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaenvolturaclase' => 'Papel'] + $cintavalida);
prueba('Satín no es un tipo de papel', tieneerror($r, 'tbinventariocategoriaenvolturatipo'));

$r = $propietario->post('inventario', 'crear', $cintavalida);
$idcinta = datos($r)['tbinventarioid'] ?? 0;
$cinta = datos($propietario->get('inventario', 'obtener', ['id' => $idcinta]));
prueba('Crea una cinta personalizable; el acabado solo es del papel y en la cinta queda vacío',
    exito($r) && array_key_exists('tbinventariocategoriaenvolturaacabado', $cinta['categoria'] ?? [])
    && $cinta['categoria']['tbinventariocategoriaenvolturaacabado'] === null
    && (int) $cinta['categoria']['tbinventariocategoriaenvolturapersonalizable'] === 1, json_encode($cinta['categoria'] ?? null, JSON_UNESCAPED_UNICODE));

$r = $propietario->post('inventario', 'crear', [
    'tbproveedorid' => 6, 'tbinventarionombre' => 'Caja prueba', 'tbinventariocategoria' => 'Base',
    'tbinventariopreciopaquete' => '5000', 'tbinventariounidadespaquete' => 5, 'tbinventarioprecioventa' => '2000',
    'tbinventariostock' => 5, 'tbinventariostockminimo' => 1,
    'tbinventariocategoriabasetipo' => 'Caja', 'tbinventariocategoriabasematerial' => 'Madera', 'tbinventariocategoriabasecolor' => 'Café',
    'tbinventariocategoriabaseforma' => 'Cuadrada', 'tbinventariocategoriabasemedida' => 'grande',
]);
prueba('La medida debe ir en centímetros (ej. 20 x 30)', tieneerror($r, 'tbinventariocategoriabasemedida'));

$r = $propietario->post('inventario', 'crear', [
    'tbproveedorid' => 6, 'tbinventarionombre' => 'Chocolate prueba', 'tbinventariocategoria' => 'Chocolate',
    'tbinventariopreciopaquete' => '5000', 'tbinventariounidadespaquete' => 5, 'tbinventarioprecioventa' => '2000',
    'tbinventariostock' => 5, 'tbinventariostockminimo' => 1,
    'tbinventariocategoriachocolatetipo' => 'Oscuro', 'tbinventariocategoriachocolatemarca' => 'Prueba',
    'tbinventariocategoriachocolatepresentacion' => 'Barra', 'tbinventariocategoriachocolatepeso' => 100,
    'tbinventariocategoriachocolatesabor' => 'Menta', 'tbinventariocategoriachocolatefechavencimiento' => '',
]);
prueba('Los chocolates piden fecha de vencimiento', tieneerror($r, 'tbinventariocategoriachocolatefechavencimiento'));

$r = $propietario->post('inventario', 'crear', [
    'tbproveedorid' => 3, 'tbinventarionombre' => 'Tarjeta de prueba', 'tbinventariocategoria' => 'Tarjeta',
    'tbinventariopreciopaquete' => '2000', 'tbinventariounidadespaquete' => 20, 'tbinventarioprecioventa' => '500',
    'tbinventariostock' => 20, 'tbinventariostockminimo' => 5,
    'tbinventariocategoriatarjetatipo' => 'Dedicatoria', 'tbinventariocategoriatarjetatematica' => 'Amor',
    'tbinventariocategoriatarjetacolor' => 'Rojo', 'tbinventariocategoriatarjetamaterial' => 'Opalina',
    'tbinventariocategoriatarjetamedida' => '8 x 5', 'tbinventariocategoriatarjetapersonalizable' => '1',
    'tbinventariocategoriatarjetaelaboracion' => 'Hecha en el negocio',
]);
prueba('Crea una tarjeta con tipo, temática, material, personalizable y elaboración', exito($r), mensaje($r));

$r = $propietario->post('inventario', 'crear', $florvalida);
$idproducto = datos($r)['tbinventarioid'] ?? 0;
prueba('Crea un producto válido', exito($r) && $idproducto > 0, mensaje($r));

$producto = datos($propietario->get('inventario', 'obtener', ['id' => $idproducto]));
prueba('Calcula el precio por unidad (10000 / 20 = 500)', (float) ($producto['tbinventariopreciounidad'] ?? 0) === 500.0, json_encode($producto['tbinventariopreciounidad'] ?? null));
prueba('Registra el movimiento "Inicial" de 40', ($producto['movimientos'][0]['tipo'] ?? '') === 'Inicial' && ($producto['movimientos'][0]['cantidad'] ?? 0) === 40);
prueba('El historial tiene los 6 datos iniciales (con el precio de venta)', count(datos($propietario->get('inventario', 'historial', ['id' => $idproducto]))) === 6);

$cambio = $florvalida + ['tbinventarioid' => $idproducto];
$cambio['tbinventariostock'] = 35;
$cambio['tbinventariopreciopaquete'] = '12000';
$cambio['tbinventarioprecioventa'] = '1200';
$r = $propietario->post('inventario', 'actualizar', $cambio);
prueba('Actualiza stock y precio', exito($r), mensaje($r));

$producto = datos($propietario->get('inventario', 'obtener', ['id' => $idproducto]));
$ultimomovimiento = end($producto['movimientos']);
prueba('Corregir el stock a mano queda como "Ajuste" de −5', ($ultimomovimiento['tipo'] ?? '') === 'Ajuste' && ($ultimomovimiento['cantidad'] ?? 0) === -5 && ($ultimomovimiento['stockfinal'] ?? 0) === 35,
    json_encode($ultimomovimiento, JSON_UNESCAPED_UNICODE));
$historial = datos($propietario->get('inventario', 'historial', ['id' => $idproducto]));
$ultimo = end($historial);
$camposcambiados = array_column(array_slice($historial, -2), 'campo');
prueba('El cambio de precio y de precio de venta quedan en el historial',
    in_array('precio', $camposcambiados, true) && in_array('precioventa', $camposcambiados, true), json_encode(array_slice($historial, -2), JSON_UNESCAPED_UNICODE));

prueba('Inactiva el producto', exito($propietario->post('inventario', 'inactivar', ['id' => $idproducto])));
prueba('Lo reactiva', exito($propietario->post('inventario', 'reactivar', ['id' => $idproducto])));

/* =====================================================================
 * 5. Pedidos
 * =================================================================== */
seccion('5. Pedidos');

$stockantes = (int) datos($propietario->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'];

$r = $propietario->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $hoy]);
prueba('Un pedido sin productos no se guarda', tieneerror($r, 'detalles'));

$detalle = ['detalles' => [['tbinventarioid' => 1, 'tbpedidodetallecantidad' => 2, 'tbpedidodetalleprecio' => '12000']]];
$r = $propietario->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $manana] + $detalle);
prueba('La fecha del pedido no puede ser futura', tieneerror($r, 'tbpedidofecha'));

$r = $propietario->post('pedido', 'crear', ['tbproveedorid' => 4, 'tbpedidofecha' => $hoy] + $detalle);
prueba('No deja pedir a un proveedor inactivo', tieneerror($r, 'tbproveedorid'));

$r = $propietario->post('pedido', 'crear', ['tbproveedorid' => 1, 'tbpedidofecha' => $hoy] + $detalle);
$idpedido = datos($r)['tbpedidoid'] ?? 0;
prueba('Crea un pedido válido (2 paquetes de rosa roja)', exito($r) && $idpedido > 0, mensaje($r));

$r = $propietario->post('pedido', 'recibir', ['id' => $idpedido]);
prueba('No deja recibir un pedido sin confirmar', !exito($r), mensaje($r));

$r = $propietario->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Crédito']);
prueba('A crédito pide el plazo', tieneerror($r, 'tbconfirmacioncompraplazodias'));

$r = $propietario->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Crédito', 'tbconfirmacioncompraplazodias' => 400]);
prueba('El plazo no puede pasar de 365 días', tieneerror($r, 'tbconfirmacioncompraplazodias'));

prueba('Confirma el pedido de contado', exito($propietario->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Contado'])));
prueba('No deja confirmarlo dos veces', !exito($propietario->post('pedido', 'confirmar', ['tbpedidoid' => $idpedido, 'tbconfirmacioncompratipopago' => 'Contado'])));

prueba('Recibe el pedido', exito($propietario->post('pedido', 'recibir', ['id' => $idpedido])));
$producto = datos($propietario->get('inventario', 'obtener', ['id' => 1]));
prueba('El stock de rosa roja sube 48 (2 paquetes × 24)', (int) $producto['tbinventariostock'] === $stockantes + 48, "Antes {$stockantes}, ahora " . $producto['tbinventariostock']);
$ultimomovimiento = end($producto['movimientos']);
prueba('Queda el movimiento "Entrada" del pedido', ($ultimomovimiento['tipo'] ?? '') === 'Entrada' && strpos($ultimomovimiento['motivo'] ?? '', "N.º {$idpedido}") !== false);

$historial = datos($propietario->get('pedido', 'historial', ['id' => $idpedido]));
prueba('Historial del pedido: Pendiente → Confirmado → Recibido', array_column($historial, 'nuevo') === ['Pendiente', 'Confirmado', 'Recibido'], json_encode(array_column($historial, 'nuevo')));
prueba('No deja recibirlo dos veces', !exito($propietario->post('pedido', 'recibir', ['id' => $idpedido])));

/* =====================================================================
 * 6. Catálogo
 * =================================================================== */
seccion('6. Catálogo');

$arreglovalido = [
    'tbarreglofloralnombre'      => 'Arreglo de prueba',
    'tbarreglofloraldescripcion' => 'Creado por las pruebas',
    'tbarreglofloralprecio'      => '25000',
    'detalles'                   => [
        ['tbinventarioid' => 1, 'tbarreglofloralinsumocantidad' => 6],   // rosa roja 6 × 291,67
        ['tbinventarioid' => 11, 'tbarreglofloralinsumocantidad' => 2],  // eucalipto 2 × 250
    ],
];

$r = $propietario->post('catalogo', 'crear', $arreglovalido);
prueba('La foto es obligatoria al crear', tieneerror($r, 'tbarreglofloralfoto'));

$r = $propietario->post('catalogo', 'crear', ['detalles' => []] + $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
prueba('Debe llevar al menos un producto', tieneerror($r, 'detalles'));

$r = $propietario->post('catalogo', 'crear', ['tbarreglofloralnombre' => 'Ramo Delicado'] + $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
prueba('No deja repetir el nombre', tieneerror($r, 'tbarreglofloralnombre'));

$r = $propietario->post('catalogo', 'crear', ['tbarreglofloralfoto_falsa' => 1] + $arreglovalido, ['tbarreglofloralfoto' => __FILE__]);
prueba('Rechaza un archivo que no es imagen', tieneerror($r, 'tbarreglofloralfoto'), mensaje($r));

$r = $propietario->post('catalogo', 'crear', $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
$idarreglo = datos($r)['tbarreglofloralid'] ?? 0;
prueba('Crea un arreglo válido con foto', exito($r) && $idarreglo > 0, mensaje($r));

$arreglo = datos($propietario->get('catalogo', 'obtener', ['id' => $idarreglo]));
prueba('Calcula el costo (6 × 291,67 + 2 × 250 = 2 250,02)', (float) ($arreglo['costo'] ?? 0) === 2250.02, json_encode($arreglo['costo'] ?? null));
prueba('Guarda la foto en imagenes/arreglos', strpos($arreglo['tbarreglofloralfoto'] ?? '', 'imagenes/arreglos/') === 0);

$r = $propietario->post('catalogo', 'actualizar', ['tbarreglofloralid' => $idarreglo, 'tbarreglofloralprecio' => '27000'] + $arreglovalido);
prueba('Actualiza el precio sin cambiar la foto', exito($r), mensaje($r));
$historial = datos($propietario->get('catalogo', 'historial', ['id' => $idarreglo]));
$ultimo = end($historial);
prueba('El cambio de precio queda en el historial', ($ultimo['campo'] ?? '') === 'precio' && strpos($ultimo['nuevo'] ?? '', '27 000') !== false, json_encode($ultimo, JSON_UNESCAPED_UNICODE));

/* =====================================================================
 * 7. Solicitudes de pedido de arreglo
 * =================================================================== */
seccion('7. Solicitudes de pedido de arreglo');

// Arreglo de la sección 6: 27 000 colones, lleva 6 rosas rojas y 2 de eucalipto
$base = [
    'tbclienteid'                    => 2,
    'tbempleadoid'                   => '',
    'tbsolicitudencargofechaentrega' => $manana,
    'tbsolicitudencargonota'         => 'Prueba',
];
$predefinida = $base + [
    'tbsolicitudencargotipo'     => 'Predefinido',
    'tbarreglofloralid'          => $idarreglo,
    'tbsolicitudencargocantidad' => 2,
    'insumos'                    => [['tbinventarioid' => 24, 'cantidad' => 1]],   // caja de chocolates, 7 000
];

$r = $lorena->get('encargo', 'opciones');
$opciones = datos($r);
prueba('Un empleado recibe clientes, zonas, arreglos y productos para el formulario',
    exito($r) && count($opciones['clientes'] ?? []) === 8 && count($opciones['zonas'] ?? []) === 6 && count($opciones['productos'] ?? []) > 0);
prueba('…con el precio de venta de los productos, pero sin el costo',
    isset($opciones['productos'][0]['tbinventarioprecioventa']) && !isset($opciones['productos'][0]['tbinventariopreciounidad']));

$r = $propietario->post('encargo', 'crear', ['tbclienteid' => ''] + $predefinida);
prueba('El cliente es obligatorio', tieneerror($r, 'tbclienteid'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargotipo' => ''] + $predefinida);
prueba('Hay que elegir si es predefinido o personalizado', tieneerror($r, 'tbsolicitudencargotipo'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargofechaentrega' => $ayer] + $predefinida);
prueba('La fecha de entrega no puede ser anterior a hoy', tieneerror($r, 'tbsolicitudencargofechaentrega'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargocantidad' => 0] + $predefinida);
prueba('La cantidad de arreglos debe ser de 1 a 99', tieneerror($r, 'tbsolicitudencargocantidad'));

$r = $propietario->post('encargo', 'crear', ['tbempleadoid' => 5] + $predefinida);
prueba('No deja asignarla a una empleada inactiva', tieneerror($r, 'tbempleadoid'));

$r = $lorena->post('encargo', 'crear', ['tbempleadoid' => 3] + $predefinida);
prueba('Un empleado no puede crear una solicitud asignada a otro', tieneerror($r, 'tbempleadoid'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargoexpress' => '1'] + $predefinida);
prueba('Con express pide la zona y la dirección', tieneerror($r, 'tbzonaentregaid') && tieneerror($r, 'tbsolicitudencargodireccionentrega'));

$r = $propietario->post('encargo', 'crear', ['insumos' => [['tbinventarioid' => 999, 'cantidad' => 1]]] + $predefinida);
prueba('No acepta un producto que no existe', tieneerror($r, 'insumos'));

$conexpress = ['tbsolicitudencargoexpress' => '1', 'tbzonaentregaid' => 4, 'tbsolicitudencargodireccionentrega' => 'Guápiles, barrio La Emilia'];
$r = $propietario->post('encargo', 'crear', $conexpress + $predefinida);
$idencargo = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Crea una predefinida: 2 arreglos + chocolates + express a Guápiles = 63 500',
    exito($r) && (float) (datos($r)['total'] ?? 0) === 63500.0, mensaje($r));

$encargo = datos($propietario->get('encargo', 'obtener', ['id' => $idencargo]));
prueba('Guarda subtotal 61 000, express 2 500 y el precio del adicional',
    (float) ($encargo['tbsolicitudencargosubtotal'] ?? 0) === 61000.0 && (float) ($encargo['tbsolicitudencargocostoexpress'] ?? 0) === 2500.0
    && (float) ($encargo['insumos'][0]['preciounitario'] ?? 0) === 7000.0, json_encode(array_intersect_key($encargo, array_flip(['tbsolicitudencargosubtotal', 'tbsolicitudencargocostoexpress'])) + ['insumos' => $encargo['insumos'] ?? []], JSON_UNESCAPED_UNICODE));
prueba('Los materiales suman el arreglo × 2 y el adicional (12 rosas, 4 eucalipto, 1 caja de chocolates)',
    array_column($encargo['materiales'] ?? [], 'necesarias', 'tbinventarioid') == [1 => 12, 11 => 4, 24 => 1],
    json_encode(array_column($encargo['materiales'] ?? [], 'necesarias', 'tbinventarioid')));

// Personalizadas: cada producto se cobra a su costo + el % de ganancia (100% = el doble del costo)
$personalizada = $base + ['tbsolicitudencargotipo' => 'Personalizado', 'tbsolicitudencargoporcentaje' => '100'];

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Libre', 'tbsolicitudencargoporcentaje' => '',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 2]]] + $personalizada);
prueba('El % de ganancia es obligatorio en los personalizados', tieneerror($r, 'tbsolicitudencargoporcentaje'));
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Libre', 'tbsolicitudencargoporcentaje' => '600',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 2]]] + $personalizada);
prueba('El % de ganancia va de 0 a 500', tieneerror($r, 'tbsolicitudencargoporcentaje'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Libre'] + $personalizada);
prueba('Un arreglo personalizado debe llevar al menos un producto', tieneerror($r, 'insumos'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Presupuesto',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 2]]] + $personalizada);
prueba('Con presupuesto pide el monto', tieneerror($r, 'tbsolicitudencargopresupuesto'));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Presupuesto', 'tbsolicitudencargopresupuesto' => '10000',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 20]]] + $personalizada);
prueba('No deja pasarse del presupuesto (20 rosas a 590 = 11 800 > 10 000)', tieneerror($r, 'insumos') && strpos(errores($r)['insumos'], 'presupuesto') !== false, mensaje($r));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Presupuesto', 'tbsolicitudencargopresupuesto' => '15000',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 8], ['tbinventarioid' => 11, 'cantidad' => 3], ['tbinventarioid' => 19, 'cantidad' => 1]]] + $personalizada);
$idpersonalizada = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Crea una personalizada con presupuesto: costo × 2 (8 rosas a 590 + 3 eucaliptos a 500 + 1 papel kraft a 400 = 6 620)',
    exito($r) && (float) (datos($r)['total'] ?? 0) === 6620.0, mensaje($r));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Libre',
    'insumos' => [['tbinventarioid' => 5, 'cantidad' => 2], ['tbinventarioid' => 5, 'cantidad' => 4], ['tbinventarioid' => 24, 'cantidad' => 1]]] + $personalizada);
$idlibre = datos($r)['tbsolicitudencargoid'] ?? 0;
$libre = datos($propietario->get('encargo', 'obtener', ['id' => $idlibre]));
prueba('Libre: calcula el total para cotizar (6 girasoles + chocolates = 10 000)', exito($r) && (float) ($libre['tbsolicitudencargototal'] ?? 0) === 10000.0, mensaje($r));
prueba('Si un producto se elige dos veces, se suman las cantidades', count($libre['insumos'] ?? []) === 2 && ($libre['insumos'][0]['cantidad'] ?? 0) === 6,
    json_encode($libre['insumos'] ?? [], JSON_UNESCAPED_UNICODE));
prueba('La lista dice "Personalizado (libre)"', ($libre['descripcion'] ?? '') === 'Personalizado (libre)', $libre['descripcion'] ?? '');
prueba('Guarda el % de ganancia y lo que costó (6 girasoles a 250 + chocolates a 3 500 = 5 000)',
    (float) ($libre['tbsolicitudencargoporcentaje'] ?? 0) === 100.0 && (float) ($libre['tbsolicitudencargocosto'] ?? 0) === 5000.0,
    json_encode(array_intersect_key($libre, array_flip(['tbsolicitudencargoporcentaje', 'tbsolicitudencargocosto']))));

$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargomodalidad' => 'Libre', 'tbsolicitudencargoporcentaje' => '80',
    'insumos' => [['tbinventarioid' => 2, 'cantidad' => 6]]] + $personalizada);
$idochenta = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Con 80% de ganancia: 6 rosas rosadas a 530 (291,67 × 1,8 hacia arriba a ₡10) = 3 180',
    exito($r) && (float) (datos($r)['total'] ?? 0) === 3180.0, mensaje($r));
$r = $propietario->post('encargo', 'actualizar', ['tbsolicitudencargoid' => $idochenta, 'tbsolicitudencargomodalidad' => 'Libre',
    'tbsolicitudencargoporcentaje' => '150', 'insumos' => [['tbinventarioid' => 2, 'cantidad' => 6]]] + $personalizada);
$ochenta = datos($propietario->get('encargo', 'obtener', ['id' => $idochenta]));
prueba('Al cambiar el % se vuelven a calcular los precios (150%: 6 × 730 = 4 380)',
    exito($r) && (float) ($ochenta['tbsolicitudencargototal'] ?? 0) === 4380.0, mensaje($r));
$propietario->post('encargo', 'cancelar', ['id' => $idochenta]);

$r = $propietario->get('encargo', 'listar', ['tipo' => 'Personalizado']);
prueba('Filtra por tipo (3 de prueba + 3 nuevas personalizadas)', count(datos($r)) === 6, 'Hay ' . count(datos($r)));

// Flujo de la predefinida: asignar, iniciar y terminar
$r = $propietario->post('encargo', 'iniciar', ['id' => $idencargo]);
prueba('No deja iniciarla sin asignar', !exito($r), mensaje($r));

$r = $propietario->post('encargo', 'asignar', ['tbsolicitudencargoid' => $idencargo, 'tbempleadoid' => 2]);
prueba('El propietario se la asigna a Lorena', exito($r), mensaje($r));
$r = $propietario->post('encargo', 'asignar', ['tbsolicitudencargoid' => $idencargo, 'tbempleadoid' => 2]);
prueba('Asignarla a la misma persona no duplica el historial', exito($r) && strpos(mensaje($r), 'ya estaba') !== false, mensaje($r));

$r = $carlos->post('encargo', 'iniciar', ['id' => $idencargo]);
prueba('Carlos no puede iniciar una solicitud de Lorena', !exito($r), mensaje($r));
$r = $carlos->post('encargo', 'asignarme', ['id' => $idencargo]);
prueba('Carlos no puede quitársela a Lorena con "Asignármela"', !exito($r), mensaje($r));

prueba('Lorena la inicia', exito($lorena->post('encargo', 'iniciar', ['id' => $idencargo])));

$stockrosa = (int) datos($propietario->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'];
$stockchocolate = (int) datos($propietario->get('inventario', 'obtener', ['id' => 24]))['tbinventariostock'];
prueba('Lorena la termina', exito($lorena->post('encargo', 'terminar', ['id' => $idencargo])));
$rosa = datos($propietario->get('inventario', 'obtener', ['id' => 1]));
$chocolate = datos($propietario->get('inventario', 'obtener', ['id' => 24]));
prueba('Descuenta 12 rosas (6 por arreglo × 2)', (int) $rosa['tbinventariostock'] === $stockrosa - 12, "Antes {$stockrosa}, ahora " . $rosa['tbinventariostock']);
prueba('Descuenta también el adicional (1 caja de chocolates)', (int) $chocolate['tbinventariostock'] === $stockchocolate - 1);
$ultimomovimiento = end($rosa['movimientos']);
prueba('Queda la "Salida" con el nombre de Lorena', ($ultimomovimiento['tipo'] ?? '') === 'Salida' && ($ultimomovimiento['hechopor'] ?? '') === 'Lorena Bermúdez Badilla',
    json_encode($ultimomovimiento, JSON_UNESCAPED_UNICODE));

$historial = datos($propietario->get('encargo', 'obtener', ['id' => $idencargo]))['historial'] ?? [];
prueba('El historial tiene registro, asignación, inicio y fin', count($historial) === 4, json_encode(array_column($historial, 'texto'), JSON_UNESCAPED_UNICODE));
prueba('No deja terminarla dos veces', !exito($lorena->post('encargo', 'terminar', ['id' => $idencargo])));
$r = $propietario->post('encargo', 'actualizar', ['tbsolicitudencargoid' => $idencargo] + $conexpress + $predefinida);
prueba('No deja editar una solicitud terminada', !exito($r), mensaje($r));

// La personalizada descuenta lo que eligió el cliente
$propietario->post('encargo', 'asignarme', ['id' => $idpersonalizada]);
$propietario->post('encargo', 'iniciar', ['id' => $idpersonalizada]);
$stockrosa = (int) datos($propietario->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'];
prueba('El propietario termina la personalizada', exito($propietario->post('encargo', 'terminar', ['id' => $idpersonalizada])));
prueba('…y descuenta las 8 rosas que eligió el cliente', (int) datos($propietario->get('inventario', 'obtener', ['id' => 1]))['tbinventariostock'] === $stockrosa - 8);

// Solicitud N.º 4 de los datos de prueba: 3 ramos de lirios con frase (18 lirios) y hay 15 → no alcanza
$stocklirio = (int) datos($propietario->get('inventario', 'obtener', ['id' => 10]))['tbinventariostock'];
$carlos->post('encargo', 'iniciar', ['id' => 4]);
$r = $carlos->post('encargo', 'terminar', ['id' => 4]);
prueba('Si no alcanza el stock, no deja terminar y dice qué falta', !exito($r) && strpos(mensaje($r), 'Lirio rosado') !== false, mensaje($r));
prueba('…y el stock no cambia', (int) datos($propietario->get('inventario', 'obtener', ['id' => 10]))['tbinventariostock'] === $stocklirio);

// Editar: se conserva el precio con que se cotizó aunque cambie el catálogo
$r = $propietario->post('encargo', 'crear', ['insumos' => []] + $predefinida);
$idencargo2 = datos($r)['tbsolicitudencargoid'] ?? 0;
$propietario->post('catalogo', 'actualizar', ['tbarreglofloralid' => $idarreglo, 'tbarreglofloralprecio' => '30000'] + $arreglovalido);
$fechanueva = date('Y-m-d', strtotime('+5 day'));
$r = $propietario->post('encargo', 'actualizar', ['tbsolicitudencargoid' => $idencargo2, 'tbsolicitudencargofechaentrega' => $fechanueva,
    'tbsolicitudencargocantidad' => 3, 'insumos' => []] + $predefinida);
$encargo2 = datos($propietario->get('encargo', 'obtener', ['id' => $idencargo2]));
prueba('Al editar se conserva el precio cotizado del arreglo (3 × 27 000 = 81 000)', exito($r) && (float) ($encargo2['tbsolicitudencargototal'] ?? 0) === 81000.0,
    mensaje($r) . ' Total: ' . ($encargo2['tbsolicitudencargototal'] ?? ''));
$textos = implode(' | ', array_column($encargo2['historial'] ?? [], 'texto'));
prueba('El cambio de fecha y de total quedan en el historial', strpos($textos, 'La fecha de entrega cambió') !== false && strpos($textos, 'El total cambió') !== false, $textos);
prueba('Cancela la solicitud', exito($propietario->post('encargo', 'cancelar', ['id' => $idencargo2])));
prueba('No deja cancelarla dos veces', !exito($propietario->post('encargo', 'cancelar', ['id' => $idencargo2])));

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

$r = $propietario->post('empleado', 'crear', ['tbempleadousuario' => 'empleado1'] + $empleadovalido);
prueba('No deja repetir el usuario', tieneerror($r, 'tbempleadousuario'));
$r = $propietario->post('empleado', 'crear', ['tbempleadotelefono' => '8800-1001'] + $empleadovalido);
prueba('No deja repetir el teléfono', tieneerror($r, 'tbempleadotelefono'));
$r = $propietario->post('empleado', 'crear', ['tbempleadoclave' => 'corta1'] + $empleadovalido);
prueba('La contraseña debe tener al menos 8 caracteres', tieneerror($r, 'tbempleadoclave'));
$r = $propietario->post('empleado', 'crear', ['tbempleadoclave' => 'solamenteletras'] + $empleadovalido);
prueba('La contraseña debe tener letra y número', tieneerror($r, 'tbempleadoclave'));
$r = $propietario->post('empleado', 'crear', ['tbempleadorol' => 'Propietario'] + $empleadovalido);
prueba('Solo puede haber un propietario activo', tieneerror($r, 'tbempleadorol'));

$r = $propietario->post('empleado', 'crear', $empleadovalido);
$idempleado = datos($r)['tbempleadoid'] ?? 0;
prueba('Crea un empleado válido', exito($r) && $idempleado > 0, mensaje($r));

$r = $propietario->get('empleado', 'listar');
$textolista = json_encode(datos($r));
prueba('La lista de empleados nunca envía contraseñas', strpos($textolista, 'clave') === false && strpos($textolista, '$2y$') === false);

$sofia = new Cliente($servidor);
prueba('El empleado nuevo puede entrar', exito($sofia->ingresar('empleado5', 'empleado5')));

$r = $propietario->post('empleado', 'actualizar', ['tbempleadoid' => $idempleado, 'tbempleadoclave' => 'nuevaclave9'] + $empleadovalido);
prueba('El propietario le cambia la contraseña', exito($r), mensaje($r));
prueba('Ya no entra con la contraseña vieja', !exito((new Cliente($servidor))->ingresar('empleado5', 'empleado5')));
prueba('Sí entra con la nueva', exito((new Cliente($servidor))->ingresar('empleado5', 'nuevaclave9')));

$historial = datos($propietario->get('empleado', 'historial', ['id' => $idempleado]));
$claves = array_values(array_filter($historial, function ($cambio) {
    return $cambio['campo'] === 'clave';
}));
prueba('El historial dice "Asignada" y "Cambiada", sin la contraseña', array_column($claves, 'nuevo') === ['Asignada', 'Cambiada']
    && strpos(json_encode($historial), 'nuevaclave9') === false, json_encode(array_column($claves, 'nuevo')));

$r = $propietario->post('empleado', 'inactivar', ['id' => 1]);
prueba('El propietario no puede inactivarse a sí mismo', !exito($r), mensaje($r));
$r = $propietario->post('empleado', 'actualizar', ['tbempleadoid' => 1, 'tbempleadonombre' => 'Patricia Mora Solano', 'tbempleadotelefono' => '8800-0001',
    'tbempleadorol' => 'Empleado', 'tbempleadousuario' => 'propietario', 'tbempleadoclave' => '']);
prueba('El propietario no puede quitarse el rol', tieneerror($r, 'tbempleadorol'));
$r = $propietario->post('empleado', 'inactivar', ['id' => 3]);
prueba('No deja inactivar a alguien con solicitudes abiertas (Carlos)', !exito($r), mensaje($r));

prueba('Inactiva al empleado nuevo', exito($propietario->post('empleado', 'inactivar', ['id' => $idempleado])));
$r = $sofia->get('encargo', 'listar');
prueba('Al inactivarlo, su sesión abierta se cierra (401)', $r['codigo'] === 401, 'HTTP ' . $r['codigo']);

/* =====================================================================
 * 9. Clientes
 * =================================================================== */
seccion('9. Clientes');

$clientevalido = [
    'tbclientenombre'    => 'Valeria Prueba Mora',
    'tbclientetelefono'  => '88887777',
    'tbclientecorreo'    => 'Valeria.Prueba@Gmail.com',
    'tbclientedireccion' => 'Cariari, barrio Palermo',
];

$r = $lorena->get('cliente', 'listar');
prueba('Un empleado sí puede ver los clientes (8 de prueba)', exito($r) && count(datos($r)) === 8, 'Hay ' . count(datos($r)));

$r = $lorena->post('cliente', 'crear', []);
prueba('Formulario vacío pide nombre y teléfono', isset(errores($r)['tbclientenombre'], errores($r)['tbclientetelefono']), mensaje($r));

$r = $lorena->post('cliente', 'crear', ['tbclientetelefono' => '8701-2233'] + $clientevalido);
prueba('No deja repetir el teléfono de otro cliente', tieneerror($r, 'tbclientetelefono'));

$r = $lorena->post('cliente', 'crear', ['tbclientetelefono' => '1234-5678'] + $clientevalido);
prueba('El teléfono debe ser un número de Costa Rica válido', tieneerror($r, 'tbclientetelefono'));

$r = $lorena->post('cliente', 'crear', ['tbclientenombre' => 'Valeria 2'] + $clientevalido);
prueba('El nombre no acepta números', tieneerror($r, 'tbclientenombre'));

$r = $lorena->post('cliente', 'crear', ['tbclientecorreo' => 'correo-malo'] + $clientevalido);
prueba('Correo con formato inválido', tieneerror($r, 'tbclientecorreo'));

$r = $lorena->post('cliente', 'crear', $clientevalido);
$idcliente = datos($r)['tbclienteid'] ?? 0;
prueba('Un empleado registra un cliente nuevo', exito($r) && $idcliente > 0, mensaje($r));

$cliente = datos($lorena->get('cliente', 'obtener', ['id' => $idcliente]));
prueba('Guarda el teléfono como 8888-7777 y el correo en minúscula',
    ($cliente['tbclientetelefono'] ?? '') === '8888-7777' && ($cliente['tbclientecorreo'] ?? '') === 'valeria.prueba@gmail.com',
    json_encode($cliente, JSON_UNESCAPED_UNICODE));

$r = $lorena->get('cliente', 'listar', ['texto' => '88887777']);
prueba('Se puede buscar por teléfono sin guion', count(datos($r)) === 1, 'Hay ' . count(datos($r)));

$historial = datos($lorena->get('cliente', 'historial', ['id' => $idcliente]));
prueba('El historial tiene los 4 datos iniciales, hechos por Lorena',
    count($historial) === 4 && ($historial[0]['hechopor'] ?? '') === 'Lorena Bermúdez Badilla', json_encode($historial, JSON_UNESCAPED_UNICODE));

$r = $lorena->post('cliente', 'actualizar', ['tbclienteid' => $idcliente, 'tbclientedireccion' => 'Cariari, barrio Palermo, casa 12'] + $clientevalido);
prueba('Actualiza la dirección', exito($r), mensaje($r));
$historial = datos($lorena->get('cliente', 'historial', ['id' => $idcliente]));
$ultimo = end($historial);
prueba('El cambio de dirección queda en el historial', count($historial) === 5 && ($ultimo['campo'] ?? '') === 'direccion', json_encode($ultimo, JSON_UNESCAPED_UNICODE));

$r = $propietario->post('cliente', 'inactivar', ['id' => 1]);
prueba('No deja inactivar a un cliente con solicitudes abiertas', !exito($r) && strpos(mensaje($r), 'solicitud') !== false, mensaje($r));

prueba('Inactiva el cliente nuevo', exito($propietario->post('cliente', 'inactivar', ['id' => $idcliente])));
prueba('No deja inactivarlo dos veces', !exito($propietario->post('cliente', 'inactivar', ['id' => $idcliente])));
prueba('Lo reactiva', exito($propietario->post('cliente', 'reactivar', ['id' => $idcliente])));

/* =====================================================================
 * 10. Zonas de entrega (servicio express)
 * =================================================================== */
seccion('10. Zonas de entrega');

$r = $lorena->get('zonaentrega', 'listar');
prueba('Un empleado no puede administrar las zonas (403)', $r['codigo'] === 403, 'HTTP ' . $r['codigo']);
$r = $lorena->post('zonaentrega', 'actualizar', ['tbzonaentregaid' => 1, 'tbzonaentreganombre' => 'El Humo de Roxana', 'tbzonaentregaprecio' => '1']);
prueba('…ni cambiarles el precio (403)', $r['codigo'] === 403, 'HTTP ' . $r['codigo']);

$r = $propietario->get('zonaentrega', 'listar');
$zonas = datos($r);
prueba('Lista las 6 zonas de prueba, de la más barata a la más cara',
    count($zonas) === 6 && ($zonas[0]['tbzonaentreganombre'] ?? '') === 'El Humo de Roxana' && ($zonas[5]['tbzonaentreganombre'] ?? '') === 'Limón centro',
    json_encode(array_column($zonas, 'tbzonaentreganombre'), JSON_UNESCAPED_UNICODE));

$zonavalida = ['tbzonaentreganombre' => 'Pococí rural', 'tbzonaentregadescripcion' => 'Fincas y pueblos alejados', 'tbzonaentregaprecio' => '3000'];

$r = $propietario->post('zonaentrega', 'crear', ['tbzonaentregaprecio' => '0'] + $zonavalida);
prueba('El precio del express debe ser mayor que 0', tieneerror($r, 'tbzonaentregaprecio'));

$r = $propietario->post('zonaentrega', 'crear', ['tbzonaentreganombre' => 'guápiles'] + $zonavalida);
prueba('No deja repetir el nombre de una zona (sin importar mayúsculas)', tieneerror($r, 'tbzonaentreganombre'));

$r = $propietario->post('zonaentrega', 'crear', $zonavalida);
$idzona = datos($r)['tbzonaentregaid'] ?? 0;
prueba('Crea una zona nueva', exito($r) && $idzona > 0, mensaje($r));
prueba('El historial tiene nombre, precio y estado', count(datos($propietario->get('zonaentrega', 'historial', ['id' => $idzona]))) === 3);

$r = $propietario->post('zonaentrega', 'actualizar', ['tbzonaentregaid' => $idzona, 'tbzonaentregaprecio' => '3500'] + $zonavalida);
prueba('El propietario le cambia el precio', exito($r), mensaje($r));
$historial = datos($propietario->get('zonaentrega', 'historial', ['id' => $idzona]));
$ultimo = end($historial);
prueba('El cambio de precio queda en el historial', ($ultimo['campo'] ?? '') === 'precio' && strpos($ultimo['nuevo'] ?? '', '3 500') !== false
    && strpos($ultimo['anterior'] ?? '', '3 000') !== false, json_encode($ultimo, JSON_UNESCAPED_UNICODE));

$r = $propietario->post('zonaentrega', 'inactivar', ['id' => 1]);
prueba('No deja inactivar una zona con solicitudes abiertas', !exito($r) && strpos(mensaje($r), 'solicitud') !== false, mensaje($r));

prueba('Inactiva la zona nueva', exito($propietario->post('zonaentrega', 'inactivar', ['id' => $idzona])));
prueba('La reactiva', exito($propietario->post('zonaentrega', 'reactivar', ['id' => $idzona])));

/* =====================================================================
 * 11. Temporada alta: se escribe lo que cobra el proveedor y lo demás sube solo
 *     (misma ganancia en colones)
 * =================================================================== */
seccion('11. Temporada alta');

$r = $lorena->get('temporada', 'estado');
prueba('Un empleado no puede manejar la temporada alta (403)', $r['codigo'] === 403, 'HTTP ' . $r['codigo']);

$estado = datos($propietario->get('temporada', 'estado'));
$flores = array_column($estado['flores'] ?? [], null, 'id');
prueba('Hay una sola temporada, "Temporada alta", y está apagada', ($estado['tbtemporadanombre'] ?? '') === 'Temporada alta'
    && ($estado['encendida'] ?? true) === false, json_encode(array_diff_key($estado, ['flores' => 1]), JSON_UNESCAPED_UNICODE));
prueba('Muestra lo que cobra el proveedor por las rosas: ₡7 000 normal y ₡14 000 en temporada alta',
    (float) ($flores[1]['paquetenormal'] ?? 0) === 7000.0 && (float) ($flores[1]['paquetealto'] ?? 0) === 14000.0);
prueba('…y a cuánto se vendería la rosa: ₡600 + lo que subió el costo (₡291,66) = ₡900',
    (float) ($flores[1]['ventaalta'] ?? 0) === 900.0, json_encode($flores[1] ?? null, JSON_UNESCAPED_UNICODE));

$catalogo = array_column(datos($propietario->get('catalogo', 'listar')), null, 'tbarreglofloralid');
prueba('Apagada, el catálogo ya muestra los dos precios del Ramo Delicado: ₡12 000 y ₡14 100 en temporada alta',
    (float) ($catalogo[1]['precioactual'] ?? 0) === 12000.0 && (float) ($catalogo[1]['precioalto'] ?? 0) === 14100.0
    && ($catalogo[1]['altaencendida'] ?? true) === false, json_encode($catalogo[1] ?? null, JSON_UNESCAPED_UNICODE));
prueba('Sube lo mismo que subió el costo: ₡12 000 + (₡5 619,98 − ₡3 550,02), hacia arriba a ₡100',
    (float) $catalogo[1]['precioalto'] === ceil((12000 + $catalogo[1]['costoalto'] - $catalogo[1]['costo']) / 100) * 100
    && (float) $catalogo[1]['costoalto'] === 5619.98);
prueba('La rosa con relleno pasa de ₡3 500 a ₡4 000', (float) ($catalogo[15]['precioalto'] ?? 0) === 4000.0);

$r = $propietario->post('temporada', 'activar', []);
prueba('Enciende la temporada alta', exito($r), mensaje($r));
prueba('No deja encenderla dos veces', !exito($propietario->post('temporada', 'activar', [])));

$inventario = array_column(datos($propietario->get('inventario', 'listar')), null, 'tbinventarioid');
prueba('La rosa roja cuesta ₡583,33 y se vende a ₡900', (float) ($inventario[1]['preciounidadactual'] ?? 0) === 583.33
    && (float) ($inventario[1]['precioventaactual'] ?? 0) === 900.0 && ($inventario[1]['temporadaprecio'] ?? '') === 'Temporada alta',
    json_encode($inventario[1] ?? null, JSON_UNESCAPED_UNICODE));
prueba('Un producto sin precio del proveedor para la temporada mantiene su precio (anturio ₡800)',
    (float) ($inventario[34]['precioventaactual'] ?? 0) === 800.0 && ($inventario[34]['temporadaprecio'] ?? '') === '');
prueba('Los chocolates no cambian de precio', (float) ($inventario[24]['precioventaactual'] ?? 0) === 7000.0);

$catalogo = array_column(datos($propietario->get('catalogo', 'listar')), null, 'tbarreglofloralid');
prueba('Encendida, el Ramo Delicado se cobra a ₡14 100', (float) ($catalogo[1]['precioactual'] ?? 0) === 14100.0
    && ($catalogo[1]['altaencendida'] ?? false) === true);

$opciones = datos($lorena->get('encargo', 'opciones'));
$arreglosopcion = array_column($opciones['arreglos'] ?? [], null, 'tbarreglofloralid');
$productosopcion = array_column($opciones['productos'] ?? [], null, 'tbinventarioid');
prueba('Al cotizar se ven los dos precios del arreglo y del producto', (float) ($arreglosopcion[1]['tbarreglofloralprecio'] ?? 0) === 14100.0
    && (float) ($arreglosopcion[1]['precionormal'] ?? 0) === 12000.0 && (float) ($arreglosopcion[1]['precioalto'] ?? 0) === 14100.0
    && (float) ($productosopcion[1]['ventanormal'] ?? 0) === 600.0 && (float) ($productosopcion[1]['ventaalta'] ?? 0) === 900.0);

$r = $propietario->post('encargo', 'crear', [
    'tbclienteid' => 3, 'tbempleadoid' => '', 'tbsolicitudencargofechaentrega' => $manana, 'tbsolicitudencargonota' => '',
    'tbsolicitudencargotipo' => 'Predefinido', 'tbarreglofloralid' => 1, 'tbsolicitudencargocantidad' => 1,
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 2]],
]);
$idalta = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Predefinida en temporada alta: 14 100 + 2 rosas a 900 = 15 900', exito($r) && (float) (datos($r)['total'] ?? 0) === 15900.0, mensaje($r));
$alta = datos($propietario->get('encargo', 'obtener', ['id' => $idalta]));
prueba('…y guarda lo que costó en temporada alta (5 619,98 + 2 × 583,33 = 6 786,64)', (float) ($alta['tbsolicitudencargocosto'] ?? 0) === 6786.64);

$r = $propietario->post('encargo', 'crear', [
    'tbclienteid' => 3, 'tbempleadoid' => '', 'tbsolicitudencargofechaentrega' => $manana, 'tbsolicitudencargonota' => '',
    'tbsolicitudencargotipo' => 'Personalizado', 'tbsolicitudencargomodalidad' => 'Libre', 'tbsolicitudencargoporcentaje' => '100',
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 6], ['tbinventarioid' => 11, 'cantidad' => 2]],
]);
prueba('Personalizada en temporada alta al 100%: 6 rosas a 1 170 + 2 eucaliptos a 700 = 8 420', exito($r) && (float) (datos($r)['total'] ?? 0) === 8420.0, mensaje($r));

$pendientes = datos($propietario->get('inventario', 'pendientestemporada'));
$idspendientes = array_column($pendientes['pendientes'] ?? [], 'id');
prueba('El aviso del inventario solo muestra flores sin precio del proveedor (anturio sí, chocolates no)',
    in_array(34, $idspendientes, true) && !in_array(24, $idspendientes, true) && !in_array(1, $idspendientes, true),
    json_encode(array_column($pendientes['pendientes'] ?? [], 'nombre'), JSON_UNESCAPED_UNICODE));

$r = $propietario->post('temporada', 'guardarprecios', ['precios' => [34 => '0']]);
prueba('En la pantalla de temporada alta, el precio del paquete debe ser mayor que 0', !exito($r) && isset(errores($r)[34]), mensaje($r));
$r = $propietario->post('temporada', 'guardarprecios', ['precios' => [34 => '6000']]);
$inventario = array_column(datos($propietario->get('inventario', 'listar')), null, 'tbinventarioid');
prueba('Ahí se pone el precio del proveedor y la venta sube sola (anturio: ₡800 + ₡200 = ₡1 000)',
    exito($r) && (float) ($inventario[34]['precioventaactual'] ?? 0) === 1000.0, mensaje($r));

$r = $propietario->post('temporada', 'desactivar', []);
prueba('Apaga la temporada alta y vuelve a los precios normales', exito($r), mensaje($r));
$catalogo = array_column(datos($propietario->get('catalogo', 'listar')), null, 'tbarreglofloralid');
prueba('El Ramo Delicado vuelve a ₡12 000', (float) ($catalogo[1]['precioactual'] ?? 0) === 12000.0);
$solicitud = datos($propietario->get('encargo', 'obtener', ['id' => $idalta]));
prueba('La solicitud de temporada alta conserva su total y dice con qué precios se cotizó',
    (float) ($solicitud['tbsolicitudencargototal'] ?? 0) === 15900.0 && ($solicitud['tbtemporadanombre'] ?? '') === 'Temporada alta');

$historial = datos($propietario->get('temporada', 'historial'));
prueba('El historial muestra cuándo se encendió y se apagó', array_column($historial, 'nuevo') === ['Apagada', 'Encendida', 'Apagada'],
    json_encode(array_column($historial, 'nuevo'), JSON_UNESCAPED_UNICODE));

// Desde el formulario del producto
$cambio = $florvalida + ['tbinventarioid' => $idproducto, 'tbinventariopreciopaquete' => '12000', 'tbinventarioprecioventa' => '1200'];
$r = $propietario->post('inventario', 'actualizar', ['temporadas' => [1 => 'abc']] + $cambio);
prueba('El precio del proveedor en temporada alta debe ser un monto', tieneerror($r, 'temporadas'), mensaje($r));
$r = $propietario->post('inventario', 'actualizar', ['temporadas' => [1 => '15000']] + $cambio);
$producto = datos($propietario->get('inventario', 'obtener', ['id' => $idproducto]));
$preciosproducto = array_column($producto['preciostemporada'] ?? [], 'precio', 'tbtemporadaid');
prueba('Guarda lo que cobra el proveedor por el paquete en temporada alta', exito($r) && (float) ($preciosproducto[1] ?? 0) === 15000.0, mensaje($r));
$historial = datos($propietario->get('inventario', 'historial', ['id' => $idproducto]));
$temporadacambios = array_values(array_filter($historial, function ($cambio) { return $cambio['campo'] === 'preciotemporada'; }));
prueba('Queda en el historial del producto', count($temporadacambios) === 1
    && strpos($temporadacambios[0]['nuevo'], 'Temporada alta: ₡15 000,00 el paquete') === 0, json_encode($temporadacambios, JSON_UNESCAPED_UNICODE));

/* =====================================================================
 * 12. Spray y flores pintadas
 * =================================================================== */
seccion('12. Spray y flores pintadas');

$sprayvalido = [
    'tbproveedorid'                              => 3,
    'tbinventarionombre'                         => 'Spray de prueba rosado',
    'tbinventariocategoria'                      => 'Spray',
    'tbinventariopreciopaquete'                  => '30000',
    'tbinventariounidadespaquete'                => 6,
    'tbinventarioprecioventa'                    => '9000',
    'tbinventariostock'                          => 6,
    'tbinventariostockminimo'                    => 2,
    'tbinventariocategoriaspraytipo'             => 'Pintura floral',
    'tbinventariocategoriaspraymarca'            => 'Design Master',
    'tbinventariocategoriaspraycolor'            => 'Rosado',
    'tbinventariocategoriasprayaroma'            => '',
    'tbinventariocategoriaspraycontenido'        => 340,
    'tbinventariocategoriasprayfechavencimiento' => '',
    'tbinventariocategoriaspraycobroflor'        => '250',
];

$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaspraycolor' => '', 'tbinventariocategoriaspraycobroflor' => ''] + $sprayvalido);
prueba('La pintura floral pide el color y cuánto se cobra por flor',
    tieneerror($r, 'tbinventariocategoriaspraycolor') && tieneerror($r, 'tbinventariocategoriaspraycobroflor'));
$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaspraytipo' => 'Aromatizante floral'] + $sprayvalido);
prueba('El aromatizante pide el aroma', tieneerror($r, 'tbinventariocategoriasprayaroma'));
$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriasprayfechavencimiento' => $ayer] + $sprayvalido);
prueba('La fecha de vencimiento no puede ser anterior a hoy', tieneerror($r, 'tbinventariocategoriasprayfechavencimiento'));
$r = $propietario->post('inventario', 'crear', $sprayvalido);
$idspray = datos($r)['tbinventarioid'] ?? 0;
$spray = datos($propietario->get('inventario', 'obtener', ['id' => $idspray]));
prueba('Registra un spray de pintura floral', exito($r) && ($spray['categoria']['tbinventariocategoriaspraycolor'] ?? '') === 'Rosado'
    && (float) ($spray['categoria']['tbinventariocategoriaspraycobroflor'] ?? 0) === 250.0, mensaje($r));
$r = $propietario->post('inventario', 'crear', ['tbinventarionombre' => 'Abrillantador de prueba',
    'tbinventariocategoriaspraytipo' => 'Abrillantador de follaje'] + $sprayvalido);
$abrillantador = datos($propietario->get('inventario', 'obtener', ['id' => datos($r)['tbinventarioid'] ?? 0]));
$categoria = $abrillantador['categoria'] ?? [];
prueba('Al abrillantador no se le guarda color ni cobro por flor', exito($r)
    && array_key_exists('tbinventariocategoriaspraycolor', $categoria) && $categoria['tbinventariocategoriaspraycolor'] === null
    && array_key_exists('tbinventariocategoriaspraycobroflor', $categoria) && $categoria['tbinventariocategoriaspraycobroflor'] === null, mensaje($r));

$opciones = datos($lorena->get('encargo', 'opciones'));
prueba('El spray no aparece como producto para agregar, pero sí en las pinturas para flores',
    !in_array('Spray', array_column($opciones['productos'] ?? [], 'tbinventariocategoria'), true)
    && in_array($idspray, array_column($opciones['pinturas'] ?? [], 'tbinventarioid'), true)
    && !in_array(56, array_column($opciones['pinturas'] ?? [], 'tbinventarioid'), true));

$r = $propietario->post('catalogo', 'crear', ['tbarreglofloralnombre' => 'Arreglo con spray',
    'detalles' => [['tbinventarioid' => 54, 'tbarreglofloralinsumocantidad' => 1]]] + $arreglovalido, ['tbarreglofloralfoto' => $fotoprueba]);
prueba('El spray no se puede poner dentro de un arreglo del catálogo', tieneerror($r, 'detalles'), mensaje($r));

// Ramo Delicado: 6 rosas, 2 eucaliptos y 2 áster (10 flores y follajes)
$conflores = [
    'tbclienteid' => 4, 'tbempleadoid' => '', 'tbsolicitudencargofechaentrega' => $manana, 'tbsolicitudencargonota' => '',
    'tbsolicitudencargotipo' => 'Predefinido', 'tbarreglofloralid' => 1, 'tbsolicitudencargocantidad' => 1,
];
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargoflorespintadas' => '5'] + $conflores);
prueba('Para pintar flores hay que elegir la pintura', tieneerror($r, 'tbsolicitudencargosprayid'));
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargosprayid' => 56, 'tbsolicitudencargoflorespintadas' => '5'] + $conflores);
prueba('Solo se puede pintar con una pintura floral (no con el abrillantador)', tieneerror($r, 'tbsolicitudencargosprayid'));
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargosprayid' => 54, 'tbsolicitudencargoflorespintadas' => '15'] + $conflores);
prueba('No deja pintar más flores de las que lleva el arreglo (10)', tieneerror($r, 'tbsolicitudencargoflorespintadas'), mensaje($r));
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargosprayid' => 54, 'tbsolicitudencargoflorespintadas' => '8'] + $conflores);
$idpintada = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Cobra cada flor pintada: 12 000 + 8 flores × 300 = 14 400', exito($r) && (float) (datos($r)['total'] ?? 0) === 14400.0, mensaje($r));
$pintada = datos($propietario->get('encargo', 'obtener', ['id' => $idpintada]));
prueba('La solicitud guarda la pintura, las flores y el cobro', ($pintada['tbsolicitudencargospraynombre'] ?? '') === 'Spray pintura floral azul'
    && ($pintada['tbsolicitudencargoflorespintadas'] ?? 0) === 8 && (float) ($pintada['tbsolicitudencargocostopintura'] ?? 0) === 2400.0);

/* =====================================================================
 * 13. Accesorios (corona, perfume...) y ventas
 * =================================================================== */
seccion('13. Accesorios y ventas');

$accesoriovalido = [
    'tbproveedorid'                          => 6,
    'tbinventarionombre'                     => 'Corona grande plateada',
    'tbinventariocategoria'                  => 'Accesorio',
    'tbinventariopreciopaquete'              => '18000',
    'tbinventariounidadespaquete'            => 12,
    'tbinventarioprecioventa'                => '3000',
    'tbinventariostock'                      => 12,
    'tbinventariostockminimo'                => 3,
    'tbinventariocategoriaaccesoriotipo'     => 'Corona',
    'tbinventariocategoriaaccesoriotamano'   => 'Grande',
    'tbinventariocategoriaaccesoriocolor'    => 'Plateado',
    'tbinventariocategoriaaccesoriomaterial' => '',
];
$r = $propietario->post('inventario', 'crear', ['tbinventariocategoriaaccesoriotipo' => '', 'tbinventariocategoriaaccesoriotamano' => ''] + $accesoriovalido);
prueba('El accesorio pide el tipo y el tamaño', tieneerror($r, 'tbinventariocategoriaaccesoriotipo') && tieneerror($r, 'tbinventariocategoriaaccesoriotamano'));
$r = $propietario->post('inventario', 'crear', $accesoriovalido);
$idcorona = datos($r)['tbinventarioid'] ?? 0;
$corona = datos($propietario->get('inventario', 'obtener', ['id' => $idcorona]));
prueba('Registra una corona (el material es opcional)', exito($r) && ($corona['categoria']['tbinventariocategoriaaccesoriotipo'] ?? '') === 'Corona'
    && array_key_exists('tbinventariocategoriaaccesoriomaterial', $corona['categoria'] ?? []) && $corona['categoria']['tbinventariocategoriaaccesoriomaterial'] === null, mensaje($r));
$opciones = datos($lorena->get('encargo', 'opciones'));
prueba('Los accesorios se pueden agregar a una solicitud (corona pequeña ₡1 000)',
    in_array('Accesorio', array_column($opciones['productos'] ?? [], 'tbinventariocategoria'), true)
    && (float) (array_column($opciones['productos'] ?? [], 'tbinventarioprecioventa', 'tbinventarioid')[62] ?? 0) === 1000.0);

$r = $lorena->get('venta', 'resumen');
prueba('Un empleado no puede ver las ventas (403)', $r['codigo'] === 403, 'HTTP ' . $r['codigo']);

$r = $propietario->get('venta', 'resumen', ['desde' => '2026-09-01', 'hasta' => '2026-09-30']);
$setiembre = datos($r);
prueba('Ventas de setiembre: 2 terminadas, vendido ₡31 500, costo ₡9 108,37 y ganancia ₡22 391,63',
    exito($r) && ($setiembre['totales']['ventas'] ?? 0) === 2 && (float) ($setiembre['totales']['vendido'] ?? 0) === 31500.0
    && (float) ($setiembre['totales']['costo'] ?? 0) === 9108.37 && (float) ($setiembre['totales']['ganancia'] ?? 0) === 22391.63,
    json_encode($setiembre['totales'] ?? null));
prueba('Cada venta trae su ganancia (total − costo)', (float) ($setiembre['ventas'][0]['ganancia'] ?? 0)
    === round($setiembre['ventas'][0]['total'] - $setiembre['ventas'][0]['costo'], 2));

$r = $propietario->get('venta', 'resumen', ['desde' => '2026-10-01', 'hasta' => '2026-10-17']);
prueba('Ventas del 1 al 17 de octubre: 9 terminadas por ₡191 210', ((datos($r)['totales']['ventas'] ?? 0) === 9)
    && (float) (datos($r)['totales']['vendido'] ?? 0) === 191210.0, json_encode(datos($r)['totales'] ?? null));

$r = $propietario->get('venta', 'resumen', ['desde' => '2026-10-17', 'hasta' => '2026-10-01']);
prueba('La fecha "desde" no puede ser posterior a "hasta"', !exito($r), mensaje($r));
$r = $propietario->get('venta', 'resumen', ['desde' => '2026-13-01', 'hasta' => '2026-10-01']);
prueba('Rechaza fechas que no existen', !exito($r), mensaje($r));

$r = $propietario->get('venta', 'resumen', ['desde' => $hoy, 'hasta' => $hoy]);
$ids = array_column(datos($r)['ventas'] ?? [], 'tbsolicitudencargoid');
prueba('Las solicitudes que se terminan hoy aparecen en las ventas de hoy', in_array($idencargo, $ids, true) && in_array($idpersonalizada, $ids, true),
    json_encode($ids));
$r = $propietario->get('venta', 'resumen', ['desde' => 'todo']);
prueba('"Todo" empieza en la primera venta (20/09/2026)', (datos($r)['desde'] ?? '') === '2026-09-20', datos($r)['desde'] ?? '');

/* =====================================================================
 * 14. Presupuesto y foto de los personalizados (Parte 11)
 * =================================================================== */
seccion('14. Presupuesto y foto de los personalizados');

// 4 rosas rojas al 100% = 4 × 590 = 2 360; la pintura azul cobra 300 por flor
$conpresupuesto = [
    'tbclienteid' => 6, 'tbempleadoid' => '2', 'tbsolicitudencargofechaentrega' => $manana, 'tbsolicitudencargonota' => '',
    'tbsolicitudencargotipo' => 'Personalizado', 'tbsolicitudencargomodalidad' => 'Presupuesto', 'tbsolicitudencargopresupuesto' => '3000',
    'tbsolicitudencargoporcentaje' => '100', 'tbsolicitudencargosprayid' => 54,
    'insumos' => [['tbinventarioid' => 1, 'cantidad' => 4]],
];
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargoflorespintadas' => '3'] + $conpresupuesto);
prueba('Las flores pintadas también entran en el presupuesto (2 360 + 3 × 300 = 3 260 > 3 000)',
    tieneerror($r, 'tbsolicitudencargoflorespintadas') && strpos(errores($r)['tbsolicitudencargoflorespintadas'] ?? '', 'presupuesto') !== false, mensaje($r));
$r = $propietario->post('encargo', 'crear', ['tbsolicitudencargoflorespintadas' => '2'] + $conpresupuesto);
$idfoto = datos($r)['tbsolicitudencargoid'] ?? 0;
prueba('Con 2 flores pintadas sí alcanza: 2 360 + 600 = 2 960', exito($r) && (float) (datos($r)['total'] ?? 0) === 2960.0, mensaje($r));

$r = $propietario->get('encargo', 'subirfoto', ['id' => $idfoto]);
prueba('Subir la foto solo acepta POST (405)', $r['codigo'] === 405, 'HTTP ' . $r['codigo']);
$r = $propietario->post('encargo', 'subirfoto', ['id' => $idfoto]);
prueba('Pide la foto si no se eligió', !exito($r) && tieneerror($r, 'tbsolicitudencargofoto'), mensaje($r));
$r = $propietario->post('encargo', 'subirfoto', ['id' => $idfoto], ['tbsolicitudencargofoto' => __FILE__]);
prueba('Rechaza archivos que no son imágenes', !exito($r) && tieneerror($r, 'tbsolicitudencargofoto'), mensaje($r));
$r = $propietario->post('encargo', 'subirfoto', ['id' => 1], ['tbsolicitudencargofoto' => $fotoprueba]);
prueba('Los predefinidos no llevan foto propia (ya tienen la del catálogo)', !exito($r), mensaje($r));
$r = $carlos->post('encargo', 'subirfoto', ['id' => $idfoto], ['tbsolicitudencargofoto' => $fotoprueba]);
prueba('Un empleado no puede subir la foto de una solicitud de otra persona', !exito($r), mensaje($r));

$r = $lorena->post('encargo', 'subirfoto', ['id' => $idfoto], ['tbsolicitudencargofoto' => $fotoprueba]);
$conf = datos($propietario->get('encargo', 'obtener', ['id' => $idfoto]));
$primerafoto = $conf['tbsolicitudencargofoto'] ?? '';
prueba('La empleada asignada sube la foto del arreglo', exito($r) && strpos($primerafoto, 'imagenes/solicitudes/') === 0
    && is_file(__DIR__ . '/../Publico/' . $primerafoto), mensaje($r));
prueba('Queda en el historial de la solicitud', in_array('Se agregó la foto del arreglo', array_column($conf['historial'] ?? [], 'texto'), true));
$lista = array_column(datos($propietario->get('encargo', 'listar')), null, 'tbsolicitudencargoid');
prueba('La lista de solicitudes trae la foto', ($lista[$idfoto]['tbsolicitudencargofoto'] ?? '') === $primerafoto);

$r = $propietario->post('encargo', 'subirfoto', ['id' => $idfoto], ['tbsolicitudencargofoto' => $fotoprueba]);
$conf = datos($propietario->get('encargo', 'obtener', ['id' => $idfoto]));
clearstatcache();
prueba('El propietario la cambia y se borra la foto anterior', exito($r) && ($conf['tbsolicitudencargofoto'] ?? '') !== $primerafoto
    && !is_file(__DIR__ . '/../Publico/' . $primerafoto), mensaje($r));
prueba('…y el historial dice que se cambió', in_array('Se cambió la foto del arreglo', array_column($conf['historial'] ?? [], 'texto'), true));

$r = $propietario->post('encargo', 'actualizar', ['tbsolicitudencargoid' => $idfoto, 'tbsolicitudencargoflorespintadas' => '1'] + $conpresupuesto);
$conf2 = datos($propietario->get('encargo', 'obtener', ['id' => $idfoto]));
prueba('Editar la solicitud no le quita la foto', exito($r) && ($conf2['tbsolicitudencargofoto'] ?? '') === ($conf['tbsolicitudencargofoto'] ?? 'x'), mensaje($r));

$propietario->post('encargo', 'cancelar', ['id' => $idfoto]);
$r = $propietario->post('encargo', 'subirfoto', ['id' => $idfoto], ['tbsolicitudencargofoto' => $fotoprueba]);
prueba('A una solicitud cancelada no se le agrega foto', !exito($r), mensaje($r));

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