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

        $datos['tbempleadoclave'] = password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT);
        $id = $this->repositorio->insertar(new Empleado($datos));

        return $this->respuesta(true, 'Empleado registrado correctamente.', ['tbempleadoid' => $id]);
    }

    public function actualizar($datos, $usuarioactual)
    {
        $id = (int) ($datos['tbempleadoid'] ?? 0);
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $empleado);

        if ($id === (int) $usuarioactual['id'] && $datos['tbempleadorol'] !== 'Dueña') {
            $errores['tbempleadorol'] = 'No puede quitarse a sí misma el rol de dueña.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $empleado->setTbempleadonombre($datos['tbempleadonombre']);
        $empleado->setTbempleadotelefono($datos['tbempleadotelefono']);
        $empleado->setTbempleadorol($datos['tbempleadorol']);
        $empleado->setTbempleadousuario($datos['tbempleadousuario']);

        // Si no se escribe una contraseña nueva, se conserva la anterior
        if ($datos['tbempleadoclave'] !== '') {
            $empleado->setTbempleadoclave(password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT));
        }

        $this->repositorio->actualizar($empleado);

        return $this->respuesta(true, 'Empleado actualizado correctamente.');
    }

    // Primera vez que se usa el sistema: se crea (o completa) la cuenta de la dueña
    public function crearduena($datos)
    {
        if ($this->repositorio->hayduenaconcuenta()) {
            return $this->respuesta(false, 'La cuenta de la dueña ya fue creada. Inicie sesión.');
        }

        $datos = $this->limpiar($datos);
        $datos['tbempleadorol'] = 'Dueña';

        // Si ya había una dueña registrada sin cuenta, se completa su registro
        $existente = $this->repositorio->obtenerduena();
        $errores = $this->validar($datos, $existente);

        if (($datos['tbempleadoclaveconfirmar'] ?? '') !== $datos['tbempleadoclave']) {
            $errores['tbempleadoclaveconfirmar'] = 'Las contraseñas no coinciden.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $clave = password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT);

        if ($existente) {
            $existente->setTbempleadonombre($datos['tbempleadonombre']);
            $existente->setTbempleadotelefono($datos['tbempleadotelefono']);
            $existente->setTbempleadousuario($datos['tbempleadousuario']);
            $existente->setTbempleadoclave($clave);
            $this->repositorio->actualizar($existente);
            $duena = $existente;
        } else {
            $datos['tbempleadoclave'] = $clave;
            $id = $this->repositorio->insertar(new Empleado($datos));
            $duena = $this->repositorio->obtenerporid($id);
        }

        return $this->respuesta(true, 'Cuenta creada.', ['empleado' => $duena]);
    }

    public function inactivar($id, $usuarioactual)
    {
        $id = (int) $id;
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        if ($id === (int) $usuarioactual['id']) {
            return $this->respuesta(false, 'No puede inactivar su propia cuenta.');
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

        return $this->respuesta(true, 'Empleado inactivado correctamente. Ya no podrá iniciar sesión.');
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
            'tbempleadonombre'         => trim(preg_replace('/\s+/u', ' ', (string) ($datos['tbempleadonombre'] ?? ''))),
            'tbempleadotelefono'       => trim((string) ($datos['tbempleadotelefono'] ?? '')),
            'tbempleadorol'            => trim((string) ($datos['tbempleadorol'] ?? '')),
            'tbempleadousuario'        => mb_strtolower(trim((string) ($datos['tbempleadousuario'] ?? ''))),
            // La contraseña no se recorta: los espacios cuentan
            'tbempleadoclave'          => (string) ($datos['tbempleadoclave'] ?? ''),
            'tbempleadoclaveconfirmar' => (string) ($datos['tbempleadoclaveconfirmar'] ?? ''),
        ];

        $digitos = preg_replace('/\D/', '', $limpios['tbempleadotelefono']);
        if (strlen($digitos) === 8) {
            $limpios['tbempleadotelefono'] = substr($digitos, 0, 4) . '-' . substr($digitos, 4);
        }

        return $limpios;
    }

    // $empleado es el registro que se está editando (null si es nuevo)
    private function validar($datos, $empleado = null)
    {
        $errores = [];
        $idexcluir = $empleado ? (int) $empleado->getTbempleadoid() : 0;
        $activo = $empleado ? $empleado->estaActivo() : true;

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

        if ($datos['tbempleadousuario'] === '') {
            $errores['tbempleadousuario'] = 'El usuario es obligatorio.';
        } elseif (!preg_match('/^[a-z0-9._]{4,30}$/', $datos['tbempleadousuario'])) {
            $errores['tbempleadousuario'] = 'El usuario debe tener de 4 a 30 caracteres: letras sin tildes, números, punto o guion bajo.';
        } elseif ($this->repositorio->existeusuario($datos['tbempleadousuario'], $idexcluir)) {
            $errores['tbempleadousuario'] = 'Ese usuario ya está en uso.';
        }

        // La contraseña es obligatoria si la persona todavía no tiene una
        $necesitaclave = !$empleado || !$empleado->tieneCuenta();

        if ($datos['tbempleadoclave'] === '') {
            if ($necesitaclave) {
                $errores['tbempleadoclave'] = 'La contraseña es obligatoria.';
            }
        } elseif (mb_strlen($datos['tbempleadoclave']) < 8 || strlen($datos['tbempleadoclave']) > 72) {
            $errores['tbempleadoclave'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbempleadoclave']) || !preg_match('/\d/', $datos['tbempleadoclave'])) {
            $errores['tbempleadoclave'] = 'La contraseña debe tener al menos una letra y un número.';
        }

        return $errores;
    }

    private function convertirarreglo(Empleado $empleado)
    {
        // La contraseña nunca se envía al navegador
        return [
            'tbempleadoid'       => $empleado->getTbempleadoid(),
            'tbempleadonombre'   => $empleado->getTbempleadonombre(),
            'tbempleadotelefono' => $empleado->getTbempleadotelefono(),
            'tbempleadorol'      => $empleado->getTbempleadorol(),
            'tbempleadoestado'   => (int) $empleado->getTbempleadoestado(),
            'tbempleadousuario'  => (string) $empleado->getTbempleadousuario(),
            'tienecuenta'        => $empleado->tieneCuenta(),
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