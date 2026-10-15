<?php
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/zonaentregarepositorio.php';
require_once __DIR__ . '/../Repositorio/encargohistoricorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Solicitudes de encargo:
// - Predefinida: un arreglo del catálogo (el cliente lo eligió del catálogo de WhatsApp) + adicionales.
// - Personalizada: el cliente elige los productos. "Presupuesto": no se puede pasar del monto que dio.
//   "Libre": se eligen los productos y el sistema calcula el total para cotizarle.
// - Express: se cobra el precio de la zona de entrega (lo define el propietario).
class encargocontrolador
{
    private $repositorio;
    private $empleados;
    private $arreglos;
    private $inventario;
    private $clientes;
    private $zonas;
    private $historico;
    private $movimientos;
    private $generales;

    public function __construct()
    {
        $this->repositorio = new encargorepositorio();
        $this->empleados = new empleadorepositorio();
        $this->arreglos = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
        $this->clientes = new clienterepositorio();
        $this->zonas = new zonaentregarepositorio();
        $this->historico = new encargohistoricorepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->generales = new historicocontrolador();
    }

    // Todo lo que se necesita para llenar el formulario, en una sola consulta
    public function opciones()
    {
        $datos = ['arreglos' => [], 'empleados' => [], 'clientes' => [], 'zonas' => [], 'productos' => []];

        foreach ($this->arreglos->listar('', 1) as $arreglo) {
            $datos['arreglos'][] = [
                'tbarreglofloralid'     => (int) $arreglo->getTbarreglofloralid(),
                'tbarreglofloralnombre' => $arreglo->getTbarreglofloralnombre(),
                'tbarreglofloralprecio' => (float) $arreglo->getTbarreglofloralprecio(),
                'tbarreglofloralfoto'   => $arreglo->getTbarreglofloralfoto(),
            ];
        }

        foreach ($this->empleados->listar('', 1) as $empleado) {
            $datos['empleados'][] = [
                'tbempleadoid'     => (int) $empleado->getTbempleadoid(),
                'tbempleadonombre' => $empleado->getTbempleadonombre(),
                'tbempleadorol'    => $empleado->getTbempleadorol(),
            ];
        }

        foreach ($this->clientes->listar('', 1) as $cliente) {
            $datos['clientes'][] = [
                'tbclienteid'        => (int) $cliente->getTbclienteid(),
                'tbclientenombre'    => $cliente->getTbclientenombre(),
                'tbclientetelefono'  => $cliente->getTbclientetelefono(),
                'tbclientedireccion' => (string) $cliente->getTbclientedireccion(),
            ];
        }

        foreach ($this->zonas->listar('', 1) as $zona) {
            $datos['zonas'][] = [
                'tbzonaentregaid'     => (int) $zona->getTbzonaentregaid(),
                'tbzonaentreganombre' => $zona->getTbzonaentreganombre(),
                'tbzonaentregaprecio' => (float) $zona->getTbzonaentregaprecio(),
            ];
        }

        foreach ($this->inventario->listar('', '', 1) as $producto) {
            $datos['productos'][] = [
                'tbinventarioid'          => (int) $producto->getTbinventarioid(),
                'tbinventarionombre'      => $producto->getTbinventarionombre(),
                'tbinventariocategoria'   => $producto->getTbinventariocategoria(),
                'tbinventarioprecioventa' => (float) $producto->getTbinventarioprecioventa(),
                'tbinventariostock'       => (int) $producto->getTbinventariostock(),
            ];
        }

        return $this->respuesta(true, '', $datos);
    }

