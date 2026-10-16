<?php
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Clientes que hacen solicitudes de pedido de arreglo. Los manejan el propietario y los empleados.
class clientecontrolador
{
    private $repositorio;
    private $historico;

    public function __construct()
    {
        $this->repositorio = new clienterepositorio();
        $this->historico = new historicocontrolador();
    }

    public function listar($texto = '', $estado = null)
    {
        $conteos = $this->repositorio->contarsolicitudes();

        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $cliente) {
            $lista[] = $this->convertirarreglo($cliente, $conteos);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $cliente = $this->repositorio->obtenerporid((int) $id);

        if (!$cliente) {
            return $this->respuesta(false, 'El cliente no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($cliente, $this->repositorio->contarsolicitudes()));
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $cliente = new Cliente($datos);

        $id = $this->entransaccion(function () use ($cliente, $usuario) {
            $id = $this->repositorio->insertar($cliente);
            $this->historico->registrar('cliente', $id, [], $this->datoshistorico($cliente), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Cliente registrado correctamente.', ['tbclienteid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbclienteid'] ?? 0);
        $cliente = $this->repositorio->obtenerporid($id);

        if (!$cliente) {
            return $this->respuesta(false, 'El cliente no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($cliente);

        $cliente->setTbclientenombre($datos['tbclientenombre']);
        $cliente->setTbclientetelefono($datos['tbclientetelefono']);
        $cliente->setTbclientecorreo($datos['tbclientecorreo']);
        $cliente->setTbclientedireccion($datos['tbclientedireccion']);

        $this->entransaccion(function () use ($cliente, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($cliente);
            $this->historico->registrar('cliente', $id, $anteriores, $this->datoshistorico($cliente), $usuario['id']);
        });

        return $this->respuesta(true, 'Cliente actualizado correctamente.');
    }

    public function inactivar($id, $usuario)
    {
        $id = (int) $id;
        $cliente = $this->repositorio->obtenerporid($id);

        if (!$cliente) {
            return $this->respuesta(false, 'El cliente no existe.');
        }

        if (!$cliente->estaActivo()) {
            return $this->respuesta(false, 'El cliente ya está inactivo.');
        }

        $abiertas = $this->repositorio->contarsolicitudes()[$id]['abiertas'] ?? 0;
        if ($abiertas > 0) {
            return $this->respuesta(false, 'No se puede inactivar: ' . $cliente->getTbclientenombre() . ' tiene ' . $abiertas
                . ($abiertas === 1 ? ' solicitud de pedido de arreglo pendiente o en proceso.' : ' solicitudes de pedido de arreglo pendientes o en proceso.'));
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('cliente', $id, ['estado' => 1], ['estado' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Cliente inactivado correctamente.');
    }

    public function reactivar($id, $usuario)
    {
        $id = (int) $id;
        $cliente = $this->repositorio->obtenerporid($id);

        if (!$cliente) {
            return $this->respuesta(false, 'El cliente no existe.');
        }

        if ($cliente->estaActivo()) {
            return $this->respuesta(false, 'El cliente ya está activo.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('cliente', $id, ['estado' => 0], ['estado' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Cliente reactivado correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El cliente no existe.');
        }

        $etiquetas = [
            'nombre'    => 'Nombre',
            'telefono'  => 'Teléfono',
            'direccion' => 'Dirección',
            'estado'    => 'Estado',
        ];

        $formatear = function ($campo, $valor) {
            if ($campo === 'estado') {
                return historicocontrolador::estado($valor);
            }

            return (string) $valor === '' ? 'Sin dato' : (string) $valor;
        };

        return $this->respuesta(true, '', $this->historico->listar('cliente', $id, $etiquetas, $formatear));
    }

    private function datoshistorico(Cliente $cliente)
    {
        return [
            'nombre'    => $cliente->getTbclientenombre(),
            'telefono'  => $cliente->getTbclientetelefono(),
            'direccion' => (string) $cliente->getTbclientedireccion(),
            'estado'    => (int) $cliente->getTbclienteestado(),
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
        foreach (['tbclientenombre', 'tbclientetelefono', 'tbclientecorreo', 'tbclientedireccion'] as $campo) {
            $limpios[$campo] = trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        }

        // El teléfono se guarda como "8888-8888"
        $digitos = preg_replace('/\D/', '', $limpios['tbclientetelefono']);
        if (strlen($digitos) === 8) {
            $limpios['tbclientetelefono'] = substr($digitos, 0, 4) . '-' . substr($digitos, 4);
        }

        $limpios['tbclientecorreo'] = mb_strtolower($limpios['tbclientecorreo']);

        return $limpios;
    }

    private function validar($datos, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbclientenombre'] === '') {
            $errores['tbclientenombre'] = 'El nombre del cliente es obligatorio.';
        } elseif (mb_strlen($datos['tbclientenombre']) > 100) {
            $errores['tbclientenombre'] = 'El nombre no puede superar 100 caracteres.';
        } elseif (!preg_match("/^[\p{L}\s'.-]+$/u", $datos['tbclientenombre'])) {
            $errores['tbclientenombre'] = 'El nombre solo puede contener letras.';
        }

        if ($datos['tbclientetelefono'] === '') {
            $errores['tbclientetelefono'] = 'El teléfono es obligatorio (es el WhatsApp del cliente).';
        } elseif (!preg_match('/^[2-8]\d{3}-\d{4}$/', $datos['tbclientetelefono'])) {
            $errores['tbclientetelefono'] = 'El teléfono debe tener 8 números y empezar con 2, 4, 5, 6, 7 u 8.';
        } elseif ($this->repositorio->existetelefono($datos['tbclientetelefono'], $idexcluir)) {
            $errores['tbclientetelefono'] = 'Ya hay un cliente registrado con ese teléfono.';
        }

        if ($datos['tbclientecorreo'] !== '') {
            if (!filter_var($datos['tbclientecorreo'], FILTER_VALIDATE_EMAIL)) {
                $errores['tbclientecorreo'] = 'El correo no tiene un formato válido.';
            } elseif (mb_strlen($datos['tbclientecorreo']) > 100) {
                $errores['tbclientecorreo'] = 'El correo no puede superar 100 caracteres.';
            } elseif ($this->repositorio->existecorreo($datos['tbclientecorreo'], $idexcluir)) {
                $errores['tbclientecorreo'] = 'Ya hay un cliente registrado con ese correo.';
            }
        }

        if (mb_strlen($datos['tbclientedireccion']) > 200) {
            $errores['tbclientedireccion'] = 'La dirección no puede superar 200 caracteres.';
        }

        return $errores;
    }

    private function convertirarreglo(Cliente $cliente, $conteos)
    {
        $id = (int) $cliente->getTbclienteid();

        return [
            'tbclienteid'            => $id,
            'tbclientenombre'        => $cliente->getTbclientenombre(),
            'tbclientetelefono'      => $cliente->getTbclientetelefono(),
            'tbclientecorreo'        => (string) $cliente->getTbclientecorreo(),
            'tbclientedireccion'     => (string) $cliente->getTbclientedireccion(),
            'tbclienteestado'        => (int) $cliente->getTbclienteestado(),
            'tbclientefecharegistro' => $cliente->getTbclientefecharegistro(),
            'solicitudes'            => $conteos[$id]['total'] ?? 0,
            'solicitudesabiertas'    => $conteos[$id]['abiertas'] ?? 0,
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
