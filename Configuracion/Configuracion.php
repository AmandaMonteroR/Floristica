<?php
// Datos de conexión a MySQL (ajústelos según su instalación local)
define('DB_HOST', 'localhost');
define('DB_PUERTO', '3306');
define('DB_NOMBRE', 'bdfloristica');
define('DB_USUARIO', 'root');
define('DB_CLAVE', '');
define('DB_CHARSET', 'utf8mb4');

date_default_timezone_set('America/Costa_Rica');

function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}