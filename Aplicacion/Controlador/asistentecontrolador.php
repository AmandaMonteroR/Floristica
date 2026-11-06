<?php
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';
require_once __DIR__ . '/encargocontrolador.php';

// Asistente de presupuesto para los arreglos personalizados.
// Recibe el presupuesto, el % de ganancia y los productos que la clienta ya pidió (por
// ejemplo 5 rosas) y propone hasta 3 arreglos que caben en el presupuesto.
//
// Algoritmo: cada arreglo del catálogo es una "receta" (qué flores, papel y demás lleva y
// en qué proporción). Para cada receta:
// 1. Se apartan los productos que pidió la clienta y lo que ya se gastó en flores pintadas.
// 2. Se ponen el papel, la base y la cinta de la receta (lo que no depende del tamaño).
// 3. Las flores y follajes de la receta se agrandan o se achican en la misma proporción
//    (hasta 3 veces la receta) para llenar lo que queda del presupuesto, sin pasar del stock.
//    Si la clienta pidió una flor que está en la receta (por ejemplo 5 rosas rojas), esas
//    cuentan dentro de la proporción: nunca se ponen menos de las que pidió.
// 4. Lo que sobra se completa de a una flor, la que más le falte para mantener la proporción.
// Al final se ordenan: primero las que aprovechan mejor el presupuesto y las que ya llevan
// las flores que pidió la clienta.
class asistentecontrolador
{
    const PROPUESTAS = 3;
    const FACTORMAXIMO = 3.0;

    private $arreglos;
    private $inventario;
    private $temporadas;
    // Lo que no se agranda con el tamaño del arreglo
    private $estructura = ['Envoltura', 'Base'];
    // Lo que se quita si no alcanza el presupuesto
    private $opcionales = ['Chocolate', 'Peluche', 'Tarjeta', 'Accesorio'];

    public function __construct()
    {
        $this->arreglos = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
        $this->temporadas = new preciotemporadacontrolador();
    }

    public function sugerir($datos)
    {
        $presupuesto = trim((string) ($datos['presupuesto'] ?? ''));
        $porcentaje = trim((string) ($datos['porcentaje'] ?? ''));
        $reservado = trim((string) ($datos['reservado'] ?? '0'));
        $errores = [];

        if ($presupuesto === '' || !is_numeric($presupuesto) || (float) $presupuesto <= 0 || (float) $presupuesto > 10000000) {
            $errores['presupuesto'] = 'Escriba el presupuesto de la clienta (mayor que 0).';
        }
        if ($porcentaje === '' || !ctype_digit($porcentaje) || (int) $porcentaje > 500) {
            $errores['porcentaje'] = 'El % de ganancia debe ser un número entero de 0 a 500.';
        }
        if ($reservado === '' || !is_numeric($reservado) || (float) $reservado < 0) {
            $reservado = '0';
        }

        $productos = $this->productos((int) $porcentaje);
        $fijos = [];
        foreach ((array) ($datos['insumos'] ?? []) as $linea) {
            $id = (int) ($linea['tbinventarioid'] ?? 0);
            $cantidad = (int) ($linea['cantidad'] ?? 0);
            if ($id <= 0 || $cantidad <= 0) {
                continue;
            }
            if (!isset($productos[$id])) {
                $errores['insumos'] = 'Uno de los productos elegidos no existe o está inactivo.';
                continue;
            }
            $fijos[$id] = ($fijos[$id] ?? 0) + $cantidad;
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos del asistente.', ['errores' => $errores]);
        }

        $presupuesto = (float) $presupuesto;
        $disponible = $presupuesto - (float) $reservado;
        foreach ($fijos as $id => $cantidad) {
            $disponible -= $productos[$id]['precio'] * $cantidad;
        }

        if ($disponible < 0) {
            return $this->respuesta(false, 'Los productos que ya eligió (y las flores pintadas) se pasan del presupuesto por '
                . historicocontrolador::monto(-$disponible) . '. Quite algo o suba el presupuesto.');
        }

        $propuestas = [];
        foreach ($this->arreglos->listar('', 1) as $arreglo) {
            $propuesta = $this->usarreceta($arreglo, $productos, $fijos, $disponible, $presupuesto);
            if ($propuesta !== null) {
                $propuestas[] = $propuesta;
            }
        }

        usort($propuestas, function ($a, $b) {
            return $b['puntaje'] <=> $a['puntaje'];
        });

        // Sin repetir: si dos recetas usan las mismas flores, queda la primera
        $elegidas = [];
        $vistas = [];
        foreach ($propuestas as $propuesta) {
            $flores = [];
            foreach ($propuesta['lineas'] as $linea) {
                if ($linea['categoria'] === 'Flor') {
                    $flores[] = $linea['tbinventarioid'];
                }
            }
            sort($flores);
            $clave = implode(',', $flores);
            if (isset($vistas[$clave])) {
                continue;
            }
            $vistas[$clave] = true;
            unset($propuesta['puntaje']);
            $elegidas[] = $propuesta;
            if (count($elegidas) === self::PROPUESTAS) {
                break;
            }
        }

        if (!$elegidas) {
            return $this->respuesta(false, 'Con ese presupuesto no alcanza para armar un arreglo con las recetas del catálogo. '
                . 'Pruebe con un presupuesto mayor o un % de ganancia menor.');
        }

        return $this->respuesta(true, '', [
            'presupuesto' => $presupuesto,
            'porcentaje'  => (int) $porcentaje,
            'disponible'  => round($disponible, 2),
            'propuestas'  => $elegidas,
        ]);
    }

