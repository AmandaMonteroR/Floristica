<?php
require_once __DIR__ . '/../Repositorio/temporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';
require_once __DIR__ . '/historicocontrolador.php';

// Temporada alta: hay una sola y solo el propietario la enciende o la apaga.
// Encendida, las flores se cobran con lo que cobra el proveedor en temporada alta
// y los arreglos suben solos según sus flores. Apagada, se usan los precios normales.
class temporadacontrolador
{
    private $repositorio;
    private $inventario;
    private $precios;
    private $historico;

    public function __construct()
    {
        $this->repositorio = new temporadarepositorio();
        $this->inventario = new inventariorepositorio();
        $this->precios = new preciotemporadacontrolador();
        $this->historico = new historicocontrolador();
    }

    // Si está encendida y lo que cobra el proveedor por cada flor, normal y en temporada alta
    public function estado()
    {
        $alta = $this->precios->alta();
        if (!$alta) {
            return $this->respuesta(false, 'No está registrada la temporada alta.');
        }

        $idalta = (int) $alta->getTbtemporadaid();
        $calculados = $this->precios->productos($idalta);
        $paquetes = (new preciotemporadarepositorio())->listarportemporada('inventario', $idalta);

        $flores = [];
        foreach ($this->inventario->listar('', 'Flor', 1) as $flor) {
            $id = (int) $flor->getTbinventarioid();
            $flores[] = [
                'id'            => $id,
                'nombre'        => $flor->getTbinventarionombre(),
                'unidades'      => (int) $flor->getTbinventariounidadespaquete(),
                'paquetenormal' => (float) $flor->getTbinventariopreciopaquete(),
                'paquetealto'   => isset($paquetes[$id]) ? (float) $paquetes[$id] : null,
                'ventanormal'   => (float) $flor->getTbinventarioprecioventa(),
                'ventaalta'     => $calculados[$id]['venta'] ?? (float) $flor->getTbinventarioprecioventa(),
            ];
        }

        return $this->respuesta(true, '', [
            'tbtemporadaid'     => $idalta,
            'tbtemporadanombre' => $alta->getTbtemporadanombre(),
            'encendida'         => $alta->estaVigente(),
            'flores'            => $flores,
            'conprecio'         => count(array_filter($flores, function ($flor) { return $flor['paquetealto'] !== null; })),
        ]);
    }

    public function activar($usuario)
    {
        $alta = $this->precios->alta();
        if (!$alta) {
            return $this->respuesta(false, 'No está registrada la temporada alta.');
        }
        if ($alta->estaVigente()) {
            return $this->respuesta(false, 'La temporada alta ya está encendida.');
        }

        $id = (int) $alta->getTbtemporadaid();
        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->hacervigente($id);
            $this->historico->registrar('temporada', $id, ['vigente' => 0], ['vigente' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Temporada alta encendida. Las flores se cobran con el precio del proveedor en temporada alta '
            . 'y los arreglos suben solos según sus flores.');
    }

    public function desactivar($usuario)
    {
        $alta = $this->precios->alta();
        if (!$alta || !$alta->estaVigente()) {
            return $this->respuesta(false, 'La temporada alta ya está apagada.');
        }

        $id = (int) $alta->getTbtemporadaid();
        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->hacervigente(null);
            $this->historico->registrar('temporada', $id, ['vigente' => 1], ['vigente' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Temporada alta apagada. Desde ahora se usan los precios normales.');
    }

    // Guarda de una vez lo que cobra el proveedor por el paquete de cada flor en temporada alta.
    // precios[idflor] = monto; vacío quita el precio (esa flor se cobra al precio normal).
    public function guardarprecios($datos, $usuario)
    {
        $idalta = $this->precios->idalta();
        if (!$idalta) {
            return $this->respuesta(false, 'No está registrada la temporada alta.');
        }

        $flores = [];
        foreach ($this->inventario->listar('', 'Flor', 1) as $flor) {
            $flores[(int) $flor->getTbinventarioid()] = $flor->getTbinventarionombre();
        }

        $errores = [];
        $validos = [];
        foreach ((array) ($datos['precios'] ?? []) as $id => $precio) {
            $precio = str_replace(',', '.', trim((string) $precio));
            if (!isset($flores[(int) $id])) {
                continue;
            }

            $error = $this->precios->validar([$idalta => $precio]);
            if ($error !== '') {
                $errores[(int) $id] = $flores[(int) $id] . ': ' . $error;
            } else {
                $validos[(int) $id] = $precio;
            }
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los precios marcados.', ['errores' => $errores]);
        }

        $this->entransaccion(function () use ($validos, $idalta, $usuario) {
            foreach ($validos as $id => $precio) {
                $this->precios->guardar('inventario', $id, [$idalta => $precio], $usuario['id']);
            }
        });

        return $this->respuesta(true, 'Precios de temporada alta guardados.');
    }

    // Cuándo se encendió y se apagó
    public function historial()
    {
        $idalta = $this->precios->idalta();
        if (!$idalta) {
            return $this->respuesta(false, 'No está registrada la temporada alta.');
        }

        $formatear = function ($campo, $valor) {
            return (int) $valor === 1 ? 'Encendida' : 'Apagada';
        };

        $lista = $this->historico->listar('temporada', $idalta, ['vigente' => 'Temporada alta'], $formatear);

        return $this->respuesta(true, '', array_values(array_filter($lista, function ($cambio) {
            return $cambio['campo'] === 'vigente';
        })));
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

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return [
            'exito'   => $exito,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ];
    }
}
