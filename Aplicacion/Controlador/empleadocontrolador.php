<?php
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

class empleadocontrolador
{
    private $repositorio;
    private $encargos;
    private $historico;
    private $roles = ['Propietario', 'Empleado'];

    public function __construct()
    {
        $this->repositorio = new empleadorepositorio();
        $this->encargos = new encargorepositorio();
        $this->historico = new historicocontrolador();
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

    public function crear($datos, $usuarioactual)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbempleadoclave'] = password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT);
        $empleado = new Empleado($datos);

        $id = $this->entransaccion(function () use ($empleado, $usuarioactual) {
            $id = $this->repositorio->insertar($empleado);
            $nuevos = $this->datoshistorico($this->repositorio->obtenerporid($id)) + ['clave' => 'Asignada'];
            $this->historico->registrar('empleado', $id, [], $nuevos, $usuarioactual['id']);

            return $id;
        });

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

        if ($id === (int) $usuarioactual['id'] && $datos['tbempleadorol'] !== 'Propietario') {
            $errores['tbempleadorol'] = 'No puede quitarse a sí mismo el rol de propietario.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($empleado);

        $empleado->setTbempleadonombre($datos['tbempleadonombre']);
        $empleado->setTbempleadotelefono($datos['tbempleadotelefono']);
        $empleado->setTbempleadorol($datos['tbempleadorol']);
        $empleado->setTbempleadousuario($datos['tbempleadousuario']);

        $nuevos = $this->datoshistorico($empleado);

        // Si no se escribe una contraseña nueva, se conserva la anterior.
        // Del cambio de contraseña solo queda que se cambió, nunca el valor.
        if ($datos['tbempleadoclave'] !== '') {
            $nuevos['clave'] = $empleado->tieneCuenta() ? 'Cambiada' : 'Asignada';
            $empleado->setTbempleadoclave(password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT));
        }

        $this->entransaccion(function () use ($empleado, $id, $anteriores, $nuevos, $usuarioactual) {
            $this->repositorio->actualizar($empleado);
            $this->historico->registrar('empleado', $id, $anteriores, $nuevos, $usuarioactual['id']);
        });

        return $this->respuesta(true, 'Empleado actualizado correctamente.');
    }

