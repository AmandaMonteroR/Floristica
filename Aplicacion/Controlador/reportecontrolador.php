<?php
require_once __DIR__ . '/ventacontrolador.php';
require_once __DIR__ . '/encargocontrolador.php';
require_once __DIR__ . '/pedidocontrolador.php';
require_once __DIR__ . '/../Repositorio/gastorepositorio.php';
require_once __DIR__ . '/../Repositorio/abonorepositorio.php';
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/pedidorepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

class reportecontrolador
{
    const MESES = 6;

    private $ventas;
    private $gastos;
    private $abonos;

    public function __construct()
    {
        $this->ventas = new ventacontrolador();
        $this->gastos = new gastorepositorio();
        $this->abonos = new abonorepositorio();
    }

    public function ganancia($desde = '', $hasta = '')
    {
        $resumen = $this->ventas->resumen($desde, $hasta);
        if (!$resumen['exito']) {
            return $resumen;
        }

        $desde = $resumen['datos']['desde'];
        $hasta = $resumen['datos']['hasta'];
        $datos = $this->calcular($desde, $hasta, $resumen['datos']['totales']);

        $meses = [];
        $nombres = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Set', 'Oct', 'Nov', 'Dic'];
        $inicio = new DateTime(substr($hasta, 0, 7) . '-01');
        $inicio->modify('-' . (self::MESES - 1) . ' months');
        for ($i = 0; $i < self::MESES; $i++) {
            $primero = $inicio->format('Y-m-01');
            $ultimo = $inicio->format('Y-m-t');
            $totales = $this->ventas->resumen($primero, $ultimo)['datos']['totales'];
            $mes = $this->calcular($primero, $ultimo, $totales);
            $meses[] = [
                'mes'      => $inicio->format('Y-m'),
                'etiqueta' => $nombres[(int) $inicio->format('n')] . ' ' . $inicio->format('Y'),
                'vendido'  => $mes['vendido'],
                'gastos'   => $mes['gastos'],
                'balance'  => $mes['balance'],
            ];
            $inicio->modify('+1 month');
        }

        $datos['desde'] = $desde;
        $datos['hasta'] = $hasta;
        $datos['meses'] = $meses;

        return $this->respuesta(true, '', $datos);
    }

    private function calcular($desde, $hasta, $totales)
    {
        $porcategoria = $this->gastos->totalesporcategoria($desde, $hasta);
        $gastos = round(array_sum($porcategoria), 2);
        $balance = round($totales['vendido'] - $gastos, 2);

        return [
            'ventas'       => (int) $totales['ventas'],
            'vendido'      => round($totales['vendido'], 2),
            'gastos'       => $gastos,
            'porcategoria' => $porcategoria,
            'balance'      => $balance,
            'margen'       => $totales['vendido'] > 0 ? round($balance * 100 / $totales['vendido'], 1) : 0,
        ];
    }

    public function opciones()
    {
        $clientes = [];
        foreach ((new clienterepositorio())->listar() as $cliente) {
            $clientes[(int) $cliente->getTbclienteid()] = $cliente->getTbclientenombre();
        }
        $proveedores = [];
        foreach ((new proveedorrepositorio())->listar() as $proveedor) {
            $proveedores[(int) $proveedor->getTbproveedorid()] = $proveedor->getTbproveedornombreempresa();
        }

        $solicitudes = [];
        foreach ((new encargorepositorio())->listar() as $encargo) {
            $solicitudes[] = [
                'id'     => (int) $encargo->getTbsolicitudencargoid(),
                'texto'  => 'N.º ' . $encargo->getTbsolicitudencargoid() . ' · ' . ($clientes[(int) $encargo->getTbclienteid()] ?? 'Cliente eliminado')
                    . ' · ' . $encargo->getTbsolicitudencargoestado(),
            ];
        }
        $pedidos = [];
        foreach ((new pedidorepositorio())->listar() as $pedido) {
            $pedidos[] = [
                'id'    => (int) $pedido->getTbpedidoid(),
                'texto' => 'N.º ' . $pedido->getTbpedidoid() . ' · ' . ($proveedores[(int) $pedido->getTbproveedorid()] ?? 'Proveedor eliminado')
                    . ' · ' . $pedido->getTbpedidoestado(),
            ];
        }
        usort($solicitudes, function ($a, $b) { return $b['id'] <=> $a['id']; });
        usort($pedidos, function ($a, $b) { return $b['id'] <=> $a['id']; });

        return $this->respuesta(true, '', ['solicitudes' => $solicitudes, 'pedidos' => $pedidos]);
    }

