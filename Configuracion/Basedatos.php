<?php
require_once __DIR__ . '/Configuracion.php';

class Basedatos
{
    private static $conexion = null;

    // Si la conexión falla lanza PDOException; index.php la convierte en respuesta JSON
    public static function conectar()
    {
        if (self::$conexion === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PUERTO .
                   ';dbname=' . DB_NOMBRE . ';charset=' . DB_CHARSET;
            self::$conexion = new PDO($dsn, DB_USUARIO, DB_CLAVE, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$conexion;
    }
}