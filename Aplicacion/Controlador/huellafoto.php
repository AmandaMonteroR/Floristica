<?php
// Algoritmo de búsqueda por foto: saca la "huella" de una foto y compara huellas.
//
// La huella tiene tres partes:
// 1. Colores de toda la foto: de cada píxel se saca su color (tono, saturación y brillo) y se
//    cuenta en uno de 16 grupos (rojo, naranja, amarillo... blanco, gris, negro, café). Los píxeles
//    del centro y los de colores vivos cuentan más: ahí suelen estar las flores, y en las
//    orillas, el fondo (mesa, pared, papel). El naranja y el amarillo solo son de flor si son vivos:
//    apagados son café (papel kraft, piel, madera) y oscuros son follaje en sombra.
// 2. Colores por zonas: lo mismo, pero en cada cuarto de la foto (arriba a la izquierda,
//    arriba a la derecha, abajo...). Así importa también dónde está cada color.
// 3. Forma: la foto en blanco y negro de 9 x 8 píxeles. Cada bit dice si un píxel es más
//    claro que el de su derecha (64 bits). Fotos con la misma forma dan bits parecidos.
//
// Parecido = 50% colores en común + 30% colores en común por zonas + 20% bits iguales de la forma.
//
// Para "Valorar este ramo" se saca además de qué colores son las flores (coloresflores()): sin el fondo,
// que se encuentra con un relleno por inundación (BFS) desde la orilla de la foto (ver fondo()).
class huellafoto
{
    const LADO = 48;
    const PESOCOLOR = 0.5;
    const PESOZONAS = 0.3;
    const PESOFORMA = 0.2;
    const PESOFONDO = 0.35;

    // Colores de flores (para valorar un ramo)
    const SIGMAFLORES = 0.25;
    const CONTRASTEFONDO = 20;
    const MINIMOFLORES = 0.05;
    const MINIMOCOLORFLOR = 0.12;
    const RELATIVOCOLORFLOR = 0.25;
    const SATURACIONVIVO = 0.6;
    const BRILLOVIVO = 0.6;
    const SATURACIONROJO = 0.75;

    // Nombres de los 15 grupos de color, en el orden de la huella
    // Café (16.º) es lo cálido pero apagado: papel kraft, piel de la mano, madera, canasta, pared beige.
    // No es color de flor: así el papel kraft ya no se confunde con flores naranjas.
    public static $colores = ['Rojo', 'Naranja', 'Amarillo', 'Verde lima', 'Verde', 'Verde agua', 'Celeste', 'Azul',
        'Azul oscuro', 'Morado', 'Fucsia', 'Rosado', 'Blanco', 'Gris', 'Negro', 'Café'];

    // Grupos que son colores de flores (sin verdes, gris, negro ni café)
    public static $gruposflor = [0, 1, 2, 6, 7, 8, 9, 10, 11, 12];

    // Versión del algoritmo: si cambia, las huellas guardadas del catálogo se calculan de nuevo
    const VERSION = 2;

    // Devuelve ['colores' => [15 números], 'zonas' => [4 x 15 números], 'forma' => '16 letras hexadecimales'],
    // o null si el archivo no es una imagen
    public static function calcular($archivo)
    {
        $contenido = @file_get_contents($archivo);
        $imagen = $contenido === false ? false : @imagecreatefromstring($contenido);
        if (!$imagen) {
            return null;
        }

        $huella = self::colores($imagen);
        $huella['forma'] = self::forma($imagen);
        $huella['version'] = self::VERSION;
        imagedestroy($imagen);

        return $huella;
    }