    public function trazabilidad($tipo, $id)
    {
        $id = (int) $id;
        if ($tipo === 'solicitud') {
            return $this->trazasolicitud($id);
        }
        if ($tipo === 'pedido') {
            return $this->trazapedido($id);
        }

        return $this->respuesta(false, 'Elija si quiere ver un encargo o un pedido.');
    }

    private function trazasolicitud($id)
    {
        $respuesta = (new encargocontrolador())->obtener($id);
        if (!$respuesta['exito']) {
            return $respuesta;
        }
        $encargo = $respuesta['datos'];

        $eventos = [];
        $terminado = null;
        foreach ($encargo['historial'] as $paso) {
            $evento = ['fecha' => $paso['fecha'], 'texto' => $paso['texto'], 'responsable' => $paso['hechopor'], 'tipo' => 'cambio'];
            if ($paso['texto'] === 'Pasó a "Terminado"') {
                $terminado = $paso['fecha'];
                $evento['texto'] = 'Se terminó';
                $evento['tipo'] = 'fin';
                $evento['detalle'] = implode(', ', array_map(function ($material) {
                    return $material['necesarias'] . ' ' . $material['tbinventarionombre'];
                }, $encargo['materiales']));
                if ($evento['detalle'] !== '') {
                    $evento['detalle'] = 'Salió del inventario: ' . $evento['detalle'];
                }
            } elseif ($paso['texto'] === 'Pasó a "Entregado"') {
                // Se entregó a la clienta: ahí termina el ciclo
                $terminado = $paso['fecha'];
                $evento['texto'] = 'Se entregó a la clienta';
                $evento['tipo'] = 'fin';
            } elseif ($paso['texto'] === 'Pasó a "Cancelado"') {
                $evento['texto'] = 'Se canceló';
                $evento['tipo'] = 'cancelado';
            } elseif ($paso['texto'] === 'Pasó a "En proceso"') {
                $evento['texto'] = 'Empezó a prepararse';
            } elseif ($paso['texto'] === 'Se registró el encargo') {
                $evento['tipo'] = 'inicio';
            }
            $eventos[] = $evento;
        }

        foreach ($this->abonoscon('cobrar', $id) as $abono) {
            $eventos[] = [
                'fecha'       => $abono['fecha'] . ' 00:00:00',
                'texto'       => 'La clienta pagó ' . historicocontrolador::monto($abono['monto']) . ' (' . $abono['metodo'] . ')',
                'detalle'     => $abono['nota'],
                'responsable' => $abono['hechopor'],
                'tipo'        => 'pago',
            ];
            if ($abono['estado'] === 0) {
                $eventos[] = [
                    'fecha'       => $abono['fechaanulacion'] ?: $abono['fecha'] . ' 00:00:00',
                    'texto'       => 'Se anuló el abono de ' . historicocontrolador::monto($abono['monto']) . ' del ' . date('d/m/Y', strtotime($abono['fecha'])),
                    'detalle'     => 'Motivo: ' . $abono['motivo'],
                    'responsable' => $abono['anuladopor'],
                    'tipo'        => 'cancelado',
                ];
            }
        }
        $eventos = $this->ordenar($eventos);

        $inicio = $this->fechainicio($eventos);
        return $this->respuesta(true, '', [
            'tipo'      => 'solicitud',
            'id'        => $id,
            'titulo'    => 'Encargo N.º ' . $id . ' · ' . $encargo['tbclientenombre'],
            'resumen'   => [
                ['Clienta', $encargo['tbclientenombre'] . ($encargo['tbclientetelefono'] ? ' · ' . $encargo['tbclientetelefono'] : '')],
                ['Pedido', $encargo['descripcion']],
                ['Estado', $encargo['tbsolicitudencargoestado']],
                ['Encargada', $encargo['tbempleadonombre'] ?: 'Sin asignar'],
                ['Fecha de entrega', date('d/m/Y', strtotime($encargo['tbsolicitudencargofechaentrega']))],
                ['Total', historicocontrolador::monto($encargo['tbsolicitudencargototal'])],
                ['Pagado', historicocontrolador::monto($encargo['abonado'])
                    . ($encargo['saldo'] > 0 ? ' · falta ' . historicocontrolador::monto($encargo['saldo']) : '')],
            ],
            'duracion'  => $inicio && $terminado ? $this->duracion($inicio, $terminado) : '',
            'eventos'   => $eventos,
        ]);
    }

