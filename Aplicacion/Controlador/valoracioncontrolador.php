<?php
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/valoracionrepositorio.php';
require_once __DIR__ . '/busquedafotocontrolador.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';
require_once __DIR__ . '/encargocontrolador.php';
require_once __DIR__ . '/huellafoto.php';

// Valorar un ramo por foto: cuando la clienta trae una foto que no se parece a ningún arreglo
// del catálogo, el sistema calcula cuánto costaría hacerlo con lo que hay en el inventario.
//
// El sistema no reconoce qué flor es cada una ni las cuenta: la empleada dice cuántas flores
// ve en la foto (o elige pequeño, mediano o grande) y el algoritmo hace lo demás:
// 1. Saca los colores de la foto (la misma huella de la búsqueda por foto).
// 2. Los colores de flores (rojo, rosado, blanco...) que tienen al menos 6% de la foto
//    (el blanco 12%, porque también puede ser fondo o papel) se pasan a flores del inventario
//    de ese color: la que más se usa en las recetas del catálogo y que tiene stock.
//    Un color pegado a otro más fuerte (naranja junto a rojo) necesita 15%: si no, suele ser
//    la sombra o el borde de las mismas flores.
// 3. Las flores se reparten según cuánto ocupa cada color en la foto.
// 4. Follaje: 1 por cada 3 flores si la foto tiene verde (5% o más); si no, 1 por cada 6.
// 5. Papel: 1 pliego por cada 8 flores (de 1 a 4), negro si la foto tiene mucho negro; si no,
//    el que más se usa con la flor principal en el catálogo. Cinta: 1.
// 6. Cada producto se cobra con el % de ganancia, igual que en un personalizado.
// La empleada puede corregir todo después en la solicitud.
class valoracioncontrolador
{
    const MINIMOCOLOR = 0.06;
    const MINIMOVECINO = 0.15;
    const MINIMOBLANCO = 0.12;
    const MINIMOVERDE = 0.05;
    const MINIMONEGRO = 0.10;
    const MAXIMOCOLORES = 4;

    // Grupo de color de la huella => colores de flores del inventario que le corresponden
    private $equivalencias = [
        0  => ['rojo'],
        1  => ['naranja'],
        2  => ['amarillo'],
        6  => ['celeste', 'azul'],
        7  => ['azul', 'celeste'],
        8  => ['azul', 'morado'],
        9  => ['morado', 'lila'],
        10 => ['fucsia', 'rosado'],
        11 => ['rosado', 'fucsia'],
        12 => ['blanco', 'crema'],
    ];

    private $inventario;
    private $datos;
    private $temporadas;
    private $carpetafotos;
    private $tiposfoto = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct()
    {
        $this->inventario = new inventariorepositorio();
        $this->datos = new valoracionrepositorio();
        $this->temporadas = new preciotemporadacontrolador();
        $this->carpetafotos = __DIR__ . '/../../Publico/imagenes/solicitudes/';
    }

    // $foto: la foto subida, o null si se vuelve a calcular con la que ya se guardó ($datos['referencia'])
    public function valorar($datos, $foto, $espropietario)
    {
        if (!function_exists('imagecreatefromstring')) {
            return $this->respuesta(false, 'Falta la extensión GD de PHP para leer fotos. '
                . 'Instálela con "sudo apt install php-gd" y vuelva a encender el servidor.');
        }

        $flores = trim((string) ($datos['flores'] ?? ''));
        $porcentaje = trim((string) ($datos['porcentaje'] ?? ''));
        $referencia = trim((string) ($datos['referencia'] ?? ''));
        $errores = [];

        if (!ctype_digit($flores) || (int) $flores < 1 || (int) $flores > 100) {
            $errores['flores'] = 'Escriba cuántas flores tiene el ramo (de 1 a 100).';
        }
        if (!ctype_digit($porcentaje) || (int) $porcentaje > 500) {
            $errores['porcentaje'] = 'El % de ganancia debe ser un número entero de 0 a 500.';
        }

        $subida = is_array($foto) && ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        if ($subida) {
            $error = (new busquedafotocontrolador())->validarfoto($foto);
            if ($error !== '') {
                $errores['foto'] = $error;
            }
        } elseif ($referencia === '') {
            $errores['foto'] = 'Elija la foto del ramo que quiere la clienta.';
        } elseif (!preg_match('#^imagenes/solicitudes/referencia_\d{14}_[0-9a-f]{8}\.(jpg|png|webp)$#', $referencia)
            || !is_file(__DIR__ . '/../../Publico/' . $referencia)) {
            $errores['foto'] = 'La foto ya no está en el sistema. Vuelva a subirla.';
        }

        if ($errores) {
            return $this->respuesta(false, reset($errores), ['errores' => $errores]);
        }

        $archivo = $subida ? $foto['tmp_name'] : __DIR__ . '/../../Publico/' . $referencia;
        $huella = huellafoto::calcular($archivo);
        if ($huella === null) {
            return $this->respuesta(false, 'No se pudo leer la foto. Pruebe con otra imagen.', ['errores' => ['foto' => 'No se pudo leer la foto.']]);
        }

        // La foto de la clienta se guarda para ponerla como foto de referencia de la solicitud
        if ($subida) {
            $referencia = $this->guardarfoto($foto);
            if ($referencia === null) {
                return $this->respuesta(false, 'No se pudo guardar la foto. Revise que exista la carpeta Publico/imagenes/solicitudes.');
            }
        }

        $resultado = $this->calcular($huella['colores'], (int) $flores, (int) $porcentaje);

        if (!$espropietario) {
            unset($resultado['costo'], $resultado['ganancia']);
            foreach ($resultado['lineas'] as &$linea) {
                unset($linea['costo']);
            }
            unset($linea);
        }

        $resultado['referencia'] = $referencia;
        $resultado['colores'] = huellafoto::principales($huella);
        $resultado['porcentaje'] = (int) $porcentaje;
        $resultado['flores'] = (int) $flores;

        return $this->respuesta(true, '', $resultado);
    }

