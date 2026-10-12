<?php
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';

class empleadocontrolador
{
    private $repositorio;
    private $encargos;
    private $roles = ['Dueña', 'Empleado'];

    public function __construct()
    {
        $this->repositorio = new empleadorepositorio();
        $this->encargos = new encargorepositorio();
    }

    public function listar($texto = '', $estado = null)
    {
        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $empleado) {
            $lista[] = $this->convertirarreglo($empleado);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $empleado = $this->repositorio->obtenerporid((int) $id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($empleado));
    }

    public function crear($datos)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $id = $this->repositorio->insertar(new Empleado($datos));

        return $this->respuesta(true, 'Empleado registrado correctamente.', ['tbempleadoid' => $id]);
    }

    public function actualizar($datos)
    {
        $id = (int) ($datos['tbempleadoid'] ?? 0);
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id, $empleado->estaActivo());

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $empleado->setTbempleadonombre($datos['tbempleadonombre']);
        $empleado->setTbempleadotelefono($datos['tbempleadotelefono']);
        $empleado->setTbempleadorol($datos['tbempleadorol']);
        $this->repositorio->actualizar($empleado);

        return $this->respuesta(true, 'Empleado actualizado correctamente.');
    }

    public function inactivar($id)
    {
        $id = (int) $id;
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        if (!$empleado->estaActivo()) {
            return $this->respuesta(false, 'El empleado ya está inactivo.');
        }

        $abiertos = $this->encargos->contarabiertosporempleado($id);
        if ($abiertos > 0) {
            return $this->respuesta(false, 'No se puede inactivar: tiene ' . $abiertos
                . ' encargo(s) pendiente(s) o en proceso. Reasígnelos primero.');
        }

        $this->repositorio->inactivar($id);

        return $this->respuesta(true, 'Empleado inactivado correctamente.');
    }

    public function reactivar($id)
    {
        $id = (int) $id;
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        if ($empleado->estaActivo()) {
            return $this->respuesta(false, 'El empleado ya está activo.');
        }

        if ($empleado->esDuena() && $this->repositorio->existeduenaactiva($id)) {
            return $this->respuesta(false, 'Ya hay otra dueña activa. Solo puede haber una.');
        }

        $this->repositorio->reactivar($id);

        return $this->respuesta(true, 'Empleado reactivado correctamente.');
    }

    private function limpiar($datos)
    {
        $limpios = [
            'tbempleadonombre'   => trim(preg_replace('/\s+/u', ' ', (string) ($datos['tbempleadonombre'] ?? ''))),
            'tbempleadotelefono' => trim((string) ($datos['tbempleadotelefono'] ?? '')),
            'tbempleadorol'      => trim((string) ($datos['tbempleadorol'] ?? '')),
        ];

        $digitos = preg_replace('/\D/', '', $limpios['tbempleadotelefono']);
        if (strlen($digitos) === 8) {
            $limpios['tbempleadotelefono'] = substr($digitos, 0, 4) . '-' . substr($digitos, 4);
        }

        return $limpios;
    }

    private function validar($datos, $idexcluir = 0, $activo = true)
    {
        $errores = [];

        if ($datos['tbempleadonombre'] === '') {
            $errores['tbempleadonombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($datos['tbempleadonombre']) > 100) {
            $errores['tbempleadonombre'] = 'El nombre no puede superar 100 caracteres.';
        } elseif (!preg_match("/^[\p{L}\s'.-]+$/u", $datos['tbempleadonombre'])) {
            $errores['tbempleadonombre'] = 'El nombre solo puede contener letras.';
        }

        if ($datos['tbempleadotelefono'] === '') {
            $errores['tbempleadotelefono'] = 'El teléfono es obligatorio.';
        } elseif (!preg_match('/^\d{4}-\d{4}$/', $datos['tbempleadotelefono'])) {
            $errores['tbempleadotelefono'] = 'El teléfono debe tener exactamente 8 números.';
        } elseif ($this->repositorio->existetelefono($datos['tbempleadotelefono'], $idexcluir)) {
            $errores['tbempleadotelefono'] = 'Ya existe un empleado con ese teléfono.';
        }

        if ($datos['tbempleadorol'] === '') {
            $errores['tbempleadorol'] = 'El rol es obligatorio.';
        } elseif (!in_array($datos['tbempleadorol'], $this->roles, true)) {
            $errores['tbempleadorol'] = 'El rol seleccionado no es válido.';
        } elseif ($datos['tbempleadorol'] === 'Dueña' && $activo && $this->repositorio->existeduenaactiva($idexcluir)) {
            $errores['tbempleadorol'] = 'Ya hay una dueña registrada. Solo puede haber una activa.';
        }

        return $errores;
    }

    private function convertirarreglo(Empleado $empleado)
    {
        return [
            'tbempleadoid'       => $empleado->getTbempleadoid(),
            'tbempleadonombre'   => $empleado->getTbempleadonombre(),
            'tbempleadotelefono' => $empleado->getTbempleadotelefono(),
            'tbempleadorol'      => $empleado->getTbempleadorol(),
            'tbempleadoestado'   => (int) $empleado->getTbempleadoestado(),
            'encargosabiertos'   => $this->encargos->contarabiertosporempleado($empleado->getTbempleadoid()),
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