<?php
require_once __DIR__ . '/../Repositorio/pedidorepositorio.php';
require_once __DIR__ . '/../Repositorio/confirmacioncomprarepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

class pedidocontrolador
{
    private $repositorio;
    private $confirmaciones;
    private $inventario;
    private $proveedores;
    private $movimientos;
    private $historico;
    private $tipospago = ['Contado', 'Crédito'];

    public function __construct()
    {
        $this->repositorio = new pedidorepositorio();
        $this->confirmaciones = new confirmacioncomprarepositorio();
        $this->inventario = new inventariorepositorio();
        $this->proveedores = new proveedorrepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->historico = new historicocontrolador();
    }

    public function proveedores()
    {
        $lista = [];
        foreach ($this->proveedores->listar('', 1) as $proveedor) {
            $lista[] = [
                'tbproveedorid'            => $proveedor->getTbproveedorid(),
                'tbproveedornombreempresa' => $proveedor->getTbproveedornombreempresa(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function productos($idproveedor)
    {
        $lista = [];
        foreach ($this->inventario->listar('', '', 1, (int) $idproveedor) as $producto) {
            $lista[] = [
                'tbinventarioid'              => $producto->getTbinventarioid(),
                'tbinventarionombre'          => $producto->getTbinventarionombre(),
                'tbinventariopreciopaquete'   => (float) $producto->getTbinventariopreciopaquete(),
                'tbinventariounidadespaquete' => (int) $producto->getTbinventariounidadespaquete(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function listar($idproveedor = 0, $estado = '')
    {
        $nombres = $this->nombresproveedores();

        $lista = [];
        foreach ($this->repositorio->listar((int) $idproveedor, (string) $estado) as $pedido) {
            $lista[] = $this->convertirarreglo($pedido, $nombres);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $pedido = $this->repositorio->obtenerporid((int) $id);

        if (!$pedido) {
            return $this->respuesta(false, 'El pedido no existe.');
        }

        $datos = $this->convertirarreglo($pedido, $this->nombresproveedores());
        $datos['detalles'] = [];

        foreach ($this->repositorio->listardetalles($pedido->getTbpedidoid()) as $detalle) {
            $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
            $datos['detalles'][] = [
                'tbinventarioid'              => (int) $detalle->getTbinventarioid(),
                'tbinventarionombre'          => $producto ? $producto->getTbinventarionombre() : '',
                'tbinventariounidadespaquete' => $producto ? (int) $producto->getTbinventariounidadespaquete() : 0,
                'tbpedidodetallecantidad'     => (int) $detalle->getTbpedidodetallecantidad(),
                'tbpedidodetalleprecio'       => (float) $detalle->getTbpedidodetalleprecio(),
            ];
        }

        $confirmacion = $this->confirmaciones->obtenerporpedido($pedido->getTbpedidoid());
        $datos['confirmacion'] = $confirmacion ? [
            'tbconfirmacioncomprafecha'     => $confirmacion->getTbconfirmacioncomprafecha(),
            'tbconfirmacioncompratipopago'  => $confirmacion->getTbconfirmacioncompratipopago(),
            'tbconfirmacioncompramonto'     => (float) $confirmacion->getTbconfirmacioncompramonto(),
            'tbconfirmacioncompraplazodias' => (int) $confirmacion->getTbconfirmacioncompraplazodias(),
        ] : null;

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $detalles = $this->limpiardetalles($datos['detalles'] ?? []);
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $detalles);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $pedido = new Pedido($datos);

        $id = $this->entransaccion(function () use ($pedido, $detalles, $usuario) {
            $id = $this->repositorio->insertar($pedido);
            $this->guardardetalles($id, $detalles);
            $this->historico->registrar('pedido', $id, [], ['estado' => 'Pendiente'], $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Pedido registrado correctamente.', ['tbpedidoid' => $id]);
    }

    public function actualizar($datos)
    {
        $id = (int) ($datos['tbpedidoid'] ?? 0);
        $pedido = $this->repositorio->obtenerporid($id);

        if (!$pedido) {
            return $this->respuesta(false, 'El pedido no existe.');
        }

        if ($pedido->getTbpedidoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden editar pedidos pendientes.');
        }

        $detalles = $this->limpiardetalles($datos['detalles'] ?? []);
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $detalles);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $pedido->setTbproveedorid($datos['tbproveedorid']);
        $pedido->setTbpedidofecha($datos['tbpedidofecha']);

        $this->entransaccion(function () use ($pedido, $detalles, $id) {
            $this->repositorio->actualizar($pedido);
            $this->repositorio->eliminardetalles($id);
            $this->guardardetalles($id, $detalles);
        });

        return $this->respuesta(true, 'Pedido actualizado correctamente.');
    }

    public function confirmar($datos, $usuario)
    {
        $id = (int) ($datos['tbpedidoid'] ?? 0);
        $pedido = $this->repositorio->obtenerporid($id);

        if (!$pedido) {
            return $this->respuesta(false, 'El pedido no existe.');
        }

        if ($pedido->getTbpedidoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden confirmar pedidos pendientes.');
        }

        $tipopago = trim((string) ($datos['tbconfirmacioncompratipopago'] ?? ''));
        $plazodias = trim((string) ($datos['tbconfirmacioncompraplazodias'] ?? ''));
        $errores = [];

        if ($tipopago === '') {
            $errores['tbconfirmacioncompratipopago'] = 'El tipo de pago es obligatorio.';
        } elseif (!in_array($tipopago, $this->tipospago, true)) {
            $errores['tbconfirmacioncompratipopago'] = 'El tipo de pago seleccionado no es válido.';
        }

        if ($tipopago === 'Crédito') {
            if ($plazodias === '') {
                $errores['tbconfirmacioncompraplazodias'] = 'El plazo es obligatorio cuando el pago es a crédito.';
            } elseif (!ctype_digit($plazodias) || (int) $plazodias < 1 || (int) $plazodias > 365) {
                $errores['tbconfirmacioncompraplazodias'] = 'El plazo debe ser un número entero entre 1 y 365 días.';
            }
        } else {
            $plazodias = '0';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $confirmacion = new Confirmacioncompra([
            'tbpedidoid'                    => $id,
            'tbconfirmacioncompratipopago'  => $tipopago,
            'tbconfirmacioncompramonto'     => $this->repositorio->total($id),
            'tbconfirmacioncompraplazodias' => (int) $plazodias,
        ]);

        $this->entransaccion(function () use ($confirmacion, $id, $usuario) {
            $this->confirmaciones->insertar($confirmacion);
            $this->repositorio->cambiarestado($id, 'Confirmado');
            $this->historico->registrar('pedido', $id, ['estado' => 'Pendiente'], ['estado' => 'Confirmado'], $usuario['id']);
        });

        return $this->respuesta(true, 'Compra confirmada correctamente.');
    }

    public function recibir($id, $usuario)
    {
        $id = (int) $id;
        $pedido = $this->repositorio->obtenerporid($id);

        if (!$pedido) {
            return $this->respuesta(false, 'El pedido no existe.');
        }

        if ($pedido->getTbpedidoestado() !== 'Confirmado') {
            return $this->respuesta(false, 'Solo se pueden recibir pedidos confirmados.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            foreach ($this->repositorio->listardetalles($id) as $detalle) {
                $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
                $unidades = (int) $detalle->getTbpedidodetallecantidad() * (int) $producto->getTbinventariounidadespaquete();
                $this->inventario->sumarstock($detalle->getTbinventarioid(), $unidades);
                $this->movimientos->registrar($detalle->getTbinventarioid(), 'Entrada', $unidades, $usuario['id'], $id);
            }

            $this->repositorio->cambiarestado($id, 'Recibido');
            $this->historico->registrar('pedido', $id, ['estado' => 'Confirmado'], ['estado' => 'Recibido'], $usuario['id']);
        });

        return $this->respuesta(true, 'Pedido recibido. El stock del inventario se actualizó.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El pedido no existe.');
        }

        $formatear = function ($campo, $valor) {
            return (string) $valor;
        };

        return $this->respuesta(true, '', $this->historico->listar('pedido', $id, ['estado' => 'Estado'], $formatear));
    }

    private function guardardetalles($idpedido, $detalles)
    {
        foreach ($detalles as $detalle) {
            $this->repositorio->insertardetalle(new Pedidodetalle([
                'tbpedidoid'              => $idpedido,
                'tbinventarioid'          => (int) $detalle['tbinventarioid'],
                'tbpedidodetallecantidad' => (int) $detalle['tbpedidodetallecantidad'],
                'tbpedidodetalleprecio'   => $detalle['tbpedidodetalleprecio'],
            ]));
        }
    }

    private function nombresproveedores()
    {
        $nombres = [];
        foreach ($this->proveedores->listar() as $proveedor) {
            $nombres[$proveedor->getTbproveedorid()] = $proveedor->getTbproveedornombreempresa();
        }

        return $nombres;
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
        return [
            'tbproveedorid' => trim((string) ($datos['tbproveedorid'] ?? '')),
            'tbpedidofecha' => trim((string) ($datos['tbpedidofecha'] ?? '')),
        ];
    }

    private function limpiardetalles($detalles)
    {
        if (!is_array($detalles)) {
            return [];
        }

        $limpios = [];
        foreach ($detalles as $detalle) {
            if (!is_array($detalle)) {
                continue;
            }

            $limpios[] = [
                'tbinventarioid'          => trim((string) ($detalle['tbinventarioid'] ?? '')),
                'tbpedidodetallecantidad' => trim((string) ($detalle['tbpedidodetallecantidad'] ?? '')),
                'tbpedidodetalleprecio'   => str_replace(',', '.', trim((string) ($detalle['tbpedidodetalleprecio'] ?? ''))),
            ];
        }

        return $limpios;
    }

    private function validar($datos, $detalles)
    {
        $errores = [];

        if ($datos['tbproveedorid'] === '') {
            $errores['tbproveedorid'] = 'El proveedor es obligatorio.';
        } else {
            $proveedor = $this->proveedores->obtenerporid((int) $datos['tbproveedorid']);
            if (!$proveedor) {
                $errores['tbproveedorid'] = 'El proveedor seleccionado no existe.';
            } elseif (!$proveedor->estaActivo()) {
                $errores['tbproveedorid'] = 'El proveedor seleccionado está inactivo.';
            }
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $datos['tbpedidofecha']);
        if ($datos['tbpedidofecha'] === '') {
            $errores['tbpedidofecha'] = 'La fecha del pedido es obligatoria.';
        } elseif (!$fecha || $fecha->format('Y-m-d') !== $datos['tbpedidofecha']) {
            $errores['tbpedidofecha'] = 'La fecha del pedido no es válida.';
        } elseif ($datos['tbpedidofecha'] > date('Y-m-d')) {
            $errores['tbpedidofecha'] = 'La fecha del pedido no puede ser posterior a hoy.';
        }

        if (count($detalles) === 0) {
            $errores['detalles'] = 'Agregue al menos un producto al pedido.';
            return $errores;
        }

        $usados = [];
        foreach ($detalles as $indice => $detalle) {
            $linea = 'Línea ' . ($indice + 1) . ': ';
            $producto = ctype_digit($detalle['tbinventarioid'])
                ? $this->inventario->obtenerporid((int) $detalle['tbinventarioid'])
                : null;

            if ($detalle['tbinventarioid'] === '') {
                $errores['detalles'] = $linea . 'seleccione el producto.';
            } elseif (!$producto) {
                $errores['detalles'] = $linea . 'el producto no existe.';
            } elseif (!$producto->estaActivo()) {
                $errores['detalles'] = $linea . 'el producto está inactivo.';
            } elseif ((int) $producto->getTbproveedorid() !== (int) $datos['tbproveedorid']) {
                $errores['detalles'] = $linea . 'el producto no pertenece al proveedor seleccionado.';
            } elseif (in_array($detalle['tbinventarioid'], $usados, true)) {
                $errores['detalles'] = $linea . 'el producto ya está en otra línea.';
            } elseif (!ctype_digit($detalle['tbpedidodetallecantidad'])
                || (int) $detalle['tbpedidodetallecantidad'] < 1
                || (int) $detalle['tbpedidodetallecantidad'] > 9999) {
                $errores['detalles'] = $linea . 'la cantidad de paquetes debe ser un número entero entre 1 y 9999.';
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $detalle['tbpedidodetalleprecio'])
                || (float) $detalle['tbpedidodetalleprecio'] <= 0) {
                $errores['detalles'] = $linea . 'el precio debe ser un monto mayor que 0, con máximo 2 decimales.';
            }

            if (isset($errores['detalles'])) {
                break;
            }

            $usados[] = $detalle['tbinventarioid'];
        }

        return $errores;
    }

    private function convertirarreglo(Pedido $pedido, $nombres)
    {
        return [
            'tbpedidoid'               => $pedido->getTbpedidoid(),
            'tbproveedorid'            => (int) $pedido->getTbproveedorid(),
            'tbproveedornombreempresa' => $nombres[$pedido->getTbproveedorid()] ?? '',
            'tbpedidofecha'            => $pedido->getTbpedidofecha(),
            'tbpedidoestado'           => $pedido->getTbpedidoestado(),
            'total'                    => $this->repositorio->total($pedido->getTbpedidoid()),
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