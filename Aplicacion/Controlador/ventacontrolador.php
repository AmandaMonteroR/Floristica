<?php
require_once __DIR__ . '/../Repositorio/ventarepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';

// Ventas y ganancias (solo el propietario).
// Una venta es una solicitud terminada. Ganancia = lo que se cobró - lo que costaron las flores y productos.
class ventacontrolador
{
    private $repositorio;
    private $clientes;
    private $arreglos;

    public function __construct()
    {
        $this->repositorio = new ventarepositorio();
        $this->clientes = new clienterepositorio();
        $this->arreglos = new arreglofloralrepositorio();
    }

    private function fechavalida($fecha)
    {
        $objeto = DateTime::createFromFormat('Y-m-d', (string) $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha;
    }

    // $desde y $hasta en formato AAAA-MM-DD. Sin fechas: el mes actual.
    public function resumen($desde = '', $hasta = '')
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);

        if ($desde === 'todo') {
            $desde = $this->repositorio->primerafecha() ?? date('Y-m-d');
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

        $lista = [];
        $totales = ['ventas' => 0, 'vendido' => 0, 'costo' => 0, 'ganancia' => 0, 'express' => 0,
            'predefinidos' => 0, 'personalizados' => 0, 'temporadaalta' => 0];

        foreach ($this->repositorio->listar($desde, $hasta) as $venta) {
            $encargo = $venta['encargo'];
            $total = (float) $encargo->getTbsolicitudencargototal();
            $costo = (float) $encargo->getTbsolicitudencargocosto();

            $descripcion = $encargo->esPersonalizado()
                ? 'Personalizado' . ($encargo->getTbsolicitudencargoporcentaje() !== null
                    ? ' (' . (float) $encargo->getTbsolicitudencargoporcentaje() . '% de ganancia)' : '')
                : (int) $encargo->getTbsolicitudencargocantidad() . ' × '
                    . ($nombresarreglos[$encargo->getTbarreglofloralid()] ?? 'Arreglo eliminado');

            $lista[] = [
                'tbsolicitudencargoid' => (int) $encargo->getTbsolicitudencargoid(),
                'fecha'                => $venta['fecha'],
                'cliente'              => $nombresclientes[$encargo->getTbclienteid()] ?? 'Cliente eliminado',
                'descripcion'          => $descripcion,
                'tipo'                 => $encargo->getTbsolicitudencargotipo(),
                'temporadaalta'        => $encargo->getTbtemporadaid() !== null,
                'total'                => $total,
                'costo'                => $costo,
                'express'              => (float) $encargo->getTbsolicitudencargocostoexpress(),
                'ganancia'             => round($total - $costo, 2),
            ];

            $totales['ventas']++;
            $totales['vendido'] += $total;
            $totales['costo'] += $costo;
            $totales['express'] += (float) $encargo->getTbsolicitudencargocostoexpress();
            $totales[$encargo->esPersonalizado() ? 'personalizados' : 'predefinidos']++;
            if ($encargo->getTbtemporadaid() !== null) {
                $totales['temporadaalta']++;
            }
        }

        $totales['vendido'] = round($totales['vendido'], 2);
        $totales['costo'] = round($totales['costo'], 2);
        $totales['express'] = round($totales['express'], 2);
        $totales['ganancia'] = round($totales['vendido'] - $totales['costo'], 2);
        // Qué parte de lo vendido es ganancia
        $totales['margen'] = $totales['vendido'] > 0 ? round($totales['ganancia'] * 100 / $totales['vendido'], 1) : 0;

        return $this->respuesta(true, '', [
            'desde'   => $desde,
            'hasta'   => $hasta,
            'totales' => $totales,
            'ventas'  => $lista,
        ]);
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