    public function listar($estado = '', $idempleado = '', $tipo = '')
    {
        $nombres = $this->nombres();

        $lista = [];
        foreach ($this->repositorio->listar((string) $estado, (string) $idempleado, (string) $tipo) as $encargo) {
            $lista[] = $this->convertirarreglo($encargo, $nombres);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        $datos = $this->convertirarreglo($encargo, $this->nombres());
        $datos['insumos'] = $this->convertirinsumos($encargo);
        $datos['materiales'] = $this->materiales($encargo);
        $datos['historial'] = $this->historial($encargo->getTbsolicitudencargoid());

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);

        // Un empleado solo puede dejarla sin asignar o asignársela a sí mismo
        if ($usuario['rol'] !== 'Propietario' && !in_array($datos['tbempleadoid'], ['', (string) $usuario['id']], true)) {
            return $this->respuesta(false, 'Revise los datos ingresados.',
                ['errores' => ['tbempleadoid' => 'Solo puede asignarse la solicitud a usted mismo.']]);
        }

        $errores = $this->validar($datos, null);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $encargo = new Encargo($datos);

        $id = $this->entransaccion(function () use ($encargo, $datos, $usuario) {
            $id = $this->repositorio->insertar($encargo);
            $this->repositorio->guardarinsumos($id, $datos['lineas']);
            $this->historico->registrar('estado', $id, 'Pendiente', $usuario['id']);
            $this->generales->registrar('solicitudencargo', $id, [], $this->datoshistorico($encargo), $usuario['id']);

            if ($encargo->getTbempleadoid() !== null) {
                $this->historico->registrar('empleado', $id, (int) $encargo->getTbempleadoid(), $usuario['id']);
            }

            return $id;
        });

        return $this->respuesta(true, 'Solicitud de encargo N.º ' . $id . ' registrada. Total: '
            . historicocontrolador::monto($encargo->getTbsolicitudencargototal()) . '.',
            ['tbsolicitudencargoid' => $id, 'total' => (float) $encargo->getTbsolicitudencargototal()]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbsolicitudencargoid'] ?? 0);
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden editar solicitudes pendientes.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $encargo);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($encargo);

        // La asignación no se cambia aquí, sino con "Asignar"
        $datos['tbempleadoid'] = $encargo->getTbempleadoid();
        $datos['tbsolicitudencargoid'] = $id;
        $datos['tbsolicitudencargoestado'] = $encargo->getTbsolicitudencargoestado();
        $datos['tbsolicitudencargofecharegistro'] = $encargo->getTbsolicitudencargofecharegistro();
        $nuevo = new Encargo($datos);

        $this->entransaccion(function () use ($nuevo, $datos, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($nuevo);
            $this->repositorio->guardarinsumos($id, $datos['lineas']);
            $this->generales->registrar('solicitudencargo', $id, $anteriores, $this->datoshistorico($nuevo), $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud de encargo N.º ' . $id . ' actualizada. Total: '
            . historicocontrolador::monto($nuevo->getTbsolicitudencargototal()) . '.');
    }

    public function asignar($datos, $usuario)
    {
        $id = (int) ($datos['tbsolicitudencargoid'] ?? 0);
        $idempleado = trim((string) ($datos['tbempleadoid'] ?? ''));
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden asignar solicitudes pendientes o en proceso.');
        }

        $error = $this->validarempleado($idempleado);
        if ($error !== '') {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => ['tbempleadoid' => $error]]);
        }

        $empleado = $this->empleados->obtenerporid((int) $idempleado);

        // Si se elige a la misma persona no hay nada que cambiar ni que guardar en el historial
        if ((int) $encargo->getTbempleadoid() === (int) $idempleado) {
            return $this->respuesta(true, 'La solicitud ya estaba asignada a ' . $empleado->getTbempleadonombre() . '.');
        }

        $this->entransaccion(function () use ($id, $idempleado, $usuario) {
            $this->repositorio->asignar($id, (int) $idempleado);
            $this->historico->registrar('empleado', $id, (int) $idempleado, $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud asignada a ' . $empleado->getTbempleadonombre() . '.');
    }

    // Quien tiene la sesión abierta (propietario o empleado) se asigna la solicitud
    public function asignarme($id, $usuario)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if ($encargo && $usuario['rol'] !== 'Propietario' && $encargo->getTbempleadoid()
            && (int) $encargo->getTbempleadoid() !== (int) $usuario['id']) {
            return $this->respuesta(false, 'Esta solicitud ya está asignada a otra persona.');
        }

        return $this->asignar(['tbsolicitudencargoid' => $id, 'tbempleadoid' => (string) $usuario['id']], $usuario);
    }

    // Un empleado solo puede mover sus propias solicitudes
    private function esdeotro(Encargo $encargo, $usuario)
    {
        return $usuario['rol'] !== 'Propietario' && (int) $encargo->getTbempleadoid() !== (int) $usuario['id'];
    }

    public function iniciar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden iniciar solicitudes pendientes.');
        }

