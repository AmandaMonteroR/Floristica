<?php
require_once __DIR__ . '/../Repositorio/abonorepositorio.php';
require_once __DIR__ . '/../Repositorio/pedidorepositorio.php';
require_once __DIR__ . '/../Repositorio/confirmacioncomprarepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';
require_once __DIR__ . '/gastocontrolador.php';

class creditocontrolador
{
    const DIASAVISO = 7;

    private $abonos;
    private $pedidos;
    private $confirmaciones;
    private $proveedores;
    private $encargos;
    private $clientes;
    private $empleados;
    private $gastos;
    private $metodos = ['Efectivo', 'SINPE Móvil', 'Transferencia', 'Tarjeta'];

    public function __construct()
    {
        $this->abonos = new abonorepositorio();
        $this->pedidos = new pedidorepositorio();
        $this->confirmaciones = new confirmacioncomprarepositorio();
        $this->proveedores = new proveedorrepositorio();
        $this->encargos = new encargorepositorio();
        $this->clientes = new clienterepositorio();
        $this->empleados = new empleadorepositorio();
        $this->gastos = new gastocontrolador();
    }

    public function listar($todas = false)
    {
        $todas = $todas === true || $todas === '1';
        $porpagar = [];
        foreach ($this->pedidos->listar() as $pedido) {
            $cuenta = $this->cuentapagar($pedido);
            if ($cuenta && ($todas || $cuenta['saldo'] > 0)) {
                $porpagar[] = $cuenta;
            }
        }
        usort($porpagar, function ($a, $b) {
            return [$a['saldo'] <= 0, $a['vence']] <=> [$b['saldo'] <= 0, $b['vence']];
        });

        $porcobrar = [];
        foreach ($this->encargos->listar() as $encargo) {
            $cuenta = $this->cuentacobrar($encargo);
            if ($cuenta && ($todas || $cuenta['saldo'] > 0)) {
                $porcobrar[] = $cuenta;
            }
        }
        usort($porcobrar, function ($a, $b) {
            return [$a['saldo'] <= 0, !$a['entregada'], $a['fechaentrega']] <=> [$b['saldo'] <= 0, !$b['entregada'], $b['fechaentrega']];
        });

        $totales = ['porpagar' => 0, 'vencido' => 0, 'cuentasvencidas' => 0, 'porcobrarentregado' => 0, 'porcobrarabierto' => 0];
        foreach ($porpagar as $cuenta) {
            $totales['porpagar'] += $cuenta['saldo'];
            if ($cuenta['estadocuenta'] === 'Vencido') {
                $totales['vencido'] += $cuenta['saldo'];
                $totales['cuentasvencidas']++;
            }
        }
        foreach ($porcobrar as $cuenta) {
            $totales[$cuenta['entregada'] ? 'porcobrarentregado' : 'porcobrarabierto'] += $cuenta['saldo'];
        }

        return $this->respuesta(true, '', [
            'porpagar'  => $porpagar,
            'porcobrar' => $porcobrar,
            'totales'   => array_map(function ($valor) { return round($valor, 2); }, $totales),
            'metodos'   => $this->metodos,
        ]);
    }

    public function obtener($tipo, $id)
    {
        $cuenta = $this->buscar((string) $tipo, (int) $id);
        if (!$cuenta) {
            return $this->respuesta(false, 'La cuenta no existe.');
        }

        $nombres = [];
        foreach ($this->empleados->listar() as $empleado) {
            $nombres[(int) $empleado->getTbempleadoid()] = $empleado->getTbempleadonombre();
        }
        $cuenta['abonos'] = array_map(function ($abono) use ($nombres) {
            $abono['hechopor'] = $nombres[$abono['tbempleadoid']] ?? '';
            $abono['anuladopor'] = $abono['anuladopor'] ? ($nombres[$abono['anuladopor']] ?? '') : '';
            return $abono;
        }, $this->abonos->listar($tipo, $id));
        $cuenta['metodos'] = $this->metodos;

        return $this->respuesta(true, '', $cuenta);
    }