    private function trazapedido($id)
    {
        $controlador = new pedidocontrolador();
        $respuesta = $controlador->obtener($id);
        if (!$respuesta['exito']) {
            return $respuesta;
        }
        $pedido = $respuesta['datos'];
        $confirmacion = $pedido['confirmacion'];

        $productos = implode(', ', array_map(function ($detalle) {
            $paquetes = (int) $detalle['tbpedidodetallecantidad'];
            return $paquetes . ($paquetes === 1 ? ' paquete de ' : ' paquetes de ') . $detalle['tbinventarionombre'];
        }, $pedido['detalles']));
        $unidades = implode(', ', array_map(function ($detalle) {
            return ($detalle['tbpedidodetallecantidad'] * $detalle['tbinventariounidadespaquete']) . ' ' . $detalle['tbinventarionombre'];
        }, $pedido['detalles']));

        $eventos = [];
        $recibido = null;
        foreach ($controlador->historial($id)['datos'] as $paso) {
            $evento = ['fecha' => $paso['fecha'], 'responsable' => $paso['hechopor'], 'tipo' => 'cambio', 'detalle' => ''];
            switch ($paso['nuevo']) {
                case 'Pendiente':
                    $evento['texto'] = 'Se hizo el pedido a ' . $pedido['tbproveedornombreempresa'];
                    $evento['detalle'] = $productos;
                    $evento['tipo'] = 'inicio';
                    break;
                case 'Confirmado':
                    $evento['texto'] = 'El proveedor confirmó la compra';
                    if ($confirmacion) {
                        $evento['detalle'] = historicocontrolador::monto($confirmacion['tbconfirmacioncompramonto']) . ' de '
                            . mb_strtolower($confirmacion['tbconfirmacioncompratipopago'])
                            . ($confirmacion['tbconfirmacioncompratipopago'] === 'Crédito'
                                ? ' a ' . $confirmacion['tbconfirmacioncompraplazodias'] . ' días' : '');
                    }
                    break;
                case 'Recibido':
                    $evento['texto'] = 'Se recibió la mercadería';
                    $evento['detalle'] = 'Entró al inventario: ' . $unidades;
                    $evento['tipo'] = 'fin';
                    $recibido = $paso['fecha'];
                    break;
                case 'Cancelado':
                    $evento['texto'] = 'Se canceló';
                    $evento['tipo'] = 'cancelado';
                    break;
                default:
                    $evento['texto'] = 'Pasó a "' . $paso['nuevo'] . '"';
            }
            $eventos[] = $evento;
        }

        foreach ($this->abonoscon('pagar', $id) as $abono) {
            $eventos[] = [
                'fecha'       => $abono['fecha'] . ' 00:00:00',
                'texto'       => 'Se le pagó ' . historicocontrolador::monto($abono['monto']) . ' al proveedor (' . $abono['metodo'] . ')',
                'detalle'     => $abono['nota'],
                'responsable' => $abono['hechopor'],
                'tipo'        => 'pago',
            ];
            if ($abono['estado'] === 0) {
                $eventos[] = [
                    'fecha'       => $abono['fechaanulacion'] ?: $abono['fecha'] . ' 00:00:00',
                    'texto'       => 'Se anuló el abono de ' . historicocontrolador::monto($abono['monto']) . ' del ' . date('d/m/Y', strtotime($abono['fecha'])),
                    'detalle'     => 'Motivo: ' . $abono['motivo'],
                    'responsable' => $abono['anuladopor'],
                    'tipo'        => 'cancelado',
                ];
            }
        }
        $eventos = $this->ordenar($eventos);

        $resumen = [
            ['Proveedor', $pedido['tbproveedornombreempresa']],
            ['Productos', $productos],
            ['Estado', $pedido['tbpedidoestado']],
            ['Total', historicocontrolador::monto($pedido['total'])],
        ];
        if ($confirmacion && $confirmacion['tbconfirmacioncompratipopago'] === 'Crédito') {
            $pagado = $this->abonos->totalde('pagar', $id);
            $saldo = max(0, round($confirmacion['tbconfirmacioncompramonto'] - $pagado, 2));
            $resumen[] = ['Pagado', historicocontrolador::monto($pagado) . ($saldo > 0 ? ' · falta ' . historicocontrolador::monto($saldo) : '')];
        } elseif ($confirmacion) {
            $resumen[] = ['Pagado', 'De contado al confirmar'];
        }

        $inicio = $this->fechainicio($eventos);
        return $this->respuesta(true, '', [
            'tipo'     => 'pedido',
            'id'       => $id,
            'titulo'   => 'Pedido N.º ' . $id . ' · ' . $pedido['tbproveedornombreempresa'],
            'resumen'  => $resumen,
            'duracion' => $inicio && $recibido ? $this->duracion($inicio, $recibido) : '',
            'eventos'  => $eventos,
        ]);
    }

