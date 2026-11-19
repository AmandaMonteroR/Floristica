<?php
require_once __DIR__ . '/../Repositorio/ventarepositorio.php';
require_once __DIR__ . '/../Repositorio/gastorepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/gastocontrolador.php';

class ventacontrolador
{
    const AGRUPACIONES = ['dia', 'semana', 'mes'];
    const DIAS = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
    const MESES = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Setiembre',
        'Octubre', 'Noviembre', 'Diciembre'];

    private $repositorio;
    private $gastos;
    private $clientes;
    private $arreglos;

    public function __construct()
    {
        $this->repositorio = new ventarepositorio();
        $this->gastos = new gastorepositorio();
        $this->clientes = new clienterepositorio();
        $this->arreglos = new arreglofloralrepositorio();
    }

    private function fechavalida($fecha)
    {
        $objeto = DateTime::createFromFormat('Y-m-d', (string) $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha;
    }

    public function resumen($desde = '', $hasta = '', $agrupar = 'dia')
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);
        $agrupar = in_array($agrupar, self::AGRUPACIONES, true) ? $agrupar : 'dia';

        if ($desde === 'todo') {
            $fechas = array_filter([$this->repositorio->primerafecha(), $this->gastos->primerafecha()]);
            $desde = $fechas ? min($fechas) : date('Y-m-d');
            $hasta = date('Y-m-d');
        }
        if ($desde === '') {
            $desde = date('Y-m-01');
        }
        if ($hasta === '') {
            $hasta = date('Y-m-d');
        }

        if (!$this->fechavalida($desde) || !$this->fechavalida($hasta)) {
            return $this->respuesta(false, 'Las fechas no son válidas.');
        }
        if ($desde > $hasta) {
            return $this->respuesta(false, 'La fecha "desde" no puede ser posterior a la fecha "hasta".');
        }

        $nombresclientes = [];
        foreach ($this->clientes->listar() as $cliente) {
            $nombresclientes[$cliente->getTbclienteid()] = $cliente->getTbclientenombre();
        }
        $nombresarreglos = [];
        foreach ($this->arreglos->listar() as $arreglo) {
            $nombresarreglos[$arreglo->getTbarreglofloralid()] = $arreglo->getTbarreglofloralnombre();
        }

        $totales = ['ventas' => 0, 'vendido' => 0, 'express' => 0, 'predefinidos' => 0, 'personalizados' => 0,
            'temporadaalta' => 0, 'gastos' => 0, 'compras' => 0];
        $periodos = [];

        $ventas = [];
        foreach ($this->repositorio->listar($desde, $hasta) as $venta) {
            $encargo = $venta['encargo'];
            $total = (float) $encargo->getTbsolicitudencargototal();

            $ventas[] = [
                'tbsolicitudencargoid' => (int) $encargo->getTbsolicitudencargoid(),
                'fecha'                => $venta['fecha'],
                'cliente'              => $nombresclientes[$encargo->getTbclienteid()] ?? 'Cliente eliminado',
                'descripcion'          => $encargo->esPersonalizado()
                    ? 'Arreglo personalizado'
                    : (int) $encargo->getTbsolicitudencargocantidad() . ' × '
                        . ($nombresarreglos[$encargo->getTbarreglofloralid()] ?? 'Arreglo eliminado'),
                'tipo'                 => $encargo->getTbsolicitudencargotipo(),
                'temporadaalta'        => $encargo->getTbtemporadaid() !== null,
                'express'              => (float) $encargo->getTbsolicitudencargocostoexpress() > 0,
                'total'                => $total,
            ];

            $totales['ventas']++;
            $totales['vendido'] += $total;
            $totales['express'] += (float) $encargo->getTbsolicitudencargocostoexpress();
            $totales[$encargo->esPersonalizado() ? 'personalizados' : 'predefinidos']++;
            if ($encargo->getTbtemporadaid() !== null) {
                $totales['temporadaalta']++;
            }

            $periodo = &$this->periodo($periodos, substr($venta['fecha'], 0, 10), $agrupar, $desde, $hasta);
            $periodo['ventas']++;
            $periodo['vendido'] += $total;
            unset($periodo);
        }

        foreach ($this->gastos->listar($desde, $hasta, '', 1) as $gasto) {
            $monto = (float) $gasto->getTbgastomonto();

            $totales['gastos'] += $monto;
            if ($gasto->getTbgastocategoria() === gastocontrolador::CATEGORIACOMPRAS) {
                $totales['compras'] += $monto;
            }

            $periodo = &$this->periodo($periodos, $gasto->getTbgastofecha(), $agrupar, $desde, $hasta);
            $periodo['gastos'] += $monto;
            $periodo['cantidadgastos']++;
            unset($periodo);
        }

        foreach (['vendido', 'express', 'gastos', 'compras'] as $campo) {
            $totales[$campo] = round($totales[$campo], 2);
        }
        $totales['otrosgastos'] = round($totales['gastos'] - $totales['compras'], 2);
        $totales['balance'] = round($totales['vendido'] - $totales['gastos'], 2);

        krsort($periodos);
        $periodos = array_values(array_map(function ($periodo) {
            $periodo['vendido'] = round($periodo['vendido'], 2);
            $periodo['gastos'] = round($periodo['gastos'], 2);
            $periodo['balance'] = round($periodo['vendido'] - $periodo['gastos'], 2);
            return $periodo;
        }, $periodos));

        return $this->respuesta(true, '', [
            'desde'    => $desde,
            'hasta'    => $hasta,
            'agrupar'  => $agrupar,
            'totales'  => $totales,
            'periodos' => $periodos,
            'ventas'   => $ventas,
        ]);
    }

    private function &periodo(&$periodos, $fecha, $agrupar, $desde, $hasta)
    {
        $dia = new DateTime($fecha);

        if ($agrupar === 'mes') {
            $clave = $dia->format('Y-m');
            $inicio = $dia->format('Y-m-01');
            $fin = $dia->format('Y-m-t');
            $etiqueta = self::MESES[(int) $dia->format('n')] . ' ' . $dia->format('Y');
        } elseif ($agrupar === 'semana') {
            $lunes = (clone $dia)->modify('-' . ((int) $dia->format('N') - 1) . ' days');
            $domingo = (clone $lunes)->modify('+6 days');
            $clave = $lunes->format('Y-m-d');
            $inicio = $lunes->format('Y-m-d');
            $fin = $domingo->format('Y-m-d');
            $etiqueta = 'Semana del ' . $lunes->format('d/m') . ' al ' . $domingo->format('d/m/Y');
        } else {
            $clave = $dia->format('Y-m-d');
            $inicio = $clave;
            $fin = $clave;
            $etiqueta = self::DIAS[(int) $dia->format('w')] . ' ' . $dia->format('d/m/Y');
        }

        if (!isset($periodos[$clave])) {
            $periodos[$clave] = [
                'clave'          => $clave,
                'etiqueta'       => $etiqueta,
                'desde'          => max($inicio, $desde),
                'hasta'          => min($fin, $hasta),
                'ventas'         => 0,
                'vendido'        => 0,
                'cantidadgastos' => 0,
                'gastos'         => 0,
            ];
        }

        return $periodos[$clave];
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