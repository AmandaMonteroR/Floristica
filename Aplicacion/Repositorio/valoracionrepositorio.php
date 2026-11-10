<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

// Datos que necesita la valoración de un ramo por foto: el color de las flores y de las
// envolturas del inventario, y cuánto se usa cada producto en las recetas del catálogo.
class valoracionrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // [idproducto => ['tipo' => 'Flor' o 'Follaje', 'color' => 'Rojo'], ...] de las flores activas
    public function flores()
    {
        $sql = 'SELECT f.tbinventarioid, f.tbinventariocategoriaflortipo AS tipo, c.tbcolornombre AS color
                FROM tbinventariocategoriaflor f
                INNER JOIN tbinventario i ON i.tbinventarioid = f.tbinventarioid
                LEFT JOIN tbcolor c ON c.tbcolorid = f.tbcolorid
                WHERE i.tbinventarioestado = 1';
        $lista = [];
        foreach ($this->conexion->query($sql)->fetchAll() as $fila) {
            $lista[(int) $fila['tbinventarioid']] = ['tipo' => (string) $fila['tipo'], 'color' => (string) $fila['color']];
        }

        return $lista;
    }

    // [idproducto => ['clase' => 'Papel' o 'Cinta', 'color' => 'Negro'], ...] de las envolturas activas
    public function envolturas()
    {
        $sql = 'SELECT e.tbinventarioid, e.tbinventariocategoriaenvolturaclase AS clase, c.tbcolornombre AS color
                FROM tbinventariocategoriaenvoltura e
                INNER JOIN tbinventario i ON i.tbinventarioid = e.tbinventarioid
                LEFT JOIN tbcolor c ON c.tbcolorid = e.tbcolorid
                WHERE i.tbinventarioestado = 1';
        $lista = [];
        foreach ($this->conexion->query($sql)->fetchAll() as $fila) {
            $lista[(int) $fila['tbinventarioid']] = ['clase' => (string) $fila['clase'], 'color' => (string) $fila['color']];
        }

        return $lista;
    }

    // [idproducto => en cuántos arreglos activos del catálogo se usa]
    // Si se da $idjunto, solo cuenta los arreglos que también llevan ese producto.
    public function usos($idjunto = 0)
    {
        $sql = 'SELECT d.tbinventarioid, COUNT(DISTINCT d.tbarreglofloralid) AS usos
                FROM tbarreglofloralinsumo d
                INNER JOIN tbarreglofloral a ON a.tbarreglofloralid = d.tbarreglofloralid
                WHERE a.tbarreglofloralestado = 1';
        $parametros = [];
        if ($idjunto > 0) {
            $sql .= ' AND d.tbarreglofloralid IN (SELECT tbarreglofloralid FROM tbarreglofloralinsumo WHERE tbinventarioid = ?)';
            $parametros[] = (int) $idjunto;
        }
        $sql .= ' GROUP BY d.tbinventarioid';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);
        $lista = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $lista[(int) $fila['tbinventarioid']] = (int) $fila['usos'];
        }

        return $lista;
    }
}
