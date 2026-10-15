<?php
require_once __DIR__ . '/../Aplicacion/Repositorio/empleadorepositorio.php';

// Sesión del usuario y permisos por rol. Todo se valida en el servidor.
class Seguridad
{
    // Lo que puede hacer un empleado. '' es abrir la pantalla; '*' es todo el módulo.
    // El propietario puede hacer todo.
    private static $permisosempleado = [
        'inicio'     => '*',
        'cuenta'     => ['cambiarclave'],
        'cliente'    => '*',
        'cotizacion' => '*',
        'catalogo'   => ['', 'listar', 'obtener'],
        'encargo'    => ['', 'listar', 'obtener', 'opciones', 'crear', 'asignarme', 'iniciar', 'terminar'],
    ];

    private static $usuario = false;

    public static function iniciar()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_name('floristica');
            session_start();
        }
    }

    // Devuelve el usuario conectado o null. Se vuelve a leer de la base en cada
    // petición, así un empleado inactivado pierde el acceso de inmediato.
    public static function usuario()
    {
        if (self::$usuario !== false) {
            return self::$usuario;
        }

        self::$usuario = null;
        $id = (int) ($_SESSION['idempleado'] ?? 0);

        if ($id > 0) {
            $empleado = (new empleadorepositorio())->obtenerporid($id);

            if ($empleado && $empleado->estaActivo() && $empleado->tieneCuenta()) {
                self::$usuario = [
                    'id'     => (int) $empleado->getTbempleadoid(),
                    'nombre' => $empleado->getTbempleadonombre(),
                    'rol'    => $empleado->getTbempleadorol(),
                ];
            } else {
                self::cerrar();
            }
        }

        return self::$usuario;
    }

    public static function espropietario()
    {
        $usuario = self::usuario();
        return $usuario !== null && $usuario['rol'] === 'Propietario';
    }

    public static function ingresar(Empleado $empleado)
    {
        // Evita que alguien reutilice un identificador de sesión anterior
        session_regenerate_id(true);
        $_SESSION['idempleado'] = (int) $empleado->getTbempleadoid();
        self::$usuario = false;
    }

    public static function cerrar()
    {
        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }

        self::$usuario = null;
    }

    public static function permitido($modulo, $accion = '')
    {
        if (self::usuario() === null) {
            return false;
        }

        if (self::espropietario()) {
            return true;
        }

        $permisos = self::$permisosempleado[$modulo] ?? [];

        return $permisos === '*' || in_array($accion, $permisos, true);
    }
}