    // Productos activos (sin spray) con el precio que tendrían en el personalizado
    private function productos($porcentaje)
    {
        $costos = $this->temporadas->productos();
        $lista = [];
        foreach ($this->inventario->listar('', '', 1) as $producto) {
            if ($producto->getTbinventariocategoria() === 'Spray') {
                continue;
            }
            $id = (int) $producto->getTbinventarioid();
            $costo = $costos[$id]['costo'] ?? (float) $producto->getTbinventariopreciounidad();
            $lista[$id] = [
                'nombre'    => $producto->getTbinventarionombre(),
                'categoria' => $producto->getTbinventariocategoria(),
                'stock'     => (int) $producto->getTbinventariostock(),
                'costo'     => $costo,
                'precio'    => encargocontrolador::preciopersonalizado($costo, $porcentaje),
            ];
        }

        return $lista;
    }

    private function usarreceta($arreglo, $productos, $fijos, $disponible, $presupuesto)
    {
        $flores = [];
        $minimos = [];
        $estructura = [];
        $opcionales = [];

        foreach ($this->arreglos->listardetalles($arreglo->getTbarreglofloralid()) as $detalle) {
            $id = (int) $detalle->getTbinventarioid();
            // Si no está activo o es spray, no se agrega
            if (!isset($productos[$id])) {
                continue;
            }
            $cantidad = (int) $detalle->getTbarreglofloralinsumocantidad();
            $categoria = $productos[$id]['categoria'];

            if ($categoria === 'Flor') {
                $flores[$id] = $cantidad;
                // Las que pidió la clienta ya están pagadas: cuentan dentro de la proporción
                $minimos[$id] = $fijos[$id] ?? 0;
            } elseif (isset($fijos[$id])) {
                // Si la clienta ya eligió ese papel, base o accesorio, no se repite
                continue;
            } elseif (in_array($categoria, $this->estructura, true)) {
                $estructura[$id] = $cantidad;
            } else {
                $opcionales[$id] = $cantidad;
            }
        }

        // Si la receta no tiene flores, no sirve
        if (!$flores) {
            return null;
        }

        // Primero con todo; si no alcanza, sin chocolates, peluches, tarjetas ni accesorios
        foreach ([true, false] as $conopcionales) {
            $base = $estructura + ($conopcionales ? $opcionales : []);
            if (!$this->hayestock($base, $productos)) {
                continue;
            }
            $gastobase = $this->gasto($base, $productos);
            if ($gastobase > $disponible) {
                continue;
            }

            [$cantidades, $factor] = $this->ajustarflores($flores, $minimos, $productos, $disponible - $gastobase);
            // Lo que se agrega además de lo que pidió la clienta
            $agregadas = [];
            foreach ($cantidades as $id => $cantidad) {
                if ($cantidad - $minimos[$id] > 0) {
                    $agregadas[$id] = $cantidad - $minimos[$id];
                }
            }
            if (!$agregadas) {
                continue;
            }

            // Si sobra plata y alguna flor llegó al tope del inventario, se avisa
            $sinstock = [];
            foreach ($cantidades as $id => $cantidad) {
                if ($cantidad >= $productos[$id]['stock'] && $cantidad < round($flores[$id] * self::FACTORMAXIMO)) {
                    $sinstock[] = $productos[$id]['nombre'];
                }
            }

            return $this->armar($arreglo, $productos, $fijos, $base + $agregadas, $presupuesto, $disponible, $factor, $sinstock);
        }

        return null;
    }

