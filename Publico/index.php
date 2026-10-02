<?php
require_once __DIR__ . '/../Configuracion/Configuracion.php';
require_once __DIR__ . '/../Aplicacion/Controlador/proveedorcontrolador.php';

$modulo = $_GET['modulo'] ?? 'proveedor';
$accion = $_GET['accion'] ?? '';

if ($accion === '') {
    $vistas = [
        'proveedor' => 'proveedorvista.php',
    ];

    if (!isset($vistas[$modulo])) {
        http_response_code(404);
        echo 'Página no encontrada.';
        exit;
    }

    require __DIR__ . '/../Aplicacion/Vista/' . $vistas[$modulo];
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$accionespost = ['crear', 'actualizar', 'inactivar', 'reactivar'];

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
                    $respuesta = $controlador->crear($_POST);
                    break;
                case 'actualizar':
                    $respuesta = $controlador->actualizar($_POST);
                    break;
                case 'inactivar':
                    $respuesta = $controlador->inactivar($_POST['id'] ?? 0);
                    break;
                case 'reactivar':
                    $respuesta = $controlador->reactivar($_POST['id'] ?? 0);
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

echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);