    // Cada usuario cambia su propia contraseña. Debe escribir la actual para
    // confirmar que es esa persona (por si dejó la sesión abierta en otra compu).
    public function cambiarclave($datos, $usuarioactual)
    {
        $id = (int) $usuarioactual['id'];
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'No se encontró su cuenta.');
        }

        $actual = (string) ($datos['claveactual'] ?? '');
        $nueva = (string) ($datos['clavenueva'] ?? '');
        $confirmar = (string) ($datos['claveconfirmar'] ?? '');
        $errores = [];

        if ($actual === '') {
            $errores['claveactual'] = 'Escriba su contraseña actual.';
        } elseif (!password_verify($actual, $empleado->getTbempleadoclave())) {
            $errores['claveactual'] = 'La contraseña actual no es correcta.';
        }

        if ($nueva === '') {
            $errores['clavenueva'] = 'Escriba la contraseña nueva.';
        } elseif (mb_strlen($nueva) < 8 || strlen($nueva) > 72) {
            $errores['clavenueva'] = 'La contraseña debe tener entre 8 y 72 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $nueva) || !preg_match('/\d/', $nueva)) {
            $errores['clavenueva'] = 'La contraseña debe tener al menos una letra y un número.';
        } elseif ($nueva === $actual) {
            $errores['clavenueva'] = 'La contraseña nueva debe ser diferente de la actual.';
        }

        if ($confirmar !== $nueva) {
            $errores['claveconfirmar'] = 'Las contraseñas no coinciden.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $empleado->setTbempleadoclave(password_hash($nueva, PASSWORD_DEFAULT));

        // En el historial solo queda que se cambió (y quién lo hizo), nunca el valor
        $this->entransaccion(function () use ($empleado, $id) {
            $this->repositorio->actualizar($empleado);
            $this->historico->registrar('empleado', $id, [], ['clave' => 'Cambiada'], $id);
        });

        return $this->respuesta(true, 'Contraseña cambiada. La próxima vez que ingrese, use la nueva.');
    }

    // Primera vez que se usa el sistema: se crea (o completa) la cuenta del propietario
    public function crearpropietario($datos)
    {
        if ($this->repositorio->haypropietarioconcuenta()) {
            return $this->respuesta(false, 'La cuenta del propietario ya fue creada. Inicie sesión.');
        }

        $datos = $this->limpiar($datos);
        $datos['tbempleadorol'] = 'Propietario';

        // Si ya había un propietario registrado sin cuenta, se completa su registro
        $existente = $this->repositorio->obtenerpropietario();
        $errores = $this->validar($datos, $existente);

        if (($datos['tbempleadoclaveconfirmar'] ?? '') !== $datos['tbempleadoclave']) {
            $errores['tbempleadoclaveconfirmar'] = 'Las contraseñas no coinciden.';
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $clave = password_hash($datos['tbempleadoclave'], PASSWORD_DEFAULT);

        // El propietario es quien hace el cambio en su propio registro
        $propietario = $this->entransaccion(function () use ($existente, $datos, $clave) {
            if ($existente) {
                $anteriores = $this->datoshistorico($existente);
                $existente->setTbempleadonombre($datos['tbempleadonombre']);
                $existente->setTbempleadotelefono($datos['tbempleadotelefono']);
                $existente->setTbempleadousuario($datos['tbempleadousuario']);
                $existente->setTbempleadoclave($clave);
                $this->repositorio->actualizar($existente);
                $propietario = $existente;
            } else {
                $anteriores = [];
                $datos['tbempleadoclave'] = $clave;
                $id = $this->repositorio->insertar(new Empleado($datos));
                $propietario = $this->repositorio->obtenerporid($id);
            }

            $nuevos = $this->datoshistorico($propietario) + ['clave' => 'Asignada'];
            $this->historico->registrar('empleado', $propietario->getTbempleadoid(), $anteriores, $nuevos, $propietario->getTbempleadoid());

            return $propietario;
        });

        return $this->respuesta(true, 'Cuenta creada.', ['empleado' => $propietario]);
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

        $this->entransaccion(function () use ($id, $usuarioactual) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('empleado', $id, ['estado' => 1], ['estado' => 0], $usuarioactual['id']);
        });

        return $this->respuesta(true, 'Empleado inactivado correctamente. Ya no podrá iniciar sesión.');
    }

    public function reactivar($id, $usuarioactual)
    {
        $id = (int) $id;
        $empleado = $this->repositorio->obtenerporid($id);

        if (!$empleado) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        if ($empleado->estaActivo()) {
            return $this->respuesta(false, 'El empleado ya está activo.');
        }

        if ($empleado->esPropietario() && $this->repositorio->existepropietarioactivo($id)) {
            return $this->respuesta(false, 'Ya hay otro propietario activo. Solo puede haber uno.');
        }

        $this->entransaccion(function () use ($id, $usuarioactual) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('empleado', $id, ['estado' => 0], ['estado' => 1], $usuarioactual['id']);
        });

        return $this->respuesta(true, 'Empleado reactivado correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El empleado no existe.');
        }

        $etiquetas = [
            'nombre'   => 'Nombre',
            'telefono' => 'Teléfono',
            'rol'      => 'Rol',
            'usuario'  => 'Usuario',
            'clave'    => 'Contraseña',
            'estado'   => 'Estado',
        ];

        $formatear = function ($campo, $valor) {
            return $campo === 'estado' ? historicocontrolador::estado($valor) : (string) $valor;
        };

        $cambios = $this->historico->listar('empleado', $id, $etiquetas, $formatear);

        // De la contraseña no hay "valor anterior" que mostrar, solo que se cambió
        foreach ($cambios as $indice => $cambio) {
            if ($cambio['campo'] === 'clave' && $cambio['anterior'] !== null) {
                $cambios[$indice]['anterior'] = '—';
            }
        }

        return $this->respuesta(true, '', $cambios);
    }

    // Datos del empleado que llevan histórico (la contraseña se registra aparte)
    private function datoshistorico(Empleado $empleado)
    {
        return [
            'nombre'   => $empleado->getTbempleadonombre(),
            'telefono' => $empleado->getTbempleadotelefono(),
            'rol'      => $empleado->getTbempleadorol(),
            'usuario'  => (string) $empleado->getTbempleadousuario(),
            'estado'   => (int) $empleado->getTbempleadoestado(),
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
        } elseif ($datos['tbempleadorol'] === 'Propietario' && $activo && $this->repositorio->existepropietarioactivo($idexcluir)) {
            $errores['tbempleadorol'] = 'Ya hay un propietario registrado. Solo puede haber uno activo.';
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