    // Qué tan parecidas son dos huellas, de 0 a 100
    public static function parecido($a, $b)
    {
        $comun = self::encomun($a['colores'], $b['colores']);

        $zonas = 0.0;
        foreach ($a['zonas'] as $zona => $colores) {
            $zonas += self::encomun($colores, $b['zonas'][$zona]) / count($a['zonas']);
        }

        $distintos = 0;
        for ($i = 0; $i < 16; $i++) {
            $distintos += substr_count(decbin(hexdec($a['forma'][$i]) ^ hexdec($b['forma'][$i])), '1');
        }
        $forma = 1 - $distintos / 64;

        return round((self::PESOCOLOR * $comun + self::PESOZONAS * $zonas + self::PESOFORMA * $forma) * 100, 1);
    }

    // Cuánto de los colores tienen en común dos fotos (de 0 a 1)
    private static function encomun($a, $b)
    {
        $comun = 0.0;
        foreach ($a as $grupo => $valor) {
            $comun += min($valor, $b[$grupo] ?? 0);
        }
        return $comun;
    }

    // Los colores vivos que más tiene la foto, para mostrarlos: [['nombre' => 'Rojo', 'porcentaje' => 42], ...]
    // (sin gris, negro ni café, que casi siempre son el fondo, el papel kraft o la mano)
    public static function principales($huella, $cantidad = 4)
    {
        $lista = [];
        foreach ($huella['colores'] as $grupo => $valor) {
            if ($valor >= 0.05 && in_array($grupo, self::$gruposflor, true) || $valor >= 0.05 && in_array($grupo, [3, 4, 5], true)) {
                $lista[] = ['nombre' => self::$colores[$grupo], 'porcentaje' => (int) round($valor * 100)];
            }
        }
        usort($lista, function ($x, $y) {
            return $y['porcentaje'] - $x['porcentaje'];
        });

        return array_slice($lista, 0, $cantidad);
    }

