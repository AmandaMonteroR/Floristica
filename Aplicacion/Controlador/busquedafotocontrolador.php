<?php
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/huellarepositorio.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';
require_once __DIR__ . '/huellafoto.php';

// Búsqueda en el catálogo por foto: recibe la foto que mandó la clienta, saca su huella
// y la compara con la de cada arreglo activo del catálogo. Devuelve los más parecidos.
class busquedafotocontrolador
{
    const RESULTADOS = 6;

    private $arreglos;
    private $huellas;
    private $temporadas;
    private $tiposfoto = ['image/jpeg', 'image/png', 'image/webp'];
    private $tamanomaximo = 5242880;

    public function __construct()
    {
        $this->arreglos = new arreglofloralrepositorio();
        $this->huellas = new huellarepositorio();
        $this->temporadas = new preciotemporadacontrolador();
    }

    public function buscar($foto)
    {
        // Las fotos se leen con GD; en Ubuntu se instala con: sudo apt install php-gd
        if (!function_exists('imagecreatefromstring')) {
            return $this->respuesta(false, 'Falta la extensión GD de PHP para comparar fotos. '
                . 'Instálela con "sudo apt install php-gd" y vuelva a encender el servidor.');
        }

        $error = $this->validarfoto($foto);
        if ($error !== '') {
            return $this->respuesta(false, $error, ['errores' => ['foto' => $error]]);
        }

        $buscada = huellafoto::calcular($foto['tmp_name']);
        if ($buscada === null) {
            return $this->respuesta(false, 'No se pudo leer la foto. Pruebe con otra imagen.', ['errores' => ['foto' => 'No se pudo leer la foto.']]);
        }

        $guardadas = $this->huellas->listar();
        $idalta = $this->temporadas->idalta();
        $resultados = [];

        foreach ($this->arreglos->listar('', 1) as $arreglo) {
            $huella = $this->huelladearreglo($arreglo, $guardadas);
            if ($huella === null) {
                continue;
            }

            $actual = $this->temporadas->arreglo($arreglo);
            $resultados[] = [
                'tbarreglofloralid'          => (int) $arreglo->getTbarreglofloralid(),
                'tbarreglofloralnombre'      => $arreglo->getTbarreglofloralnombre(),
                'tbarreglofloraldescripcion' => (string) $arreglo->getTbarreglofloraldescripcion(),
                'tbarreglofloralfoto'        => $arreglo->getTbarreglofloralfoto(),
                'precioactual'               => $actual['precio'],
                'precionormal'               => (float) $arreglo->getTbarreglofloralprecio(),
                'precioalto'                 => $this->temporadas->arreglo($arreglo, $idalta)['precio'],
                'altaencendida'              => $this->temporadas->datosvigente() !== null,
                'parecido'                   => huellafoto::parecido($buscada, $huella),
                'colores'                    => huellafoto::principales($huella, 3),
            ];
        }

        usort($resultados, function ($a, $b) {
            return $b['parecido'] <=> $a['parecido'];
        });

        return $this->respuesta(true, '', [
            'colores'    => huellafoto::principales($buscada),
            'revisados'  => count($resultados),
            'resultados' => array_slice($resultados, 0, self::RESULTADOS),
        ]);
    }

    // La huella guardada del arreglo, o se calcula y se guarda si no hay o si cambió la foto
    private function huelladearreglo($arreglo, $guardadas)
    {
        $id = (int) $arreglo->getTbarreglofloralid();
        $foto = (string) $arreglo->getTbarreglofloralfoto();

        if (isset($guardadas[$id]) && $guardadas[$id]['foto'] === $foto && is_array($guardadas[$id]['huella'])) {
            return $guardadas[$id]['huella'];
        }

        $archivo = __DIR__ . '/../../Publico/' . $foto;
        $huella = $foto !== '' && is_file($archivo) ? huellafoto::calcular($archivo) : null;
        if ($huella !== null) {
            $this->huellas->guardar($id, $foto, $huella);
        }

        return $huella;
    }

    // También la usa la valoración de ramos
    public function validarfoto($foto)
    {
        if (!is_array($foto) || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Elija la foto del arreglo que quiere la clienta.';
        }
        if ($foto['error'] !== UPLOAD_ERR_OK) {
            return 'No se pudo subir la foto. Verifique que pese menos de 5 MB.';
        }
        if ($foto['size'] > $this->tamanomaximo) {
            return 'La foto no puede pesar más de 5 MB.';
        }
        $imagen = @getimagesize($foto['tmp_name']);
        if (!$imagen || !in_array($imagen['mime'], $this->tiposfoto, true)) {
            return 'La foto debe ser una imagen JPG, PNG o WEBP.';
        }

        return '';
    }

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return ['exito' => $exito, 'mensaje' => $mensaje, 'datos' => $datos];
    }
}
