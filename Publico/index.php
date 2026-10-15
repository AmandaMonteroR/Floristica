<?php
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Aplicacion/Controlador/proveedorcontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/inventariocontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/pedidocontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/arreglofloralcontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/empleadocontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/encargocontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/clientecontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/zonaentregacontrolador.php';
require_once __DIR__ . '/../Aplicacion/Controlador/sesioncontrolador.php';
require_once __DIR__ . '/../Configuracion/Seguridad.php';

Seguridad::iniciar();

$modulo = $_GET['modulo'] ?? '';
$accion = $_GET['accion'] ?? '';

function responderjson($respuesta, $codigo = 200)
{
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

// ---------- Iniciar y cerrar sesión (no requieren estar conectado) ----------
if ($modulo === 'sesion') {
    if ($accion === 'cerrar') {
        Seguridad::cerrar();
        header('Location: index.php');
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        responderjson(['exito' => false, 'mensaje' => 'Método no permitido.', 'datos' => []], 405);
    }

    $sesion = new sesioncontrolador();

    try {
        switch ($accion) {
            case 'ingresar':
                responderjson($sesion->ingresar($_POST));
                break;
            case 'registrarpropietario':
                responderjson($sesion->registrarpropietario($_POST));
                break;
            default:
                responderjson(['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []], 404);
        }
    } catch (PDOException $excepcion) {
        responderjson(['exito' => false, 'mensaje' => 'No se pudo conectar con la base de datos. Intente de nuevo.', 'datos' => []], 500);
    }
}

// ---------- Sin sesión: se muestra el login ----------
$usuarioactual = Seguridad::usuario();

if ($usuarioactual === null) {
    if ($accion !== '') {
        responderjson(['exito' => false, 'mensaje' => 'Su sesión terminó. Vuelva a ingresar.', 'datos' => ['sesion' => false]], 401);
    }

    $primeravez = (new sesioncontrolador())->primeravez();
    require __DIR__ . '/../Aplicacion/Vista/loginvista.php';
    exit;
}

// Si no se pide un módulo, se muestra la página de inicio
if ($modulo === '') {
    $modulo = 'inicio';
}

// ---------- Permisos por rol ----------
if (!Seguridad::permitido($modulo, $accion)) {
    if ($accion === '') {
        http_response_code(403);
        require __DIR__ . '/../Aplicacion/Vista/sinpermisovista.php';
        exit;
    }

    responderjson(['exito' => false, 'mensaje' => 'No tiene permiso para realizar esta acción.', 'datos' => []], 403);
}

if ($accion === '') {
    $vistas = [
        'inicio'     => 'iniciovista.php',
        'proveedor'  => 'proveedorvista.php',
        'inventario' => 'inventariovista.php',
        'pedido'     => 'pedidovista.php',
        'catalogo'   => 'arreglofloralvista.php',
        'encargo'    => 'encargovista.php',
        'empleado'   => 'empleadovista.php',
        'cliente'    => 'clientevista.php',
        'zonaentrega' => 'zonaentregavista.php',
    ];

    if (!isset($vistas[$modulo]) || !is_file(__DIR__ . '/../Aplicacion/Vista/' . $vistas[$modulo])) {
        http_response_code(404);
        echo 'Página no encontrada.';
        exit;
    }

    require __DIR__ . '/../Aplicacion/Vista/' . $vistas[$modulo];
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$accionespost = ['crear', 'actualizar', 'inactivar', 'reactivar', 'confirmar', 'recibir',
    'asignar', 'asignarme', 'iniciar', 'terminar', 'cancelar', 'cambiarclave'];

if (in_array($accion, $accionespost) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['exito' => false, 'mensaje' => 'Método no permitido.', 'datos' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    switch ($modulo) {
        case 'proveedor':
            $controlador = new proveedorcontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['texto'] ?? '', $_GET['estado'] ?? null);
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'ubicaciones':
                    $respuesta = $controlador->ubicaciones();
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $usuarioactual);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        case 'inventario':
            $controlador = new inventariocontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['texto'] ?? '', $_GET['categoria'] ?? '', $_GET['estado'] ?? null);
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'proveedores':
                    $respuesta = $controlador->proveedores();
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $usuarioactual);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        case 'pedido':
            $controlador = new pedidocontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['proveedor'] ?? 0, $_GET['estado'] ?? '');
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'proveedores':
                    $respuesta = $controlador->proveedores();
                    break;
                case 'productos':
                    $respuesta = $controlador->productos($_GET['proveedor'] ?? 0);
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST);
                    break;
                case 'confirmar':
                    $respuesta = $controlador->confirmar($_POST, $usuarioactual);
                    break;
                case 'recibir':
                    $respuesta = $controlador->recibir($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        case 'catalogo':
            $controlador = new arreglofloralcontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['texto'] ?? '', $_GET['estado'] ?? null);
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'productos':
                    $respuesta = $controlador->productos();
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $_FILES, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $_FILES, $usuarioactual);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        case 'empleado':
            $controlador = new empleadocontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['texto'] ?? '', $_GET['estado'] ?? null);
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $usuarioactual);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        case 'encargo':
            $controlador = new encargocontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['estado'] ?? '', $_GET['empleado'] ?? '');
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'arreglos':
                    $respuesta = $controlador->arreglos();
                    break;
                case 'empleados':
                    $respuesta = $controlador->empleados();
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $usuarioactual);
                    break;
                case 'asignar':
                    $respuesta = $controlador->asignar($_POST, $usuarioactual);
                    break;
                case 'asignarme':
                    $respuesta = $controlador->asignarme($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'iniciar':
                    $respuesta = $controlador->iniciar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'terminar':
                    $respuesta = $controlador->terminar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'cancelar':
                    $respuesta = $controlador->cancelar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;
        // Clientes y zonas de entrega tienen las mismas acciones
        case 'cliente':
        case 'zonaentrega':
            $controlador = $modulo === 'cliente' ? new clientecontrolador() : new zonaentregacontrolador();

            switch ($accion) {
                case 'listar':
                    $respuesta = $controlador->listar($_GET['texto'] ?? '', $_GET['estado'] ?? null);
                    break;
                case 'obtener':
                    $respuesta = $controlador->obtener($_GET['id'] ?? 0);
                    break;
                case 'historial':
                    $respuesta = $controlador->historial($_GET['id'] ?? 0);
                    break;
                case 'crear':
                    $respuesta = $controlador->crear($_POST, $usuarioactual);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST, $usuarioactual);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0, $usuarioactual);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;

        // Cuenta del usuario conectado: cada uno cambia solo su propia contraseña
        case 'cuenta':
            $controlador = new empleadocontrolador();

            switch ($accion) {
                case 'cambiarclave':
                    $respuesta = $controlador->cambiarclave($_POST, $usuarioactual);
                    break;
                default:
                    $respuesta = ['exito' => false, 'mensaje' => 'Acción no válida.', 'datos' => []];
            }
            break;
        default:
            $respuesta = ['exito' => false, 'mensaje' => 'Módulo no válido.', 'datos' => []];
    }
} catch (PDOException $excepcion) {
    http_response_code(500);
    $respuesta = ['exito' => false, 'mensaje' => 'No se pudo completar la operación en la base de datos. Intente de nuevo.', 'datos' => []];
} catch (RuntimeException $excepcion) {
    http_response_code(500);
    $respuesta = ['exito' => false, 'mensaje' => $excepcion->getMessage(), 'datos' => []];
}

// Las empleadas no reciben los costos ni las ganancias de los arreglos
if ($modulo === 'catalogo' && !Seguridad::espropietario() && !empty($respuesta['datos'])) {
    $quitarcostos = function ($arreglo) {
        unset($arreglo['costo']);
        foreach ($arreglo['detalles'] ?? [] as $indice => $detalle) {
            unset($arreglo['detalles'][$indice]['tbinventariopreciounidad']);
        }
        return $arreglo;
    };

    $respuesta['datos'] = isset($respuesta['datos']['tbarreglofloralid'])
        ? $quitarcostos($respuesta['datos'])
        : array_map($quitarcostos, $respuesta['datos']);
}

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);