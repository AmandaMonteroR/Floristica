<?php
// Algoritmo de búsqueda por foto: saca la "huella" de una foto y compara huellas.
//
// La huella tiene tres partes:
// 1. Colores de toda la foto: de cada píxel se saca su color (tono, saturación y brillo) y se
//    cuenta en uno de 15 grupos (rojo, naranja, amarillo... blanco, gris, negro). Los píxeles
//    del centro y los de colores vivos cuentan más: ahí suelen estar las flores, y en las
//    orillas, el fondo (mesa, pared, papel).
// 2. Colores por zonas: lo mismo, pero en cada cuarto de la foto (arriba a la izquierda,
//    arriba a la derecha, abajo...). Así importa también dónde está cada color.
// 3. Forma: la foto en blanco y negro de 9 x 8 píxeles. Cada bit dice si un píxel es más
//    claro que el de su derecha (64 bits). Fotos con la misma forma dan bits parecidos.
//
// Parecido = 50% colores en común + 30% colores en común por zonas + 20% bits iguales de la forma.
class huellafoto
{
    const LADO = 48;
    const PESOCOLOR = 0.5;
    const PESOZONAS = 0.3;
    const PESOFORMA = 0.2;
    const PESOFONDO = 0.35;

    // Nombres de los 15 grupos de color, en el orden de la huella
    public static $colores = ['Rojo', 'Naranja', 'Amarillo', 'Verde lima', 'Verde', 'Verde agua', 'Celeste', 'Azul',
        'Azul oscuro', 'Morado', 'Fucsia', 'Rosado', 'Blanco', 'Gris', 'Negro'];

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
    // (sin gris ni negro, que casi siempre son el fondo)
    public static function principales($huella, $cantidad = 4)
    {
        $lista = [];
        foreach ($huella['colores'] as $grupo => $valor) {
            if ($valor >= 0.05 && $grupo < 13) {
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

        $vacio = array_fill(0, count(self::$colores), 0.0);
        $grupos = $vacio;
        $zonas = [$vacio, $vacio, $vacio, $vacio];
        $total = 0.0;
        $totalzonas = [0.0, 0.0, 0.0, 0.0];

        for ($y = 0; $y < $lado; $y++) {
            for ($x = 0; $x < $lado; $x++) {
                $rgb = imagecolorat($pequena, $x, $y);
                $grupo = self::grupo(($rgb >> 16) & 255, ($rgb >> 8) & 255, $rgb & 255);

                // El centro pesa más que las orillas (campana de Gauss), y el blanco, gris y negro
                // pesan menos que los colores vivos
                $dx = ($x + 0.5) / $lado - 0.5;
                $dy = ($y + 0.5) / $lado - 0.5;
                $peso = exp(-($dx * $dx + $dy * $dy) / (2 * 0.3 * 0.3)) * ($grupo >= 12 ? self::PESOFONDO : 1);

                $grupos[$grupo] += $peso;
                $total += $peso;

                $zona = ($y < $lado / 2 ? 0 : 2) + ($x < $lado / 2 ? 0 : 1);
                $zonas[$zona][$grupo] += $peso;
                $totalzonas[$zona] += $peso;
            }
        }
        imagedestroy($pequena);

        $normalizar = function ($lista, $suma) {
            return array_map(function ($valor) use ($suma) {
                return $suma > 0 ? round($valor / $suma, 4) : 0;
            }, $lista);
        };

        return [
            'colores' => $normalizar($grupos, $total),
            'zonas'   => array_map($normalizar, $zonas, $totalzonas),
        ];
    }

    // En qué grupo cae un color
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

        // 12 tonos de 30 grados; el rojo va de 345 a 15
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