    // El algoritmo (pasos 2 a 6). $colores: los 15 grupos de la huella (suman 1).
    public function calcular($colores, $flores, $porcentaje)
    {
        $productos = $this->productos($porcentaje);
        $notas = [];
        $usados = [];

        // 2. Colores de flores que se ven en la foto, del que más ocupa al que menos
        $vistos = [];
        foreach ($this->equivalencias as $grupo => $nombres) {
            $minimo = $grupo === 12 ? self::MINIMOBLANCO : self::MINIMOCOLOR;
            $parte = (float) ($colores[$grupo] ?? 0);
            if ($grupo < 12 && $parte < self::MINIMOVECINO) {
                $vecinos = [($grupo + 11) % 12, ($grupo + 1) % 12];
                foreach ($vecinos as $vecino) {
                    if (($colores[$vecino] ?? 0) > $parte) {
                        $minimo = self::MINIMOVECINO;
                    }
                }
            }
            if ($parte >= $minimo) {
                $vistos[$grupo] = $parte;
            }
        }
        arsort($vistos);
        $vistos = array_slice($vistos, 0, min(self::MAXIMOCOLORES, $flores), true);

        // Cada color se pasa a una flor; si dos colores dan la misma flor, se suman
        $partes = [];
        foreach ($vistos as $grupo => $parte) {
            $lista = $this->candidatas($productos, 'Flor', $this->equivalencias[$grupo]);
            if (!$lista) {
                $notas[] = 'No hay flores de color ' . mb_strtolower(huellafoto::$colores[$grupo]) . ' con stock en el inventario.';
                continue;
            }
            $id = $lista[0];
            if (!isset($partes[$id])) {
                $partes[$id] = ['parte' => 0, 'grupo' => $grupo];
            }
            $partes[$id]['parte'] += $parte;
        }

        // Si no se distinguió ningún color de flor (por ejemplo, una foto casi toda verde)
        if (!$partes) {
            $lista = $this->candidatas($productos, 'Flor', []);
            if (!$lista) {
                return $this->vacio($notas, 'No hay flores con stock en el inventario para valorar el ramo.');
            }
            $partes[$lista[0]] = ['parte' => 1, 'grupo' => null];
            $notas[] = 'No se distinguió bien el color de las flores; se usó ' . $productos[$lista[0]]['nombre'] . '. Corríjalo si hace falta.';
        }

        // 3. Se reparten las flores según lo que ocupa cada color (cada color lleva al menos 1)
        $cantidades = $this->repartir(array_map(function ($parte) {
            return $parte['parte'];
        }, $partes), $flores);

        $lineas = [];
        foreach ($cantidades as $id => $cantidad) {
            $grupo = $partes[$id]['grupo'];
            $nombres = $grupo === null ? [] : $this->equivalencias[$grupo];
            $faltan = $this->agregar($lineas, $usados, $productos, 'Flor', $nombres, $id, $cantidad,
                $grupo === null ? '' : huellafoto::$colores[$grupo]);
            if ($faltan > 0) {
                $notas[] = 'Faltan ' . $faltan . ' flores de color ' . mb_strtolower(huellafoto::$colores[$grupo] ?? '')
                    . ' en el inventario; el ramo quedaría con menos flores.';
            }
        }

        // 4. Follaje
        $verde = ($colores[3] ?? 0) + ($colores[4] ?? 0) + ($colores[5] ?? 0);
        $follaje = $flores >= 3 ? max(1, (int) round($flores / ($verde >= self::MINIMOVERDE ? 3 : 6))) : ($verde >= self::MINIMOVERDE ? 1 : 0);
        if ($follaje > 0) {
            $lista = $this->candidatas($productos, 'Follaje', ['verde'], $usados);
            if ($lista) {
                $this->agregar($lineas, $usados, $productos, 'Follaje', ['verde'], $lista[0], $follaje, 'Verde');
            } else {
                $notas[] = 'No hay follaje con stock en el inventario.';
            }
        }

        // 5. Papel y cinta
        $principal = array_key_first($cantidades);
        $papeles = (int) min(4, max(1, ceil($flores / 8)));
        $papel = ($colores[14] ?? 0) >= self::MINIMONEGRO ? $this->envoltura($productos, 'Papel', $papeles, 'negro', 0) : null;
        $papel = $papel ?? $this->envoltura($productos, 'Papel', $papeles, '', $principal);
        if ($papel !== null) {
            $this->agregar($lineas, $usados, $productos, 'Papel', [], $papel, $papeles, '');
        } else {
            $notas[] = 'No hay papel con stock suficiente en el inventario.';
        }
        $cinta = $this->envoltura($productos, 'Cinta', 1, '', $principal);
        if ($cinta !== null) {
            $this->agregar($lineas, $usados, $productos, 'Cinta', [], $cinta, 1, '');
        }

        // 6. Precio con el % de ganancia
        $total = 0.0;
        $costo = 0.0;
        $cuentaflores = 0;
        foreach ($lineas as &$linea) {
            $linea['subtotal'] = round($linea['precio'] * $linea['cantidad'], 2);
            $total += $linea['subtotal'];
            $costo += $linea['costo'] * $linea['cantidad'];
            if ($linea['tipo'] === 'Flor') {
                $cuentaflores += $linea['cantidad'];
            }
        }
        unset($linea);

        return [
            'lineas'       => $lineas,
            'total'        => round($total, 2),
            'costo'        => round($costo, 2),
            'ganancia'     => round($total - $costo, 2),
            'cuentaflores' => $cuentaflores,
            'notas'        => $notas,
        ];
    }