    public function abonar($datos, $usuario)
    {
        $tipo = trim((string) ($datos['tipo'] ?? ''));
        $id = (int) ($datos['id'] ?? 0);
        $cuenta = $this->buscar($tipo, $id);
        if (!$cuenta) {
            return $this->respuesta(false, 'La cuenta no existe.');
        }

        $monto = str_replace(',', '.', trim((string) ($datos['monto'] ?? '')));
        $fecha = trim((string) ($datos['fecha'] ?? ''));
        $metodo = trim((string) ($datos['metodo'] ?? ''));
        $nota = trim(preg_replace('/\s+/u', ' ', (string) ($datos['nota'] ?? '')));

        $errores = [];
        if ($cuenta['saldo'] <= 0) {
            return $this->respuesta(false, $tipo === 'pagar' ? 'Ese pedido ya está pagado.' : 'Ese encargo ya está pagado.');
        }
        if ($monto === '') {
            $errores['monto'] = 'Escriba el monto del abono.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $monto) || (float) $monto <= 0) {
            $errores['monto'] = 'El monto debe ser mayor que 0, con máximo 2 decimales.';
        } elseif ((float) $monto > $cuenta['saldo'] + 0.001) {
            $errores['monto'] = 'El abono no puede ser mayor que el saldo (' . historicocontrolador::monto($cuenta['saldo']) . ').';
        }

        $objeto = DateTime::createFromFormat('Y-m-d', $fecha);
        if ($fecha === '') {
            $errores['fecha'] = 'La fecha es obligatoria.';
        } elseif (!$objeto || $objeto->format('Y-m-d') !== $fecha) {
            $errores['fecha'] = 'La fecha no es válida.';
        } elseif ($fecha > date('Y-m-d')) {
            $errores['fecha'] = 'La fecha no puede ser posterior a hoy.';
        } elseif ($fecha < $cuenta['fechainicio']) {
            $errores['fecha'] = $tipo === 'pagar'
                ? 'La fecha no puede ser anterior a la compra (' . date('d/m/Y', strtotime($cuenta['fechainicio'])) . ').'
                : 'La fecha no puede ser anterior al encargo (' . date('d/m/Y', strtotime($cuenta['fechainicio'])) . ').';
        }

        if (!in_array($metodo, $this->metodos, true)) {
            $errores['metodo'] = 'Elija cómo se pagó.';
        }
        if (mb_strlen($nota) > 100) {
            $errores['nota'] = 'La nota puede tener hasta 100 caracteres.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $monto = number_format((float) $monto, 2, '.', '');
        $conexion = Basedatos::conectar();
        $conexion->beginTransaction();
        try {
            $idabono = $this->abonos->insertar($tipo, $id, $fecha, $monto, $metodo, $nota, $usuario['id']);
            if ($tipo === 'pagar') {
                $this->gastos->registrarcompra('Abono', $idabono, 'Abono al pedido N.º ' . $id . ' a ' . $cuenta['nombre']
                    . ' (a crédito)', $monto, $fecha, $usuario['id']);
            }
            $conexion->commit();
        } catch (Throwable $excepcion) {
            $conexion->rollBack();
            throw $excepcion;
        }
        $saldo = round($cuenta['saldo'] - (float) $monto, 2);

        return $this->respuesta(true, $saldo > 0
            ? 'Abono registrado. Queda un saldo de ' . historicocontrolador::monto($saldo) . '.'
            : ($tipo === 'pagar' ? 'Abono registrado. El pedido quedó pagado.' : 'Abono registrado. El encargo quedó pagado.'),
            ['saldo' => $saldo]);
    }

    public function anular($datos, $usuario)
    {
        $tipo = trim((string) ($datos['tipo'] ?? ''));
        $id = (int) ($datos['id'] ?? 0);
        $idabono = (int) ($datos['idabono'] ?? 0);
        $motivo = trim(preg_replace('/\s+/u', ' ', (string) ($datos['motivo'] ?? '')));

        $cuenta = $this->buscar($tipo, $id);
        if (!$cuenta) {
            return $this->respuesta(false, 'La cuenta no existe.');
        }

        $abono = $this->abonos->obtener($tipo, $idabono);
        $prefijo = $tipo === 'pagar' ? 'getTbpedidoabono' : 'getTbsolicitudencargoabono';
        $registro = $tipo === 'pagar' ? $abono?->getTbpedidoid() : $abono?->getTbsolicitudencargoid();
        if (!$abono || (int) $registro !== $id) {
            return $this->respuesta(false, 'Ese abono no pertenece a esta cuenta.');
        }
        if ((int) $abono->{$prefijo . 'estado'}() !== 1) {
            return $this->respuesta(false, 'Ese abono ya está anulado.');
        }

        if ($motivo === '') {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => ['motivo' => 'Escriba por qué se anula el abono.']]);
        }
        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 100 || !preg_match('/\p{L}/u', $motivo)) {
            return $this->respuesta(false, 'Revise los datos ingresados.',
                ['errores' => ['motivo' => 'El motivo debe tener de 5 a 100 caracteres, con letras.']]);
        }

        if (!$this->abonos->anular($tipo, $idabono, $motivo, $usuario['id'])) {
            return $this->respuesta(false, 'Ese abono ya está anulado.');
        }
        if ($tipo === 'pagar') {
            $this->gastos->anularcompra('Abono', $idabono, $usuario['id']);
        }

        $monto = (float) $abono->{$prefijo . 'monto'}();
        $saldo = round($cuenta['saldo'] + $monto, 2);
        $saldo = min($saldo, round($cuenta['monto'], 2));

        return $this->respuesta(true, 'Abono de ' . historicocontrolador::monto($monto) . ' anulado. El saldo ahora es '
            . historicocontrolador::monto($saldo) . '.', ['saldo' => $saldo]);
    }

    private function buscar($tipo, $id)
    {
        if ($tipo === 'pagar') {
            $pedido = $this->pedidos->obtenerporid($id);
            return $pedido ? $this->cuentapagar($pedido) : null;
        }
        if ($tipo === 'cobrar') {
            $encargo = $this->encargos->obtenerporid($id);
            return $encargo ? $this->cuentacobrar($encargo) : null;
        }

        return null;
    }

    private function cuentapagar(Pedido $pedido)
    {
        $confirmacion = $this->confirmaciones->obtenerporpedido($pedido->getTbpedidoid());
        if (!$confirmacion || $confirmacion->getTbconfirmacioncompratipopago() !== 'Crédito') {
            return null;
        }

        $proveedor = $this->proveedores->obtenerporid($pedido->getTbproveedorid());
        $monto = (float) $confirmacion->getTbconfirmacioncompramonto();
        $abonado = $this->abonos->totalde('pagar', $pedido->getTbpedidoid());
        $saldo = max(0, round($monto - $abonado, 2));
        $fecha = $confirmacion->getTbconfirmacioncomprafecha();
        $vence = date('Y-m-d', strtotime($fecha . ' +' . (int) $confirmacion->getTbconfirmacioncompraplazodias() . ' days'));
        $dias = (int) floor((strtotime($vence) - strtotime(date('Y-m-d'))) / 86400);

        if ($saldo <= 0) {
            $estado = 'Pagado';
        } elseif ($dias < 0) {
            $estado = 'Vencido';
        } elseif ($dias <= self::DIASAVISO) {
            $estado = 'Por vencer';
        } else {
            $estado = 'Al día';
        }

        return [
            'tipo'         => 'pagar',
            'id'           => (int) $pedido->getTbpedidoid(),
            'nombre'       => $proveedor ? $proveedor->getTbproveedornombreempresa() : 'Proveedor eliminado',
            'detalle'      => 'Pedido N.º ' . $pedido->getTbpedidoid() . ' · ' . $pedido->getTbpedidoestado(),
            'fechainicio'  => $fecha,
            'plazo'        => (int) $confirmacion->getTbconfirmacioncompraplazodias(),
            'vence'        => $vence,
            'dias'         => $dias,
            'monto'        => $monto,
            'abonado'      => round($abonado, 2),
            'saldo'        => $saldo,
            'estadocuenta' => $estado,
        ];
    }

    private function cuentacobrar(Encargo $encargo)
    {
        if ($encargo->getTbsolicitudencargoestado() === 'Cancelado') {
            return null;
        }

        $cliente = $this->clientes->obtenerporid($encargo->getTbclienteid());
        $total = (float) $encargo->getTbsolicitudencargototal();
        $abonado = $this->abonos->totalde('cobrar', $encargo->getTbsolicitudencargoid());
        $saldo = max(0, round($total - $abonado, 2));

        $entregada = $encargo->getTbsolicitudencargoestado() === 'Entregado';

        if ($saldo <= 0) {
            $estado = 'Pagado';
        } elseif ($entregada) {
            $estado = 'Entregada sin pagar';
        } else {
            $estado = $abonado > 0 ? 'Con adelanto' : 'Sin pagar';
        }

        return [
            'tipo'         => 'cobrar',
            'id'           => (int) $encargo->getTbsolicitudencargoid(),
            'nombre'       => $cliente ? $cliente->getTbclientenombre() : 'Cliente eliminado',
            'telefono'     => $cliente ? $cliente->getTbclientetelefono() : '',
            'detalle'      => 'Encargo N.º ' . $encargo->getTbsolicitudencargoid() . ' · ' . $encargo->getTbsolicitudencargoestado(),
            'fechainicio'  => $encargo->getTbsolicitudencargofecharegistro(),
            'fechaentrega' => $encargo->getTbsolicitudencargofechaentrega(),
            'entregada'    => $entregada,
            'monto'        => $total,
            'abonado'      => round($abonado, 2),
            'saldo'        => $saldo,
            'estadocuenta' => $estado,
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