    private function fechainicio($eventos)
    {
        foreach ($eventos as $evento) {
            if ($evento['tipo'] === 'inicio') {
                return $evento['fecha'];
            }
        }

        return $eventos ? $eventos[0]['fecha'] : null;
    }

    private function abonoscon($tipo, $id)
    {
        $nombres = [];
        foreach ((new empleadorepositorio())->listar() as $empleado) {
            $nombres[(int) $empleado->getTbempleadoid()] = $empleado->getTbempleadonombre();
        }

        return array_map(function ($abono) use ($nombres) {
            $abono['hechopor'] = $nombres[$abono['tbempleadoid']] ?? '';
            $abono['anuladopor'] = $abono['anuladopor'] ? ($nombres[$abono['anuladopor']] ?? '') : '';
            return $abono;
        }, $this->abonos->listar($tipo, $id));
    }

    private function ordenar($eventos)
    {
        foreach ($eventos as $indice => &$evento) {
            $evento['orden'] = $indice;
            $evento['detalle'] = $evento['detalle'] ?? '';
        }
        unset($evento);
        usort($eventos, function ($a, $b) {
            return [$a['fecha'], $a['orden']] <=> [$b['fecha'], $b['orden']];
        });

        return array_map(function ($evento) {
            unset($evento['orden']);
            return $evento;
        }, $eventos);
    }

    private function duracion($inicio, $fin)
    {
        $segundos = max(0, strtotime($fin) - strtotime($inicio));
        $dias = intdiv($segundos, 86400);
        $horas = intdiv($segundos % 86400, 3600);
        $minutos = intdiv($segundos % 3600, 60);

        $partes = [];
        if ($dias > 0) {
            $partes[] = $dias . ($dias === 1 ? ' día' : ' días');
        }
        if ($horas > 0) {
            $partes[] = $horas . ($horas === 1 ? ' hora' : ' horas');
        }
        if ($dias === 0 && $minutos > 0) {
            $partes[] = $minutos . ($minutos === 1 ? ' minuto' : ' minutos');
        }

        return $partes ? implode(' y ', array_slice($partes, 0, 2)) : 'menos de un minuto';
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