    // Reparte $total unidades según las partes (la mayor parte se lleva lo que sobra al redondear)
    private function repartir($partes, $total)
    {
        $suma = array_sum($partes);
        $cuotas = [];
        $cantidades = [];
        foreach ($partes as $id => $parte) {
            $cuotas[$id] = $total * $parte / $suma;
            $cantidades[$id] = max(1, (int) floor($cuotas[$id]));
        }
        while (array_sum($cantidades) < $total) {
            $mejor = null;
            foreach ($cantidades as $id => $cantidad) {
                if ($mejor === null || $cuotas[$id] - $cantidad > $cuotas[$mejor] - $cantidades[$mejor]) {
                    $mejor = $id;
                }
            }
            $cantidades[$mejor]++;
        }
        while (array_sum($cantidades) > $total) {
            $mayor = array_search(max($cantidades), $cantidades, true);
            $cantidades[$mayor]--;
        }
        arsort($cantidades);

        return $cantidades;
    }

    // Agrega $cantidad del producto; si no alcanza el stock, completa con otro del mismo color.
    // Devuelve cuántas unidades no se pudieron poner.
    private function agregar(&$lineas, &$usados, $productos, $tipo, $nombres, $id, $cantidad, $color)
    {
        while ($cantidad > 0 && $id !== null) {
            $usados[$id] = true;
            // Si el producto ya está en la lista, se suma a esa línea sin pasar del stock
            $posicion = null;
            foreach ($lineas as $numero => $linea) {
                if ($linea['tbinventarioid'] === $id) {
                    $posicion = $numero;
                }
            }
            $yapuestas = $posicion === null ? 0 : $lineas[$posicion]['cantidad'];
            $poner = max(0, min($cantidad, $productos[$id]['stock'] - $yapuestas));
            if ($poner > 0 && $posicion !== null) {
                $lineas[$posicion]['cantidad'] += $poner;
                $cantidad -= $poner;
            } elseif ($poner > 0) {
                $lineas[] = [
                    'tbinventarioid' => $id,
                    'nombre'         => $productos[$id]['nombre'],
                    'tipo'           => $tipo,
                    'color'          => $color,
                    'cantidad'       => $poner,
                    'precio'         => $productos[$id]['precio'],
                    'costo'          => $productos[$id]['costo'],
                ];
                $cantidad -= $poner;
            }
            if ($cantidad > 0 && in_array($tipo, ['Flor', 'Follaje'], true)) {
                $lista = $this->candidatas($productos, $tipo, $nombres, $usados);
                $id = $lista[0] ?? null;
            } else {
                $id = null;
            }
        }

        return $cantidad;
    }