    // Agranda o achica las flores en la misma proporción hasta llenar lo que queda (sin pasar del stock).
    // $minimos: las que pidió la clienta de cada flor (ya pagadas). Devuelve [cantidades totales, factor].
    private function ajustarflores($flores, $minimos, $productos, $queda)
    {
        $escalar = function ($factor) use ($flores, $minimos, $productos) {
            $cantidades = [];
            foreach ($flores as $id => $cantidad) {
                $limite = max($minimos[$id], $productos[$id]['stock']);
                $cantidades[$id] = max($minimos[$id], min($limite, (int) round($cantidad * $factor)));
            }
            return $cantidades;
        };
        $extra = function ($cantidades) use ($minimos, $productos) {
            $total = 0.0;
            foreach ($cantidades as $id => $cantidad) {
                $total += $productos[$id]['precio'] * ($cantidad - $minimos[$id]);
            }
            return $total;
        };

        // El factor más grande que cabe, de 0.05 en 0.05 (hasta 3 veces la receta)
        $mejor = [$minimos, 0.0];
        for ($factor = 0.05; $factor <= self::FACTORMAXIMO + 0.0001; $factor += 0.05) {
            $cantidades = $escalar($factor);
            if ($extra($cantidades) <= $queda) {
                $mejor = [$cantidades, round($factor, 2)];
            }
        }

        // Lo que sobra se completa de a una flor: la que más le falte para mantener la proporción,
        // sin pasar de la receta agrandada un poco más
        [$cantidades, $factor] = $mejor;
        $sobra = $queda - $extra($cantidades);
        for ($vuelta = 0; $vuelta < 300; $vuelta++) {
            $elegida = null;
            $menor = PHP_FLOAT_MAX;
            foreach ($flores as $id => $receta) {
                if ($productos[$id]['precio'] > $sobra || $cantidades[$id] >= $productos[$id]['stock']
                    || $cantidades[$id] >= ceil($receta * ($factor + 0.5))) {
                    continue;
                }
                $proporcion = $cantidades[$id] / $receta;
                if ($proporcion < $menor) {
                    $menor = $proporcion;
                    $elegida = $id;
                }
            }
            if ($elegida === null) {
                break;
            }
            $cantidades[$elegida]++;
            $sobra -= $productos[$elegida]['precio'];
        }

        return [$cantidades, $factor];
    }