        if (!$encargo->getTbempleadoid()) {
            return $this->respuesta(false, 'Asigne la solicitud a alguien antes de iniciarla.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede iniciar las solicitudes asignadas a usted.');
        }

        $this->cambiarestado($id, 'En proceso', $usuario);

        return $this->respuesta(true, 'La solicitud pasó a "En proceso".');
    }

    public function terminar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede terminar las solicitudes asignadas a usted.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'En proceso') {
            return $this->respuesta(false, 'Solo se pueden terminar solicitudes en proceso.');
        }

        $materiales = $this->materiales($encargo);
        $faltantes = [];
        foreach ($materiales as $material) {
            if (!$material['alcanza']) {
                $faltantes[] = $material['tbinventarionombre'] . ' (faltan ' . ($material['necesarias'] - $material['stock']) . ')';
            }
        }

        if ($faltantes) {
            return $this->respuesta(false, 'No alcanza el inventario para terminarla: ' . implode(', ', $faltantes) . '.');
        }

        $this->entransaccion(function () use ($materiales, $id, $usuario) {
            foreach ($materiales as $material) {
                if (!$this->inventario->restarstock($material['tbinventarioid'], $material['necesarias'])) {
                    throw new RuntimeException('El stock de ' . $material['tbinventarionombre'] . ' cambió y ya no alcanza. Intente de nuevo.');
                }

                $this->movimientos->registrar($material['tbinventarioid'], 'Salida', -$material['necesarias'],
                    $usuario['id'], null, $id);
            }

            $this->repositorio->cambiarestado($id, 'Terminado');
            $this->historico->registrar('estado', $id, 'Terminado', $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud terminada. Se descontaron los productos del inventario.');
    }

    public function cancelar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de encargo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden cancelar solicitudes pendientes o en proceso.');
        }

        $this->cambiarestado($id, 'Cancelado', $usuario);

        return $this->respuesta(true, 'Solicitud cancelada.');
    }

    // Cambia el estado y lo deja en el historial con quién lo hizo
    private function cambiarestado($id, $estado, $usuario)
    {
        $this->entransaccion(function () use ($id, $estado, $usuario) {
            $this->repositorio->cambiarestado($id, $estado);
            $this->historico->registrar('estado', $id, $estado, $usuario['id']);
        });
    }

    // Datos de la solicitud que llevan histórico general (tbsolicitudencargo<campo>historico)
    private function datoshistorico(Encargo $encargo)
    {
        return [
            'fechaentrega' => $encargo->getTbsolicitudencargofechaentrega(),
            'total'        => number_format((float) $encargo->getTbsolicitudencargototal(), 2, '.', ''),
        ];
    }

    // Historial de la solicitud en palabras, para mostrarlo en "Ver"
    private function historial($idencargo)
    {
        $lista = [];
        foreach ($this->historico->listar($idencargo) as $fila) {
            if ($fila['campo'] === 'estado') {
                $texto = $fila['valor'] === 'Pendiente' && !$lista
                    ? 'Se registró la solicitud'
                    : 'Pasó a "' . $fila['valor'] . '"';
            } else {
                $texto = 'Se asignó a ' . ($fila['nombreasignado'] ?? 'un empleado eliminado');
            }

            $lista[] = [
                'fecha'    => $fila['fecha'],
                'texto'    => $texto,
                'hechopor' => $fila['hechopor'] ?? '',
            ];
        }

        // Cambios de fecha de entrega y de total. El primer valor es el del registro, así que no se repite.
        $formatear = function ($campo, $valor) {
            return $campo === 'total' ? historicocontrolador::monto($valor) : date('d/m/Y', strtotime($valor));
        };
        $etiquetas = ['fechaentrega' => 'La fecha de entrega', 'total' => 'El total'];

        foreach ($this->generales->listar('solicitudencargo', $idencargo, $etiquetas, $formatear) as $cambio) {
            if ($cambio['anterior'] === null) {
                continue;
            }

            $lista[] = [
                'fecha'    => $cambio['fecha'],
                'texto'    => $cambio['etiqueta'] . ' cambió de ' . $cambio['anterior'] . ' a ' . $cambio['nuevo'],
                'hechopor' => $cambio['hechopor'],
            ];
        }

        usort($lista, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        return $lista;
    }

    // Productos que hay que sacar del inventario para prepararla, y si alcanza el stock.
    // Predefinida: los del arreglo × cantidad, más los adicionales. Personalizada: los que eligió el cliente.
    private function materiales(Encargo $encargo)
    {
        $necesarias = [];

        if (!$encargo->esPersonalizado() && $encargo->getTbarreglofloralid()) {
            foreach ($this->arreglos->listardetalles($encargo->getTbarreglofloralid()) as $detalle) {
                $idproducto = (int) $detalle->getTbinventarioid();
                $necesarias[$idproducto] = ($necesarias[$idproducto] ?? 0)
                    + (int) $detalle->getTbarreglofloralinsumocantidad() * (int) $encargo->getTbsolicitudencargocantidad();
            }
        }

        foreach ($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()) as $insumo) {
            $idproducto = (int) $insumo->getTbinventarioid();
            $necesarias[$idproducto] = ($necesarias[$idproducto] ?? 0) + (int) $insumo->getTbsolicitudencargoinsumocantidad();
        }

        $materiales = [];
        foreach ($necesarias as $idproducto => $cantidad) {
            $producto = $this->inventario->obtenerporid($idproducto);
            $stock = $producto ? (int) $producto->getTbinventariostock() : 0;

            $materiales[] = [
                'tbinventarioid'     => $idproducto,
                'tbinventarionombre' => $producto ? $producto->getTbinventarionombre() : 'Producto eliminado',
                'necesarias'         => $cantidad,
                'stock'              => $stock,
                'alcanza'            => $stock >= $cantidad,
            ];
        }

        return $materiales;
    }

    private function convertirinsumos(Encargo $encargo)
    {
        $lista = [];
        foreach ($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()) as $insumo) {
            $producto = $this->inventario->obtenerporid($insumo->getTbinventarioid());
            $cantidad = (int) $insumo->getTbsolicitudencargoinsumocantidad();
            $precio = (float) $insumo->getTbsolicitudencargoinsumopreciounitario();

            $lista[] = [
                'tbinventarioid'        => (int) $insumo->getTbinventarioid(),
                'tbinventarionombre'    => $producto ? $producto->getTbinventarionombre() : 'Producto eliminado',
                'tbinventariocategoria' => $producto ? $producto->getTbinventariocategoria() : '',
                'cantidad'              => $cantidad,
                'preciounitario'        => $precio,
                'subtotal'              => round($cantidad * $precio, 2),
            ];
        }

        return $lista;
    }

    private function validarempleado($idempleado)
    {
        if ($idempleado === '') {
            return 'Seleccione a quién se le asigna.';
        }

        $empleado = ctype_digit($idempleado) ? $this->empleados->obtenerporid((int) $idempleado) : null;

        if (!$empleado) {
            return 'El empleado seleccionado no existe.';
        }

        if (!$empleado->estaActivo()) {
            return 'El empleado seleccionado está inactivo.';
        }

        return '';
    }

    private function entransaccion($operacion)
    {
        $conexion = Basedatos::conectar();
        $conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $conexion->commit();
            return $resultado;
        } catch (Throwable $excepcion) {
            $conexion->rollBack();
            throw $excepcion;
        }
    }

    private function limpiar($datos)
    {
        $texto = function ($campo) use ($datos) {
            return trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        };

        $limpios = [
            'tbclienteid'                        => $texto('tbclienteid'),
            'tbempleadoid'                       => $texto('tbempleadoid'),
            'tbsolicitudencargotipo'             => $texto('tbsolicitudencargotipo'),
            'tbarreglofloralid'                  => $texto('tbarreglofloralid'),
            'tbsolicitudencargocantidad'         => $texto('tbsolicitudencargocantidad'),
            'tbsolicitudencargomodalidad'        => $texto('tbsolicitudencargomodalidad'),
            'tbsolicitudencargopresupuesto'      => str_replace(',', '.', $texto('tbsolicitudencargopresupuesto')),
            'tbsolicitudencargoexpress'          => ($datos['tbsolicitudencargoexpress'] ?? '') === '1' ? 1 : 0,
            'tbzonaentregaid'                    => $texto('tbzonaentregaid'),
            'tbsolicitudencargodireccionentrega' => $texto('tbsolicitudencargodireccionentrega'),
            'tbsolicitudencargofechaentrega'     => $texto('tbsolicitudencargofechaentrega'),
            'tbsolicitudencargonota'             => $texto('tbsolicitudencargonota'),
            'insumos'                            => [],
        ];

        // Productos: insumos[0][tbinventarioid], insumos[0][cantidad]... Las filas vacías se ignoran.
        foreach ((array) ($datos['insumos'] ?? []) as $fila) {
            $producto = trim((string) ($fila['tbinventarioid'] ?? ''));
            $cantidad = trim((string) ($fila['cantidad'] ?? ''));

            if ($producto !== '' || $cantidad !== '') {
                $limpios['insumos'][] = ['tbinventarioid' => $producto, 'cantidad' => $cantidad];
            }
        }

        return $limpios;
    }

    // Valida y, si todo está bien, completa los precios y totales en $datos.
    // $actual es la solicitud que se está editando (null al crear): lo que ya tenía
    // se acepta aunque después se haya inactivado y conserva el precio con que se cotizó.
    private function validar(&$datos, $actual)
    {
        $errores = [];
        $esnuevo = $actual === null;

        // Cliente
        $cliente = ctype_digit($datos['tbclienteid']) ? $this->clientes->obtenerporid((int) $datos['tbclienteid']) : null;
        $mismocliente = !$esnuevo && (int) $actual->getTbclienteid() === (int) $datos['tbclienteid'];
        if ($datos['tbclienteid'] === '') {
            $errores['tbclienteid'] = 'Seleccione el cliente.';
        } elseif (!$cliente) {
            $errores['tbclienteid'] = 'El cliente seleccionado no existe.';
        } elseif (!$cliente->estaActivo() && !$mismocliente) {
            $errores['tbclienteid'] = 'El cliente seleccionado está inactivo.';
        }

        // Tipo
        $tipo = $datos['tbsolicitudencargotipo'];
        if ($tipo === '') {
            $errores['tbsolicitudencargotipo'] = 'Elija si es un arreglo predefinido o personalizado.';
        } elseif (!in_array($tipo, ['Predefinido', 'Personalizado'], true)) {
            $errores['tbsolicitudencargotipo'] = 'El tipo de solicitud no es válido.';
        }

        // Productos (adicionales o los del arreglo personalizado), con el precio de venta de cada uno
        $preciosanteriores = [];
        if (!$esnuevo) {
            foreach ($this->repositorio->listarinsumos($actual->getTbsolicitudencargoid()) as $insumo) {
                $preciosanteriores[(int) $insumo->getTbinventarioid()] = (float) $insumo->getTbsolicitudencargoinsumopreciounitario();
            }
        }

        $lineas = [];
        $cantidades = [];
        $erroresproductos = [];
        foreach ($datos['insumos'] as $numero => $fila) {
            $etiqueta = 'Producto ' . ($numero + 1) . ': ';
            $producto = ctype_digit($fila['tbinventarioid']) ? $this->inventario->obtenerporid((int) $fila['tbinventarioid']) : null;

            if ($fila['tbinventarioid'] === '') {
                $erroresproductos[] = $etiqueta . 'elija el producto.';
                continue;
            }
            if (!$producto) {
                $erroresproductos[] = $etiqueta . 'el producto no existe.';
                continue;
            }
            if (!$producto->estaActivo() && !isset($preciosanteriores[(int) $producto->getTbinventarioid()])) {
                $erroresproductos[] = $etiqueta . $producto->getTbinventarionombre() . ' está inactivo.';
                continue;
            }
            if (!ctype_digit($fila['cantidad']) || (int) $fila['cantidad'] < 1 || (int) $fila['cantidad'] > 999) {
                $erroresproductos[] = $etiqueta . 'la cantidad de ' . $producto->getTbinventarionombre() . ' debe ser un número entero entre 1 y 999.';
                continue;
            }

            // Si el mismo producto se eligió dos veces, se suman las cantidades
            $idproducto = (int) $producto->getTbinventarioid();
            $cantidades[$idproducto] = ($cantidades[$idproducto] ?? 0) + (int) $fila['cantidad'];
            $lineas[$idproducto] = $preciosanteriores[$idproducto] ?? (float) $producto->getTbinventarioprecioventa();
        }

        $subtotalproductos = 0;
        $insumos = [];
        foreach ($lineas as $idproducto => $precio) {
            $insumos[] = new Solicitudencargoinsumo([
                'tbinventarioid'                         => $idproducto,
                'tbsolicitudencargoinsumocantidad'       => $cantidades[$idproducto],
                'tbsolicitudencargoinsumopreciounitario' => $precio,
            ]);
            $subtotalproductos += $cantidades[$idproducto] * $precio;
        }

        if ($erroresproductos) {
            $errores['insumos'] = implode(' ', $erroresproductos);
        }

        $subtotal = $subtotalproductos;

        if ($tipo === 'Predefinido') {
            $arreglo = ctype_digit($datos['tbarreglofloralid']) ? $this->arreglos->obtenerporid((int) $datos['tbarreglofloralid']) : null;
            $mismoarreglo = !$esnuevo && !$actual->esPersonalizado() && (int) $actual->getTbarreglofloralid() === (int) $datos['tbarreglofloralid'];

            if ($datos['tbarreglofloralid'] === '') {
                $errores['tbarreglofloralid'] = 'Seleccione el arreglo del catálogo.';
            } elseif (!$arreglo) {
                $errores['tbarreglofloralid'] = 'El arreglo seleccionado no existe.';
            } elseif (!$arreglo->estaActivo() && !$mismoarreglo) {
                $errores['tbarreglofloralid'] = 'El arreglo seleccionado está inactivo.';
            }

            if ($datos['tbsolicitudencargocantidad'] === '') {
                $errores['tbsolicitudencargocantidad'] = 'La cantidad es obligatoria.';
            } elseif (!ctype_digit($datos['tbsolicitudencargocantidad']) || (int) $datos['tbsolicitudencargocantidad'] < 1
                || (int) $datos['tbsolicitudencargocantidad'] > 99) {
                $errores['tbsolicitudencargocantidad'] = 'La cantidad debe ser un número entero entre 1 y 99.';
            }

            $precioarreglo = $arreglo ? ($mismoarreglo ? (float) $actual->getTbsolicitudencargoprecioarreglo() : (float) $arreglo->getTbarreglofloralprecio()) : 0;
            $subtotal += $precioarreglo * (int) $datos['tbsolicitudencargocantidad'];

            $datos['tbsolicitudencargoprecioarreglo'] = $precioarreglo;
            $datos['tbsolicitudencargomodalidad'] = null;
            $datos['tbsolicitudencargopresupuesto'] = null;
        } elseif ($tipo === 'Personalizado') {
            $modalidad = $datos['tbsolicitudencargomodalidad'];

            if ($modalidad === '') {
                $errores['tbsolicitudencargomodalidad'] = 'Elija si el cliente dio un presupuesto o si es libre.';
            } elseif (!in_array($modalidad, ['Presupuesto', 'Libre'], true)) {
                $errores['tbsolicitudencargomodalidad'] = 'La modalidad no es válida.';
            }

            if (!$insumos && !isset($errores['insumos'])) {
                $errores['insumos'] = 'Agregue al menos un producto al arreglo personalizado.';
            }

            if ($modalidad === 'Presupuesto') {
                $presupuesto = $datos['tbsolicitudencargopresupuesto'];

                if ($presupuesto === '') {
                    $errores['tbsolicitudencargopresupuesto'] = 'Escriba el presupuesto que dio el cliente.';
                } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $presupuesto) || (float) $presupuesto <= 0) {
                    $errores['tbsolicitudencargopresupuesto'] = 'El presupuesto debe ser un monto mayor que 0, con máximo 2 decimales.';
                } elseif (!isset($errores['insumos']) && $subtotalproductos > (float) $presupuesto) {
                    $errores['insumos'] = 'Los productos suman ' . historicocontrolador::monto($subtotalproductos)
                        . ' y se pasan del presupuesto (' . historicocontrolador::monto($presupuesto) . ') por '
                        . historicocontrolador::monto($subtotalproductos - (float) $presupuesto) . '. Quite algún producto o baje la cantidad.';
                }
            } else {
                $datos['tbsolicitudencargopresupuesto'] = null;
            }

            $datos['tbarreglofloralid'] = null;
            $datos['tbsolicitudencargocantidad'] = 1;
            $datos['tbsolicitudencargoprecioarreglo'] = null;
        }

        // Servicio express
        $costoexpress = 0;
        if ($datos['tbsolicitudencargoexpress'] === 1) {
            $zona = ctype_digit($datos['tbzonaentregaid']) ? $this->zonas->obtenerporid((int) $datos['tbzonaentregaid']) : null;
            $mismazona = !$esnuevo && $actual->tieneExpress() && (int) $actual->getTbzonaentregaid() === (int) $datos['tbzonaentregaid'];

            if ($datos['tbzonaentregaid'] === '') {
                $errores['tbzonaentregaid'] = 'Elija la zona de entrega.';
            } elseif (!$zona) {
                $errores['tbzonaentregaid'] = 'La zona seleccionada no existe.';
            } elseif (!$zona->estaActivo() && !$mismazona) {
                $errores['tbzonaentregaid'] = 'La zona seleccionada está inactiva.';
            } else {
                $costoexpress = $mismazona ? (float) $actual->getTbsolicitudencargocostoexpress() : (float) $zona->getTbzonaentregaprecio();
            }

            if ($datos['tbsolicitudencargodireccionentrega'] === '') {
                $errores['tbsolicitudencargodireccionentrega'] = 'Escriba la dirección de entrega.';
            } elseif (mb_strlen($datos['tbsolicitudencargodireccionentrega']) > 200) {
                $errores['tbsolicitudencargodireccionentrega'] = 'La dirección no puede superar 200 caracteres.';
            }
        } else {
            $datos['tbzonaentregaid'] = null;
            $datos['tbsolicitudencargodireccionentrega'] = '';
        }

        // Fecha de entrega: al editar se acepta la que ya tenía aunque ya haya pasado
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['tbsolicitudencargofechaentrega']);
        $mismafecha = !$esnuevo && $actual->getTbsolicitudencargofechaentrega() === $datos['tbsolicitudencargofechaentrega'];
        if ($datos['tbsolicitudencargofechaentrega'] === '') {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega es obligatoria.';
        } elseif (!$fecha || $fecha->format('Y-m-d') !== $datos['tbsolicitudencargofechaentrega']) {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega no es válida.';
        } elseif ($datos['tbsolicitudencargofechaentrega'] < date('Y-m-d') && !$mismafecha) {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega no puede ser anterior a hoy.';
        }

        if (mb_strlen($datos['tbsolicitudencargonota']) > 255) {
            $errores['tbsolicitudencargonota'] = 'La nota no puede superar 255 caracteres.';
        }

        // Al crear se puede dejar sin asignar
        if ($esnuevo && $datos['tbempleadoid'] !== '') {
            $error = $this->validarempleado($datos['tbempleadoid']);
            if ($error !== '') {
                $errores['tbempleadoid'] = $error;
            }
        }

        $datos['tbempleadoid'] = $datos['tbempleadoid'] === '' ? null : (int) $datos['tbempleadoid'];
        $datos['tbsolicitudencargocostoexpress'] = $costoexpress;
        $datos['tbsolicitudencargosubtotal'] = round($subtotal, 2);
        $datos['tbsolicitudencargototal'] = round($subtotal + $costoexpress, 2);
        $datos['lineas'] = $insumos;

        return $errores;
    }

    // Nombres de clientes, zonas, arreglos y empleados, para no consultarlos uno por uno
    private function nombres()
    {
        $nombres = ['clientes' => [], 'zonas' => [], 'arreglos' => [], 'empleados' => []];

        foreach ($this->clientes->listar() as $cliente) {
            $nombres['clientes'][$cliente->getTbclienteid()] = $cliente;
        }
        foreach ($this->zonas->listar() as $zona) {
            $nombres['zonas'][$zona->getTbzonaentregaid()] = $zona;
        }
        foreach ($this->arreglos->listar() as $arreglo) {
            $nombres['arreglos'][$arreglo->getTbarreglofloralid()] = $arreglo;
        }
        foreach ($this->empleados->listar() as $empleado) {
            $nombres['empleados'][$empleado->getTbempleadoid()] = $empleado;
        }

        return $nombres;
    }

    private function convertirarreglo(Encargo $encargo, $nombres)
    {
        $cliente = $nombres['clientes'][$encargo->getTbclienteid()] ?? null;
        $zona = $nombres['zonas'][$encargo->getTbzonaentregaid()] ?? null;
        $arreglo = $nombres['arreglos'][$encargo->getTbarreglofloralid()] ?? null;
        $empleado = $nombres['empleados'][$encargo->getTbempleadoid()] ?? null;

        $alcanza = true;
        if ($encargo->estaAbierto()) {
            foreach ($this->materiales($encargo) as $material) {
                $alcanza = $alcanza && $material['alcanza'];
            }
        }

        // Texto corto de lo que pidió el cliente
        if ($encargo->esPersonalizado()) {
            $descripcion = $encargo->getTbsolicitudencargomodalidad() === 'Presupuesto'
                ? 'Personalizado con presupuesto de ' . historicocontrolador::monto($encargo->getTbsolicitudencargopresupuesto())
                : 'Personalizado (libre)';
        } else {
            $descripcion = (int) $encargo->getTbsolicitudencargocantidad() . ' × ' . ($arreglo ? $arreglo->getTbarreglofloralnombre() : 'Arreglo eliminado');
            $adicionales = count($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()));
            if ($adicionales > 0) {
                $descripcion .= ' + ' . $adicionales . ($adicionales === 1 ? ' adicional' : ' adicionales');
            }
        }

        return [
            'tbsolicitudencargoid'               => (int) $encargo->getTbsolicitudencargoid(),
            'tbclienteid'                        => (int) $encargo->getTbclienteid(),
            'tbclientenombre'                    => $cliente ? $cliente->getTbclientenombre() : 'Cliente eliminado',
            'tbclientetelefono'                  => $cliente ? $cliente->getTbclientetelefono() : '',
            'tbsolicitudencargotipo'             => $encargo->getTbsolicitudencargotipo(),
            'tbarreglofloralid'                  => $encargo->getTbarreglofloralid() !== null ? (int) $encargo->getTbarreglofloralid() : null,
            'tbarreglofloralnombre'              => $arreglo ? $arreglo->getTbarreglofloralnombre() : '',
            'tbarreglofloralfoto'                => $arreglo ? $arreglo->getTbarreglofloralfoto() : '',
            'tbsolicitudencargocantidad'         => (int) $encargo->getTbsolicitudencargocantidad(),
            'tbsolicitudencargoprecioarreglo'    => (float) $encargo->getTbsolicitudencargoprecioarreglo(),
            'tbsolicitudencargomodalidad'        => $encargo->getTbsolicitudencargomodalidad(),
            'tbsolicitudencargopresupuesto'      => $encargo->getTbsolicitudencargopresupuesto() !== null ? (float) $encargo->getTbsolicitudencargopresupuesto() : null,
            'tbsolicitudencargoexpress'          => (int) $encargo->getTbsolicitudencargoexpress(),
            'tbzonaentregaid'                    => $encargo->getTbzonaentregaid() !== null ? (int) $encargo->getTbzonaentregaid() : null,
            'tbzonaentreganombre'                => $zona ? $zona->getTbzonaentreganombre() : '',
            'tbsolicitudencargodireccionentrega' => (string) $encargo->getTbsolicitudencargodireccionentrega(),
            'tbsolicitudencargocostoexpress'     => (float) $encargo->getTbsolicitudencargocostoexpress(),
            'tbsolicitudencargosubtotal'         => (float) $encargo->getTbsolicitudencargosubtotal(),
            'tbsolicitudencargototal'            => (float) $encargo->getTbsolicitudencargototal(),
            'tbempleadoid'                       => $empleado ? (int) $empleado->getTbempleadoid() : null,
            'tbempleadonombre'                   => $empleado ? $empleado->getTbempleadonombre() : '',
            'tbempleadorol'                      => $empleado ? $empleado->getTbempleadorol() : '',
            'tbsolicitudencargofechaentrega'     => $encargo->getTbsolicitudencargofechaentrega(),
            'tbsolicitudencargonota'             => (string) $encargo->getTbsolicitudencargonota(),
            'tbsolicitudencargoestado'           => $encargo->getTbsolicitudencargoestado(),
            'tbsolicitudencargofecharegistro'    => $encargo->getTbsolicitudencargofecharegistro(),
            'descripcion'                        => $descripcion,
            'alcanzastock'                       => $alcanza,
        ];
    }

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return [
            'exito'   => $exito,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ];
    }
}
