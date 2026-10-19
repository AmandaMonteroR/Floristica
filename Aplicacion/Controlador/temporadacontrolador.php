<?php
require_once __DIR__ . '/../Repositorio/temporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/preciotemporadarepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Temporadas con precios especiales. Solo el propietario las maneja.
// "Activar precios" deja vigente una temporada (solo una a la vez) hasta que se vuelva a los precios normales.
class temporadacontrolador
{
    private $repositorio;
    private $precios;
    private $historico;

    public function __construct()
    {
        $this->repositorio = new temporadarepositorio();
        $this->precios = new preciotemporadarepositorio();
        $this->historico = new historicocontrolador();
    }

    public function listar($texto = '', $estado = null)
    {
        $productos = $this->precios->contarportemporada('inventario');

        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $temporada) {
            $lista[] = $this->convertirarreglo($temporada, $productos);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $temporada = $this->repositorio->obtenerporid((int) $id);

        if (!$temporada) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($temporada, $this->precios->contarportemporada('inventario')));
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $temporada = new Temporada($datos);

        $id = $this->entransaccion(function () use ($temporada, $usuario) {
            $id = $this->repositorio->insertar($temporada);
            $this->historico->registrar('temporada', $id, [], $this->datoshistorico($temporada), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Temporada registrada. Ahora ponga en el inventario lo que cobra el proveedor por las flores en esta temporada.', ['tbtemporadaid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbtemporadaid'] ?? 0);
        $temporada = $this->repositorio->obtenerporid($id);

        if (!$temporada) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($temporada);
        $temporada->setTbtemporadanombre($datos['tbtemporadanombre']);
        $temporada->setTbtemporadadescripcion($datos['tbtemporadadescripcion']);

        $this->entransaccion(function () use ($temporada, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($temporada);
            $this->historico->registrar('temporada', $id, $anteriores, $this->datoshistorico($temporada), $usuario['id']);
        });

        return $this->respuesta(true, 'Temporada actualizada correctamente.');
    }

    // Empieza a usar los precios de esta temporada. Si había otra vigente, esa se desactiva.
    public function activar($id, $usuario)
    {
        $id = (int) $id;
        $temporada = $this->repositorio->obtenerporid($id);

        if (!$temporada) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        if (!$temporada->estaActivo()) {
            return $this->respuesta(false, 'Reactive la temporada antes de usar sus precios.');
        }

        if ($temporada->estaVigente()) {
            return $this->respuesta(false, 'Los precios de ' . $temporada->getTbtemporadanombre() . ' ya se están usando.');
        }

        $anterior = $this->repositorio->obtenervigente();

        $this->entransaccion(function () use ($id, $anterior, $usuario) {
            if ($anterior) {
                $this->historico->registrar('temporada', $anterior->getTbtemporadaid(), ['vigente' => 1], ['vigente' => 0], $usuario['id']);
            }
            $this->repositorio->hacervigente($id);
            $this->historico->registrar('temporada', $id, ['vigente' => 0], ['vigente' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Desde ahora se usan los precios de ' . $temporada->getTbtemporadanombre()
            . ($anterior ? ' (se dejaron de usar los de ' . $anterior->getTbtemporadanombre() . ')' : '')
            . '. Los arreglos suben solos según sus flores; los productos sin precio del proveedor para esta temporada se cobran al precio normal.');
    }

    // Vuelve a los precios normales (temporada baja)
    public function desactivar($usuario)
    {
        $vigente = $this->repositorio->obtenervigente();

        if (!$vigente) {
            return $this->respuesta(false, 'Ya se están usando los precios normales.');
        }

        $this->entransaccion(function () use ($vigente, $usuario) {
            $this->repositorio->hacervigente(null);
            $this->historico->registrar('temporada', $vigente->getTbtemporadaid(), ['vigente' => 1], ['vigente' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Se dejaron de usar los precios de ' . $vigente->getTbtemporadanombre() . '. Desde ahora se usan los precios normales.');
    }

    public function inactivar($id, $usuario)
    {
        $id = (int) $id;
        $temporada = $this->repositorio->obtenerporid($id);

        if (!$temporada) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        if (!$temporada->estaActivo()) {
            return $this->respuesta(false, 'La temporada ya está inactiva.');
        }

        if ($temporada->estaVigente()) {
            return $this->respuesta(false, 'No se puede inactivar: sus precios se están usando. Primero vuelva a los precios normales.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('temporada', $id, ['estado' => 1], ['estado' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Temporada inactivada correctamente.');
    }

    public function reactivar($id, $usuario)
    {
        $id = (int) $id;
        $temporada = $this->repositorio->obtenerporid($id);

        if (!$temporada) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        if ($temporada->estaActivo()) {
            return $this->respuesta(false, 'La temporada ya está activa.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('temporada', $id, ['estado' => 0], ['estado' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Temporada reactivada correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'La temporada no existe.');
        }

        $etiquetas = [
            'nombre'  => 'Nombre',
            'vigente' => 'Precios en uso',
            'estado'  => 'Estado',
        ];

        $formatear = function ($campo, $valor) {
            switch ($campo) {
                case 'vigente':
                    return (int) $valor === 1 ? 'Sí (precios activados)' : 'No (precios normales)';
                case 'estado':
                    return historicocontrolador::estado($valor);
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('temporada', $id, $etiquetas, $formatear));
    }

    private function datoshistorico(Temporada $temporada)
    {
        return [
            'nombre'  => $temporada->getTbtemporadanombre(),
            'vigente' => (int) $temporada->getTbtemporadavigente(),
            'estado'  => (int) $temporada->getTbtemporadaestado(),
        ];
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
        $limpios = [];
        foreach (['tbtemporadanombre', 'tbtemporadadescripcion'] as $campo) {
            $limpios[$campo] = trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        }

        return $limpios;
    }

    private function validar($datos, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbtemporadanombre'] === '') {
            $errores['tbtemporadanombre'] = 'El nombre de la temporada es obligatorio.';
        } elseif (mb_strlen($datos['tbtemporadanombre']) > 100) {
            $errores['tbtemporadanombre'] = 'El nombre no puede superar 100 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbtemporadanombre'])) {
            $errores['tbtemporadanombre'] = 'El nombre debe contener al menos una letra.';
        } elseif (!preg_match("/^[\p{L}\d\s.,'()-]+$/u", $datos['tbtemporadanombre'])) {
            $errores['tbtemporadanombre'] = 'El nombre solo puede contener letras, números y los signos . , - \' ( ).';
        } elseif ($this->repositorio->existenombre($datos['tbtemporadanombre'], $idexcluir)) {
            $errores['tbtemporadanombre'] = 'Ya existe una temporada con ese nombre.';
        }

        if (mb_strlen($datos['tbtemporadadescripcion']) > 200) {
            $errores['tbtemporadadescripcion'] = 'La descripción no puede superar 200 caracteres.';
        }

        return $errores;
    }

    private function convertirarreglo(Temporada $temporada, $productos)
    {
        $id = (int) $temporada->getTbtemporadaid();

        return [
            'tbtemporadaid'          => $id,
            'tbtemporadanombre'      => $temporada->getTbtemporadanombre(),
            'tbtemporadadescripcion' => (string) $temporada->getTbtemporadadescripcion(),
            'tbtemporadavigente'     => (int) $temporada->getTbtemporadavigente(),
            'tbtemporadaestado'      => (int) $temporada->getTbtemporadaestado(),
            'productosconprecio'     => $productos[$id] ?? 0,
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