    private function armar($arreglo, $productos, $fijos, $sugeridos, $presupuesto, $disponible, $factor, $sinstock)
    {
        $lineas = [];
        $total = 0.0;
        $costo = 0.0;

        // Una línea por producto: lo que pidió la clienta más lo que se agrega
        $cantidades = [];
        foreach ($fijos as $id => $cantidad) {
            $cantidades[$id] = ['pedidas' => $cantidad, 'agregadas' => 0];
        }
        foreach ($sugeridos as $id => $cantidad) {
            $cantidades[$id] = ['pedidas' => $cantidades[$id]['pedidas'] ?? 0, 'agregadas' => $cantidad];
        }

        foreach ($cantidades as $id => $cantidad) {
            $unidades = $cantidad['pedidas'] + $cantidad['agregadas'];
            $lineas[] = [
                'tbinventarioid' => $id,
                'nombre'         => $productos[$id]['nombre'],
                'categoria'      => $productos[$id]['categoria'],
                'cantidad'       => $unidades,
                'pedidas'        => $cantidad['pedidas'],
                'precio'         => $productos[$id]['precio'],
                'subtotal'       => round($productos[$id]['precio'] * $unidades, 2),
            ];
            $total += $productos[$id]['precio'] * $unidades;
            $costo += $productos[$id]['costo'] * $unidades;
        }

        $usado = $presupuesto - $disponible + ($total - $this->gasto($fijos, $productos));
        $aprovecha = $presupuesto > 0 ? min(1, $usado / $presupuesto) : 0;

        // Con lo que sobra: hasta 3 adicionales que caben (peluche, chocolate, accesorio...), del más caro al más barato
        $extras = [];
        $sobra = $presupuesto - $usado;
        $enlineas = array_column($lineas, 'tbinventarioid');
        foreach ($productos as $id => $producto) {
            if (in_array($producto['categoria'], $this->opcionales, true) && $producto['stock'] > 0
                && $producto['precio'] <= $sobra && !in_array($id, $enlineas, true)) {
                $extras[] = ['tbinventarioid' => $id, 'nombre' => $producto['nombre'], 'precio' => $producto['precio']];
            }
        }
        usort($extras, function ($a, $b) {
            return $b['precio'] <=> $a['precio'];
        });

        // Si la receta lleva las mismas flores que pidió la clienta (por ejemplo rosas), se parece más a lo que quiere
        $relacion = 0;
        foreach ($this->arreglos->listardetalles($arreglo->getTbarreglofloralid()) as $detalle) {
            if (isset($fijos[(int) $detalle->getTbinventarioid()])) {
                $relacion = 1;
                break;
            }
        }
        $tamano = $factor > 0 ? 1 - min(1, abs(log($factor)) / 2) : 0;

        return [
            'tbarreglofloralid'     => (int) $arreglo->getTbarreglofloralid(),
            'nombre'                => 'Al estilo de ' . $arreglo->getTbarreglofloralnombre(),
            'tbarreglofloralfoto'   => $arreglo->getTbarreglofloralfoto(),
            'lineas'                => $lineas,
            'total'                 => round($total, 2),
            'costo'                 => round($costo, 2),
            'ganancia'              => round($total - $costo, 2),
            'sobra'                 => round($presupuesto - $usado, 2),
            'sinstock'              => $sinstock,
            'extras'                => array_slice($extras, 0, 3),
            'flores'                => array_sum(array_map(function ($linea) {
                return $linea['categoria'] === 'Flor' ? $linea['cantidad'] : 0;
            }, $lineas)),
            'puntaje'               => round(0.6 * $aprovecha + 0.25 * $relacion + 0.15 * $tamano, 4),
        ];
    }

    private function gasto($cantidades, $productos)
    {
        $total = 0.0;
        foreach ($cantidades as $id => $cantidad) {
            $total += $productos[$id]['precio'] * $cantidad;
        }
        return $total;
    }

    private function hayestock($cantidades, $productos)
    {
        foreach ($cantidades as $id => $cantidad) {
            if ($cantidad > $productos[$id]['stock']) {
                return false;
            }
        }
        return true;
    }

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return ['exito' => $exito, 'mensaje' => $mensaje, 'datos' => $datos];
    }
}
