<?php
require_once __DIR__ . '/../Repositorio/zonaentregarepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Zonas del servicio express. Solo el propietario las crea y les cambia el precio.
class zonaentregacontrolador
{
    private $repositorio;
    private $historico;

    public function __construct()
    {
        $this->repositorio = new zonaentregarepositorio();
        $this->historico = new historicocontrolador();
    }

    public function listar($texto = '', $estado = null)
    {
        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $zona) {
            $lista[] = $this->convertirarreglo($zona);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $zona = $this->repositorio->obtenerporid((int) $id);

        if (!$zona) {
            return $this->respuesta(false, 'La zona de entrega no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($zona));
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $zona = new Zonaentrega($datos);

        $id = $this->entransaccion(function () use ($zona, $usuario) {
            $id = $this->repositorio->insertar($zona);
            $this->historico->registrar('zonaentrega', $id, [], $this->datoshistorico($zona), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Zona de entrega registrada correctamente.', ['tbzonaentregaid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbzonaentregaid'] ?? 0);
        $zona = $this->repositorio->obtenerporid($id);

        if (!$zona) {
            return $this->respuesta(false, 'La zona de entrega no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($zona);

        $zona->setTbzonaentreganombre($datos['tbzonaentreganombre']);
        $zona->setTbzonaentregadescripcion($datos['tbzonaentregadescripcion']);
        $zona->setTbzonaentregaprecio($datos['tbzonaentregaprecio']);

        $this->entransaccion(function () use ($zona, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($zona);
            $this->historico->registrar('zonaentrega', $id, $anteriores, $this->datoshistorico($zona), $usuario['id']);
        });

        return $this->respuesta(true, 'Zona de entrega actualizada correctamente.');
    }

    public function inactivar($id, $usuario)
    {
        $id = (int) $id;
        $zona = $this->repositorio->obtenerporid($id);

        if (!$zona) {
            return $this->respuesta(false, 'La zona de entrega no existe.');
        }

        if (!$zona->estaActivo()) {
            return $this->respuesta(false, 'La zona de entrega ya está inactiva.');
        }

        $abiertas = $this->repositorio->solicitudesabiertas($id);
        if ($abiertas > 0) {
            return $this->respuesta(false, 'No se puede inactivar: hay ' . $abiertas
                . ($abiertas === 1 ? ' solicitud de pedido de arreglo pendiente o en proceso' : ' solicitudes de pedido de arreglo pendientes o en proceso')
                . ' con entrega a esta zona.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('zonaentrega', $id, ['estado' => 1], ['estado' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Zona de entrega inactivada correctamente.');
    }

    public function reactivar($id, $usuario)
    {
        $id = (int) $id;
        $zona = $this->repositorio->obtenerporid($id);

        if (!$zona) {
            return $this->respuesta(false, 'La zona de entrega no existe.');
        }

        if ($zona->estaActivo()) {
            return $this->respuesta(false, 'La zona de entrega ya está activa.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('zonaentrega', $id, ['estado' => 0], ['estado' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Zona de entrega reactivada correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'La zona de entrega no existe.');
        }

        $etiquetas = [
            'nombre' => 'Nombre',
            'precio' => 'Precio del express',
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

        return $this->respuesta(true, '', $this->historico->listar('zonaentrega', $id, $etiquetas, $formatear));
    }

    private function datoshistorico(Zonaentrega $zona)
    {
        return [
            'nombre' => $zona->getTbzonaentreganombre(),
            'precio' => number_format((float) $zona->getTbzonaentregaprecio(), 2, '.', ''),
            'estado' => (int) $zona->getTbzonaentregaestado(),
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
        foreach (['tbzonaentreganombre', 'tbzonaentregadescripcion', 'tbzonaentregaprecio'] as $campo) {
            $limpios[$campo] = trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        }

        $limpios['tbzonaentregaprecio'] = str_replace(',', '.', $limpios['tbzonaentregaprecio']);

        return $limpios;
    }

    private function validar($datos, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbzonaentreganombre'] === '') {
            $errores['tbzonaentreganombre'] = 'El nombre de la zona es obligatorio.';
        } elseif (mb_strlen($datos['tbzonaentreganombre']) > 100) {
            $errores['tbzonaentreganombre'] = 'El nombre de la zona no puede superar 100 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbzonaentreganombre'])) {
            $errores['tbzonaentreganombre'] = 'El nombre de la zona debe contener al menos una letra.';
        } elseif (!preg_match("/^[\p{L}\d\s.,'()-]+$/u", $datos['tbzonaentreganombre'])) {
            $errores['tbzonaentreganombre'] = 'El nombre de la zona solo puede contener letras, números y los signos . , - \' ( ).';
        } elseif ($this->repositorio->existenombre($datos['tbzonaentreganombre'], $idexcluir)) {
            $errores['tbzonaentreganombre'] = 'Ya existe una zona de entrega con ese nombre.';
        }

        if (mb_strlen($datos['tbzonaentregadescripcion']) > 200) {
            $errores['tbzonaentregadescripcion'] = 'La descripción no puede superar 200 caracteres.';
        }

        if ($datos['tbzonaentregaprecio'] === '') {
            $errores['tbzonaentregaprecio'] = 'El precio del express es obligatorio.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $datos['tbzonaentregaprecio'])
            || (float) $datos['tbzonaentregaprecio'] <= 0) {
            $errores['tbzonaentregaprecio'] = 'El precio debe ser un monto mayor que 0, con máximo 2 decimales.';
        }

        return $errores;
    }

    private function convertirarreglo(Zonaentrega $zona)
    {
        return [
            'tbzonaentregaid'          => (int) $zona->getTbzonaentregaid(),
            'tbzonaentreganombre'      => $zona->getTbzonaentreganombre(),
            'tbzonaentregadescripcion' => (string) $zona->getTbzonaentregadescripcion(),
            'tbzonaentregaprecio'      => (float) $zona->getTbzonaentregaprecio(),
            'tbzonaentregaestado'      => (int) $zona->getTbzonaentregaestado(),
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
