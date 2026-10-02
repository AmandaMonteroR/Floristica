<?php

class ubicacionrepositorio
{
    private $archivo;
    private $datos = null;

    public function __construct()
    {
        $this->archivo = __DIR__ . '/../../BaseDatos/DatosIniciales/ubicaciones.txt';
    }

    private function cargar()
    {
        if ($this->datos !== null) {
            return $this->datos;
        }

        if (!is_readable($this->archivo)) {
            throw new RuntimeException('No se encontró el archivo de ubicaciones.');
        }

        $this->datos = [];
        foreach (file($this->archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $linea) {
            $linea = trim(preg_replace('/^\xEF\xBB\xBF/', '', $linea));

            if ($linea === '' || $linea[0] === '#') {
                continue;
            }

            $partes = array_map('trim', explode('|', $linea));
            if (count($partes) !== 3) {
                continue;
            }

            $this->datos[$partes[0]][$partes[1]][] = $partes[2];
        }

        return $this->datos;
    }

    public function listar()
    {
        $provincias = [];
        foreach ($this->cargar() as $provincia => $cantones) {
            $listacantones = [];
            foreach ($cantones as $canton => $distritos) {
                $listacantones[] = ['nombre' => $canton, 'distritos' => $distritos];
            }
            $provincias[] = ['nombre' => $provincia, 'cantones' => $listacantones];
        }

        return $provincias;
    }

    public function existeprovincia($provincia)
    {
        return isset($this->cargar()[$provincia]);
    }

    public function existecanton($provincia, $canton)
    {
        return isset($this->cargar()[$provincia][$canton]);
    }

    public function existedistrito($provincia, $canton, $distrito)
    {
        $datos = $this->cargar();

        return isset($datos[$provincia][$canton]) && in_array($distrito, $datos[$provincia][$canton], true);
    }
}