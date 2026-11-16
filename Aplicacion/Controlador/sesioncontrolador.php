<?php
require_once __DIR__ . '/../../Configuracion/Seguridad.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/empleadocontrolador.php';

class sesioncontrolador
{
    private $empleados;
    private $intentosmaximos = 3;
    private $segundosbloqueo = 60;

    public function __construct()
    {
        $this->empleados = new empleadorepositorio();
    }

    // true si todavía no existe la cuenta del propietario
    public function primeravez()
    {
        return !$this->empleados->haypropietarioconcuenta();
    }

    public function ingresar($datos)
    {
        $espera = $this->espera();
        if ($espera > 0) {
            return $this->bloqueado($espera);
        }

        $usuario = mb_strtolower(trim((string) ($datos['usuario'] ?? '')));
        $clave = (string) ($datos['clave'] ?? '');

        $errores = [];
        if ($usuario === '') {
            $errores['usuario'] = 'Escriba su usuario.';
        }
        if ($clave === '') {
            $errores['clave'] = 'Escriba su contraseña.';
        }
        if ($errores) {
            return $this->respuesta(false, 'Complete los datos para ingresar.', ['errores' => $errores]);
        }

        $empleado = $this->empleados->obtenerporusuario($usuario);
        $correcto = $empleado
            && $empleado->tieneCuenta()
            && password_verify($clave, $empleado->getTbempleadoclave());

        if (!$correcto) {
            $_SESSION['intentos'] = (int) ($_SESSION['intentos'] ?? 0) + 1;

            // Al tercer intento fallido se bloquea; los 60 segundos cuentan desde ese intento
            if ($_SESSION['intentos'] >= $this->intentosmaximos) {
                $_SESSION['intentos'] = 0;
                $_SESSION['bloqueohasta'] = time() + $this->segundosbloqueo;
                return $this->bloqueado($this->segundosbloqueo);
            }

            $quedan = $this->intentosmaximos - $_SESSION['intentos'];

            // El mismo mensaje para usuario o contraseña incorrectos, así no se revela cuál falló
            return $this->respuesta(false, 'Usuario o contraseña incorrectos. '
                . ($quedan === 1 ? 'Le queda 1 intento.' : 'Le quedan ' . $quedan . ' intentos.'));
        }

        if (!$empleado->estaActivo()) {
            return $this->respuesta(false, 'Su cuenta está inactiva. Hable con el propietario de la floristería.');
        }

        unset($_SESSION['intentos'], $_SESSION['bloqueohasta']);
        Seguridad::ingresar($empleado);

        return $this->respuesta(true, 'Bienvenida, ' . $empleado->getTbempleadonombre() . '.');
    }

    // Segundos que faltan para poder intentar de nuevo (0 si no está bloqueado).
    // La página de ingreso también la usa para seguir la cuenta regresiva si se recarga.
    public function espera()
    {
        return max(0, (int) ($_SESSION['bloqueohasta'] ?? 0) - time());
    }

    private function bloqueado($espera)
    {
        return $this->respuesta(false, 'Demasiados intentos. Espere ' . $espera . ' segundos e intente de nuevo.',
            ['espera' => $espera]);
    }

    public function registrarpropietario($datos)
    {
        $respuesta = (new empleadocontrolador())->crearpropietario($datos);

        if (!$respuesta['exito']) {
            return $respuesta;
        }

        Seguridad::ingresar($respuesta['datos']['empleado']);

        return $this->respuesta(true, 'Cuenta del propietario creada. Ya puede registrar a sus empleados.');
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