    // Flores o follajes con stock del color pedido (el primer color de la lista va primero),
    // de la que más se usa en el catálogo a la que menos. Sin colores: todas.
    private function candidatas($productos, $tipo, $nombres, $excluir = [])
    {
        $usos = $this->usos();
        $lista = [];
        foreach ($productos as $id => $producto) {
            if ($producto['tipo'] !== $tipo || $producto['stock'] <= 0 || isset($excluir[$id])) {
                continue;
            }
            $orden = $nombres ? array_search($this->normalizar($producto['color']), $nombres, true) : 0;
            if ($orden === false) {
                continue;
            }
            $lista[] = [$id, $orden, $usos[$id] ?? 0, $producto['stock']];
        }
        usort($lista, function ($a, $b) {
            return [$a[1], $b[2], $b[3], $a[0]] <=> [$b[1], $a[2], $a[3], $b[0]];
        });

        return array_column($lista, 0);
    }

    // Papel o cinta con stock suficiente: del color pedido, o el que más se usa junto con la flor principal
    private function envoltura($productos, $clase, $cantidad, $color, $idflor)
    {
        $junto = $idflor ? $this->datos->usos($idflor) : [];
        $usos = $this->usos();
        $lista = [];
        foreach ($productos as $id => $producto) {
            if ($producto['tipo'] !== $clase || $producto['stock'] < $cantidad) {
                continue;
            }
            if ($color !== '' && $this->normalizar($producto['color']) !== $color) {
                continue;
            }
            $lista[] = [$id, $junto[$id] ?? 0, $usos[$id] ?? 0, $producto['stock']];
        }
        usort($lista, function ($a, $b) {
            return [$b[1], $b[2], $b[3], $a[0]] <=> [$a[1], $a[2], $a[3], $b[0]];
        });

        return $lista[0][0] ?? null;
    }

    private $usos = null;

    private function usos()
    {
        if ($this->usos === null) {
            $this->usos = $this->datos->usos();
        }
        return $this->usos;
    }

    // Flores, follajes, papeles y cintas activos, con su color y el precio con el % de ganancia
    private function productos($porcentaje)
    {
        $costos = $this->temporadas->productos();
        $atributos = $this->datos->flores();
        foreach ($this->datos->envolturas() as $id => $envoltura) {
            $atributos[$id] = ['tipo' => $envoltura['clase'], 'color' => $envoltura['color']];
        }

        $lista = [];
        foreach ($this->inventario->listar('', '', 1) as $producto) {
            $id = (int) $producto->getTbinventarioid();
            if (!isset($atributos[$id])) {
                continue;
            }
            $costo = (float) ($costos[$id]['costo'] ?? $producto->getTbinventariopreciounidad());
            $lista[$id] = [
                'nombre' => $producto->getTbinventarionombre(),
                'tipo'   => $atributos[$id]['tipo'],
                'color'  => $atributos[$id]['color'],
                'stock'  => (int) $producto->getTbinventariostock(),
                'costo'  => $costo,
                'precio' => encargocontrolador::preciopersonalizado($costo, $porcentaje),
            ];
        }

        return $lista;
    }

    private function normalizar($texto)
    {
        return strtr(mb_strtolower(trim((string) $texto)), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    }

    private function vacio($notas, $mensaje)
    {
        $notas[] = $mensaje;
        return ['lineas' => [], 'total' => 0, 'costo' => 0, 'ganancia' => 0, 'cuentaflores' => 0, 'notas' => $notas];
    }

    private function guardarfoto($foto)
    {
        $tipo = getimagesize($foto['tmp_name'])['mime'] ?? '';
        if (!isset($this->tiposfoto[$tipo])) {
            return null;
        }
        if (!is_dir($this->carpetafotos)) {
            @mkdir($this->carpetafotos, 0775, true);
        }

        $nombre = 'referencia_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $this->tiposfoto[$tipo];
        if (!move_uploaded_file($foto['tmp_name'], $this->carpetafotos . $nombre)) {
            return null;
        }

        return 'imagenes/solicitudes/' . $nombre;
    }

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return ['exito' => $exito, 'mensaje' => $mensaje, 'datos' => $datos];
    }
}