    private static function colores($imagen)
    {
        $lado = self::LADO;
        $pequena = imagecreatetruecolor($lado, $lado);
        imagecopyresampled($pequena, $imagen, 0, 0, 0, 0, $lado, $lado, imagesx($imagen), imagesy($imagen));

        // Grupo de color y gris (brillo) de cada píxel
        $grupopixel = [];
        $grispixel = [];
        for ($y = 0; $y < $lado; $y++) {
            for ($x = 0; $x < $lado; $x++) {
                $rgb = imagecolorat($pequena, $x, $y);
                $grupopixel[$y][$x] = self::grupo(($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255);
                $grispixel[$y][$x] = self::gris($pequena, $x, $y);
            }
        }
        imagedestroy($pequena);
        $fondo = self::fondo($grupopixel, $grispixel);

        $vacio = array_fill(0, count(self::$colores), 0.0);
        $grupos = $vacio;
        $zonas = [$vacio, $vacio, $vacio, $vacio];
        $flores = $vacio;
        $total = 0.0;
        $totalzonas = [0.0, 0.0, 0.0, 0.0];
        $totalflores = 0.0;
        $pixelesfondo = 0;

        for ($y = 0; $y < $lado; $y++) {
            for ($x = 0; $x < $lado; $x++) {
                $grupo = $grupopixel[$y][$x];

                // Para comparar fotos: el centro pesa más que las orillas (campana de Gauss), y el blanco,
                // gris, negro y café pesan menos que los colores vivos
                $dx = ($x + 0.5) / $lado - 0.5;
                $dy = ($y + 0.5) / $lado - 0.5;
                $distancia = $dx * $dx + $dy * $dy;
                $peso = exp(-$distancia / (2 * 0.3 * 0.3)) * ($grupo >= 12 ? self::PESOFONDO : 1);

                $grupos[$grupo] += $peso;
                $total += $peso;

                $zona = ($y < $lado / 2 ? 0 : 2) + ($x < $lado / 2 ? 0 : 1);
                $zonas[$zona][$grupo] += $peso;
                $totalzonas[$zona] += $peso;

                // Para saber de qué color son las flores: sin el fondo (pared, mesa) y con una campana
                // más angosta; el blanco cuenta igual que los demás, porque puede ser de flores blancas
                if (isset($fondo[$y][$x])) {
                    $pixelesfondo++;
                    continue;
                }
                $pesoflor = exp(-$distancia / (2 * self::SIGMAFLORES * self::SIGMAFLORES));
                $flores[$grupo] += $pesoflor;
                $totalflores += $pesoflor;
            }
        }

        $normalizar = function ($lista, $suma) {
            return array_map(function ($valor) use ($suma) {
                return $suma > 0 ? round($valor / $suma, 4) : 0;
            }, $lista);
        };

        return [
            'colores' => $normalizar($grupos, $total),
            'zonas'   => array_map($normalizar, $zonas, $totalzonas),
            'flores'  => $normalizar($flores, $totalflores),
            'fondo'   => round($pixelesfondo / ($lado * $lado), 4),
        ];
    }

    // El fondo de la foto (pared, mesa, piso) con un relleno por inundación (búsqueda en anchura, BFS):
    // se empieza en los píxeles de la orilla y se avanza a los vecinos (arriba, abajo, izquierda y derecha)
    // mientras sean claros o apagados (blanco, gris o café) y lisos. Las flores blancas no son lisas:
    // los pétalos tienen sombras y bordes, así que el relleno se detiene en ellas.
    // Devuelve [y][x] => true de los píxeles que son fondo.
    private static function fondo($grupopixel, $grispixel)
    {
        $lado = count($grupopixel);
        $fondo = [];
        $cola = new SplQueue();

        $esfondo = function ($x, $y) use ($grupopixel, $grispixel, $lado) {
            if (!in_array($grupopixel[$y][$x], [12, 13, 15], true)) {
                return false;
            }
            // Liso: poca diferencia de brillo con los vecinos de alrededor
            $minimo = 255;
            $maximo = 0;
            for ($j = max(0, $y - 1); $j <= min($lado - 1, $y + 1); $j++) {
                for ($i = max(0, $x - 1); $i <= min($lado - 1, $x + 1); $i++) {
                    $minimo = min($minimo, $grispixel[$j][$i]);
                    $maximo = max($maximo, $grispixel[$j][$i]);
                }
            }
            return $maximo - $minimo <= self::CONTRASTEFONDO;
        };

        for ($i = 0; $i < $lado; $i++) {
            foreach ([[$i, 0], [$i, $lado - 1], [0, $i], [$lado - 1, $i]] as [$x, $y]) {
                if (!isset($fondo[$y][$x]) && $esfondo($x, $y)) {
                    $fondo[$y][$x] = true;
                    $cola->enqueue([$x, $y]);
                }
            }
        }

        while (!$cola->isEmpty()) {
            [$x, $y] = $cola->dequeue();
            foreach ([[1, 0], [-1, 0], [0, 1], [0, -1]] as [$mx, $my]) {
                $nx = $x + $mx;
                $ny = $y + $my;
                if ($nx >= 0 && $ny >= 0 && $nx < $lado && $ny < $lado && !isset($fondo[$ny][$nx]) && $esfondo($nx, $ny)) {
                    $fondo[$ny][$nx] = true;
                    $cola->enqueue([$nx, $ny]);
                }
            }
        }

        return $fondo;
    }

    // De qué colores son las flores de la foto: [grupo => parte de 0 a 1], del que más al que menos.
    // Se usa en "Valorar este ramo".
    // - Solo cuentan los colores de flores (sin verdes, gris, negro ni café) y del centro de la foto.
    // - El fondo (pared, mesa) no cuenta: se quita con un relleno por inundación desde la orilla (ver fondo()).
    // - Un color se toma si es al menos el 12% de los colores de flores y al menos la cuarta parte
    //   del color principal (así los centros amarillos de las margaritas no se vuelven girasoles).
    public static function coloresflores($huella, $maximo = 4)
    {
        $flores = $huella['flores'] ?? $huella['colores'];

        $partes = [];
        foreach (self::$gruposflor as $grupo) {
            $partes[$grupo] = (float) ($flores[$grupo] ?? 0);
        }

        $suma = array_sum($partes);
        if ($suma < self::MINIMOFLORES) {
            return [];
        }
        foreach ($partes as $grupo => $parte) {
            $partes[$grupo] = $parte / $suma;
        }
        arsort($partes);
        $mayor = reset($partes);

        $vistos = [];
        foreach ($partes as $grupo => $parte) {
            if ($parte >= self::MINIMOCOLORFLOR && $parte >= $mayor * self::RELATIVOCOLORFLOR) {
                $vistos[$grupo] = round($parte, 4);
            }
        }

        return array_slice($vistos, 0, $maximo, true);
    }

    // En qué grupo cae un color.
    // Los tonos cálidos (naranja y amarillo) se revisan con más cuidado, porque ahí caen el papel kraft,
    // la piel, la madera y el follaje en sombra: solo son flores si el color es vivo (saturado y claro).
    private static function grupo($rojo, $verde, $azul)
    {
        $maximo = max($rojo, $verde, $azul);
        $minimo = min($rojo, $verde, $azul);
        $brillo = $maximo / 255;
        $saturacion = $maximo === 0 ? 0 : ($maximo - $minimo) / $maximo;

        if ($brillo < 0.2) {
            return 14; // Negro
        }
        if ($saturacion < 0.2) {
            return $brillo > 0.75 ? 12 : 13; // Blanco o gris
        }

        $diferencia = $maximo - $minimo;
        if ($maximo === $rojo) {
            $tono = 60 * fmod(($verde - $azul) / $diferencia, 6);
        } elseif ($maximo === $verde) {
            $tono = 60 * (($azul - $rojo) / $diferencia + 2);
        } else {
            $tono = 60 * (($rojo - $verde) / $diferencia + 4);
        }
        if ($tono < 0) {
            $tono += 360;
        }

        if ($tono >= 15 && $tono < 45) {
            // Naranja vivo (gerbera) o café (kraft, piel, madera); muy pálido es blanco crema
            if ($saturacion >= self::SATURACIONVIVO && $brillo >= self::BRILLOVIVO) {
                return 1;
            }
            return $saturacion < 0.3 && $brillo >= 0.8 ? 12 : 15;
        }
        if ($tono >= 45 && $tono < 75) {
            // Amarillo vivo (girasol); oscuro es follaje en sombra; pálido es blanco crema o beige
            if ($saturacion >= self::SATURACIONVIVO - 0.1 && $brillo >= self::BRILLOVIVO) {
                return 2;
            }
            if ($brillo < self::BRILLOVIVO) {
                return 4;
            }
            return $saturacion < 0.3 ? 12 : 15;
        }
        if ($tono >= 345 || $tono < 15) {
            // Rojo de verdad (rosa roja) es muy saturado; menos saturado es rosado (lirio, rosa rosada).
            // Del lado naranja y apagado es piel.
            if ($saturacion >= self::SATURACIONROJO) {
                return 0;
            }
            return $tono < 15 && $saturacion < 0.4 ? 15 : 11;
        }

        // Los demás tonos, de 30 en 30 grados
        return (int) floor(fmod($tono + 15, 360) / 30);
    }

    private static function forma($imagen)
    {
        $pequena = imagecreatetruecolor(9, 8);
        imagecopyresampled($pequena, $imagen, 0, 0, 0, 0, 9, 8, imagesx($imagen), imagesy($imagen));

        $bits = '';
        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $bits .= self::gris($pequena, $x, $y) < self::gris($pequena, $x + 1, $y) ? '1' : '0';
            }
        }
        imagedestroy($pequena);

        $hexadecimal = '';
        foreach (str_split($bits, 4) as $grupo) {
            $hexadecimal .= dechex(bindec($grupo));
        }

        return $hexadecimal;
    }

    private static function gris($imagen, $x, $y)
    {
        $rgb = imagecolorat($imagen, $x, $y);
        return 0.299 * (($rgb >> 16) & 255) + 0.587 * (($rgb >> 8) & 255) + 0.114 * ($rgb & 255);
    }
}
