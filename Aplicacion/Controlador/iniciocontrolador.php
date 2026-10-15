<?php
require_once __DIR__ . '/encargocontrolador.php';
require_once __DIR__ . '/pedidocontrolador.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';

// Resumen de la página de inicio. Cada rol ve solo lo que le corresponde.
class iniciocontrolador
{
    public function resumen($usuario)
    {
        $encargos = (new encargocontrolador())->listar()['datos'];
        $abiertos = array_values(array_filter($encargos, function ($encargo) {
            return in_array($encargo['tbsolicitudencargoestado'], ['Pendiente', 'En proceso'], true);
        }));

        usort($abiertos, function ($a, $b) {
            return [$a['tbsolicitudencargofechaentrega'], $a['tbsolicitudencargoid']] <=> [$b['tbsolicitudencargofechaentrega'], $b['tbsolicitudencargoid']];
        });

        $sinasignar = array_values(array_filter($abiertos, function ($encargo) {
            return $encargo['tbempleadoid'] === null;
        }));

        $mios = array_values(array_filter($abiertos, function ($encargo) use ($usuario) {
            return $encargo['tbempleadoid'] === (int) $usuario['id'];
        }));

        // El propietario ve todos los atrasados; el empleado, solo los suyos
        $hoy = date('Y-m-d');
        $revisar = $usuario['rol'] === 'Propietario' ? $abiertos : $mios;
        $atrasados = array_values(array_filter($revisar, function ($encargo) use ($hoy) {
            return $encargo['tbsolicitudencargofechaentrega'] < $hoy;
        }));

        $resumen = [
            'hoy'        => $hoy,
            'mios'       => $mios,
            'sinasignar' => $sinasignar,
            'atrasados'  => $atrasados,
            'abiertos'   => $abiertos,
        ];

        // Lo de compras e inventario solo lo ve el propietario
        if ($usuario['rol'] === 'Propietario') {
            $stockbajo = [];
            foreach ((new inventariorepositorio())->listar('', '', 1) as $producto) {
                if ($producto->tieneStockBajo()) {
                    $stockbajo[] = [
                        'nombre'  => $producto->getTbinventarionombre(),
                        'stock'   => (int) $producto->getTbinventariostock(),
                        'minimo'  => (int) $producto->getTbinventariostockminimo(),
                    ];
                }
            }

            usort($stockbajo, function ($a, $b) {
                return $a['stock'] <=> $b['stock'];
            });

            $pedidos = new pedidocontrolador();
            $resumen['stockbajo'] = $stockbajo;
            $resumen['pedidospendientes'] = $pedidos->listar(0, 'Pendiente')['datos'];
            $resumen['pedidosporrecibir'] = $pedidos->listar(0, 'Confirmado')['datos'];
        }

        return $resumen;
    }
}