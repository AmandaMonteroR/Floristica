<?php
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';

class arreglofloralcontrolador
{
    private $repositorio;
    private $inventario;
    private $historico;
    private $temporadas;
    private $carpetafotos;
    private $tiposfoto = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private $tamanomaximo = 2097152;

    public function __construct()
    {
        $this->repositorio = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
        $this->historico = new historicocontrolador();
        $this->temporadas = new preciotemporadacontrolador();
        $this->carpetafotos = __DIR__ . '/../../Publico/imagenes/arreglos/';
    }

    public function productos()
    {
        $lista = [];
        foreach ($this->inventario->listar('', '', 1) as $producto) {
            if ($producto->getTbinventariocategoria() === 'Spray') {
                continue;
            }
            $lista[] = [
                'tbinventarioid'           => $producto->getTbinventarioid(),
                'tbinventarionombre'       => $producto->getTbinventarionombre(),
                'tbinventariocategoria'    => $producto->getTbinventariocategoria(),
                'tbinventariopreciounidad' => (float) $producto->getTbinventariopreciounidad(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function listar($texto = '', $estado = null)
    {
        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $arreglo) {
            $lista[] = $this->convertirarreglo($arreglo);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $arreglo = $this->repositorio->obtenerporid((int) $id);

        if (!$arreglo) {
            return $this->respuesta(false, 'El arreglo floral no existe.');
        }

        $datos = $this->convertirarreglo($arreglo);
        $datos['preciostemporada'] = $this->preciosportemporada($arreglo);
        $datos['detalles'] = [];

        foreach ($this->repositorio->listardetalles($arreglo->getTbarreglofloralid()) as $detalle) {
            $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
            $datos['detalles'][] = [
                'tbinventarioid'                 => (int) $detalle->getTbinventarioid(),
                'tbinventarionombre'             => $producto ? $producto->getTbinventarionombre() : '',
                'tbinventariocategoria'          => $producto ? $producto->getTbinventariocategoria() : '',
                'tbinventariopreciounidad'       => $producto ? (float) $producto->getTbinventariopreciounidad() : 0,
                'tbarreglofloralinsumocantidad' => (int) $detalle->getTbarreglofloralinsumocantidad(),
            ];
        }

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $archivos, $usuario)
    {
        $detalles = $this->limpiardetalles($datos['detalles'] ?? []);
        $datos = $this->limpiar($datos);
        $foto = $archivos['tbarreglofloralfoto'] ?? null;
        $errores = $this->validar($datos, $detalles, $foto, true);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbarreglofloralfoto'] = $this->guardarfoto($foto);
        $arreglo = new Arreglofloral($datos);

        try {
            $id = $this->entransaccion(function () use ($arreglo, $detalles, $usuario) {
                $id = $this->repositorio->insertar($arreglo);
                $this->guardardetalles($id, $detalles);
                $this->historico->registrar('arreglofloral', $id, [], $this->datoshistorico($arreglo), $usuario['id']);

                return $id;
            });
        } catch (Throwable $excepcion) {
            $this->eliminarfoto($datos['tbarreglofloralfoto']);
            throw $excepcion;
        }

        return $this->respuesta(true, 'Arreglo floral registrado correctamente.', ['tbarreglofloralid' => $id]);
    }

    public function actualizar($datos, $archivos, $usuario)
    {
        $id = (int) ($datos['tbarreglofloralid'] ?? 0);
        $arreglo = $this->repositorio->obtenerporid($id);

        if (!$arreglo) {
            return $this->respuesta(false, 'El arreglo floral no existe.');
        }

        $detalles = $this->limpiardetalles($datos['detalles'] ?? []);
        $datos = $this->limpiar($datos);
        $foto = $archivos['tbarreglofloralfoto'] ?? null;
        $errores = $this->validar($datos, $detalles, $foto, false, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($arreglo);
        $fotoanterior = $arreglo->getTbarreglofloralfoto();
        $fotonueva = $this->haysubida($foto) ? $this->guardarfoto($foto) : null;

        $arreglo->setTbarreglofloralnombre($datos['tbarreglofloralnombre']);
        $arreglo->setTbarreglofloraldescripcion($datos['tbarreglofloraldescripcion']);
        $arreglo->setTbarreglofloralprecio($datos['tbarreglofloralprecio']);

        if ($fotonueva !== null) {
            $arreglo->setTbarreglofloralfoto($fotonueva);
        }

        try {
            $this->entransaccion(function () use ($arreglo, $detalles, $id, $anteriores, $usuario) {
                $this->repositorio->actualizar($arreglo);
                $this->historico->registrar('arreglofloral', $id, $anteriores, $this->datoshistorico($arreglo), $usuario['id']);
                $this->repositorio->eliminardetalles($id);
                $this->guardardetalles($id, $detalles);
            });
        } catch (Throwable $excepcion) {
            if ($fotonueva !== null) {
                $this->eliminarfoto($fotonueva);
            }
            throw $excepcion;
        }

        if ($fotonueva !== null) {
            $this->eliminarfoto($fotoanterior);
        }

        return $this->respuesta(true, 'Arreglo floral actualizado correctamente.');
    }

    public function inactivar($id, $usuario)
    {
        $id = (int) $id;
        $arreglo = $this->repositorio->obtenerporid($id);

        if (!$arreglo) {
            return $this->respuesta(false, 'El arreglo floral no existe.');
        }

        if (!$arreglo->estaActivo()) {
            return $this->respuesta(false, 'El arreglo floral ya está inactivo.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('arreglofloral', $id, ['estado' => 1], ['estado' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Arreglo floral inactivado correctamente.');
    }

    public function reactivar($id, $usuario)
    {
        $id = (int) $id;
        $arreglo = $this->repositorio->obtenerporid($id);

        if (!$arreglo) {
            return $this->respuesta(false, 'El arreglo floral no existe.');
        }

        if ($arreglo->estaActivo()) {
            return $this->respuesta(false, 'El arreglo floral ya está activo.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('arreglofloral', $id, ['estado' => 0], ['estado' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Arreglo floral reactivado correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El arreglo floral no existe.');
        }

        $etiquetas = [
            'nombre' => 'Nombre',
            'precio' => 'Precio de venta',
            'estado' => 'Estado',
        ];

        $formatear = function ($campo, $valor) {
            switch ($campo) {
                case 'precio':
                    return historicocontrolador::monto($valor);
                case 'estado':
                    return historicocontrolador::estado($valor);
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('arreglofloral', $id, $etiquetas, $formatear));
    }

    // Precio del arreglo en cada temporada activa, calculado con lo que cuestan sus productos en ella.
    // Solo aparecen las temporadas en las que el precio cambia.
    private function preciosportemporada(Arreglofloral $arreglo)
    {
        $lista = [];
        foreach ((new temporadarepositorio())->listar('', 1) as $temporada) {
            $calculo = $this->temporadas->arreglo($arreglo, (int) $temporada->getTbtemporadaid());
            if ($calculo['contemporada']) {
                $lista[] = [
                    'tbtemporadanombre' => $temporada->getTbtemporadanombre(),
                    'vigente'           => $temporada->estaVigente(),
                    'precio'            => $calculo['precio'],
                    'costo'             => $calculo['costo'],
                ];
            }
        }

        return $lista;
    }

    // Datos del arreglo que llevan histórico, tal como se guardan
    private function datoshistorico(Arreglofloral $arreglo)
    {
        return [
            'nombre' => $arreglo->getTbarreglofloralnombre(),
            'precio' => number_format((float) $arreglo->getTbarreglofloralprecio(), 2, '.', ''),
            'estado' => (int) $arreglo->getTbarreglofloralestado(),
        ];
    }

    private function guardardetalles($idarreglo, $detalles)
    {
        foreach ($detalles as $detalle) {
            $this->repositorio->insertardetalle(new Arreglofloralinsumo([
                'tbarreglofloralid'              => $idarreglo,
                'tbinventarioid'                 => (int) $detalle['tbinventarioid'],
                'tbarreglofloralinsumocantidad' => (int) $detalle['tbarreglofloralinsumocantidad'],
            ]));
        }
    }

    private function costo($idarreglo)
    {
        $costo = 0;
        foreach ($this->repositorio->listardetalles($idarreglo) as $detalle) {
            $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
            if ($producto) {
                $costo += (int) $detalle->getTbarreglofloralinsumocantidad() * (float) $producto->getTbinventariopreciounidad();
            }
        }

        return round($costo, 2);
    }

    private function haysubida($foto)
    {
        return is_array($foto) && ($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function guardarfoto($foto)
    {
        if (!is_dir($this->carpetafotos)) {
            mkdir($this->carpetafotos, 0775, true);
        }

        $tipo = getimagesize($foto['tmp_name'])['mime'];
        $nombre = 'arreglo_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $this->tiposfoto[$tipo];

        if (!move_uploaded_file($foto['tmp_name'], $this->carpetafotos . $nombre)) {
            throw new RuntimeException('No se pudo guardar la foto del arreglo.');
        }

        return 'imagenes/arreglos/' . $nombre;
    }

    private function eliminarfoto($ruta)
    {
        $archivo = __DIR__ . '/../../Publico/' . $ruta;

        if ($ruta !== '' && strpos($ruta, 'imagenes/arreglos/') === 0 && is_file($archivo)) {
            unlink($archivo);
        }
    }

    private function entransaccion($operacion)
    {
        $conexion = Basedatos::conectar();
        $conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $conexion->commit();
            return $resultado;
        } catch (Throwable $excepcion) {
            $conexion->rollBack();
            throw $excepcion;
        }
    }

    private function limpiar($datos)
    {
        $campos = [
            'tbarreglofloralnombre',
            'tbarreglofloraldescripcion',
            'tbarreglofloralprecio',
        ];

        $limpios = [];
        foreach ($campos as $campo) {
            $limpios[$campo] = trim((string) ($datos[$campo] ?? ''));
        }

        $limpios['tbarreglofloralprecio'] = str_replace(',', '.', $limpios['tbarreglofloralprecio']);

        return $limpios;
    }

    private function limpiardetalles($detalles)
    {
        if (!is_array($detalles)) {
            return [];
        }

        $limpios = [];
        foreach ($detalles as $detalle) {
            if (!is_array($detalle)) {
                continue;
            }

            $limpios[] = [
                'tbinventarioid'                 => trim((string) ($detalle['tbinventarioid'] ?? '')),
                'tbarreglofloralinsumocantidad' => trim((string) ($detalle['tbarreglofloralinsumocantidad'] ?? '')),
            ];
        }

        return $limpios;
    }

    private function validar($datos, $detalles, $foto, $fotoobligatoria, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbarreglofloralnombre'] === '') {
            $errores['tbarreglofloralnombre'] = 'El nombre del arreglo es obligatorio.';
        } elseif (mb_strlen($datos['tbarreglofloralnombre']) > 100) {
            $errores['tbarreglofloralnombre'] = 'El nombre del arreglo no puede superar 100 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbarreglofloralnombre'])) {
            $errores['tbarreglofloralnombre'] = 'El nombre del arreglo debe contener al menos una letra.';
        } elseif ($this->repositorio->existenombre($datos['tbarreglofloralnombre'], $idexcluir)) {
            $errores['tbarreglofloralnombre'] = 'Ya existe un arreglo floral con ese nombre.';
        }

        if (mb_strlen($datos['tbarreglofloraldescripcion']) > 255) {
            $errores['tbarreglofloraldescripcion'] = 'La descripción no puede superar 255 caracteres.';
        }

        if ($datos['tbarreglofloralprecio'] === '') {
            $errores['tbarreglofloralprecio'] = 'El precio es obligatorio.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $datos['tbarreglofloralprecio'])
            || (float) $datos['tbarreglofloralprecio'] <= 0) {
            $errores['tbarreglofloralprecio'] = 'El precio debe ser un monto mayor que 0, con máximo 2 decimales.';
        }

        if (!$this->haysubida($foto)) {
            if ($fotoobligatoria) {
                $errores['tbarreglofloralfoto'] = 'La foto del arreglo es obligatoria.';
            }
        } elseif ($foto['error'] !== UPLOAD_ERR_OK) {
            $errores['tbarreglofloralfoto'] = 'No se pudo subir la foto. Verifique que pese menos de 2 MB.';
        } elseif ($foto['size'] > $this->tamanomaximo) {
            $errores['tbarreglofloralfoto'] = 'La foto no puede pesar más de 2 MB.';
        } else {
            $imagen = @getimagesize($foto['tmp_name']);
            if (!$imagen || !isset($this->tiposfoto[$imagen['mime']])) {
                $errores['tbarreglofloralfoto'] = 'La foto debe ser una imagen JPG, PNG o WEBP.';
            }
        }

        if (count($detalles) === 0) {
            $errores['detalles'] = 'Agregue al menos un producto del inventario.';
            return $errores;
        }

        $usados = [];
        foreach ($detalles as $indice => $detalle) {
            $linea = 'Línea ' . ($indice + 1) . ': ';
            $producto = ctype_digit($detalle['tbinventarioid'])
                ? $this->inventario->obtenerporid((int) $detalle['tbinventarioid'])
                : null;

            if ($detalle['tbinventarioid'] === '') {
                $errores['detalles'] = $linea . 'seleccione el producto.';
            } elseif (!$producto) {
                $errores['detalles'] = $linea . 'el producto no existe.';
            } elseif (!$producto->estaActivo()) {
                $errores['detalles'] = $linea . 'el producto está inactivo.';
            } elseif ($producto->getTbinventariocategoria() === 'Spray') {
                $errores['detalles'] = $linea . 'el spray no va dentro del arreglo; la pintura se cobra por flor pintada en la solicitud.';
            } elseif (in_array($detalle['tbinventarioid'], $usados, true)) {
                $errores['detalles'] = $linea . 'el producto ya está en otra línea.';
            } elseif (!ctype_digit($detalle['tbarreglofloralinsumocantidad'])
                || (int) $detalle['tbarreglofloralinsumocantidad'] < 1
                || (int) $detalle['tbarreglofloralinsumocantidad'] > 999) {
                $errores['detalles'] = $linea . 'la cantidad debe ser un número entero entre 1 y 999 unidades.';
            }

            if (isset($errores['detalles'])) {
                break;
            }

            $usados[] = $detalle['tbinventarioid'];
        }

        return $errores;
    }

    private function convertirarreglo(Arreglofloral $arreglo)
    {
        // Precio y costo de ahora: si hay una temporada en uso, se calculan con lo que cuestan sus flores en ella
        $actual = $this->temporadas->arreglo($arreglo);
        $vigente = $this->temporadas->datosvigente();

        return [
            'precioactual'               => $actual['precio'],
            'costoactual'                => $actual['costo'],
            'temporadaprecio'            => $actual['contemporada'] ? $vigente['tbtemporadanombre'] : '',
            'tbarreglofloralid'          => $arreglo->getTbarreglofloralid(),
            'tbarreglofloralnombre'      => $arreglo->getTbarreglofloralnombre(),
            'tbarreglofloraldescripcion' => $arreglo->getTbarreglofloraldescripcion(),
            'tbarreglofloralfoto'        => $arreglo->getTbarreglofloralfoto(),
            'tbarreglofloralprecio'      => (float) $arreglo->getTbarreglofloralprecio(),
            'tbarreglofloralestado'      => (int) $arreglo->getTbarreglofloralestado(),
            'costo'                      => $this->costo($arreglo->getTbarreglofloralid()),
        ];
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