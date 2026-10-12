<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class historicorepositorio
{
    private $conexion;

    // Entidades y datos que llevan histórico. Solo se aceptan estos nombres.
    private $campos = [
        'inventario'    => ['nombre', 'proveedor', 'precio', 'stockminimo', 'estado'],
        'arreglofloral' => ['nombre', 'precio', 'estado'],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tabla($entidad, $campo)
    {
        if (!in_array($campo, $this->campos[$entidad] ?? [], true)) {
            throw new InvalidArgumentException('Histórico no válido.');
        }

        return 'tb' . $entidad . $campo . 'historico';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function registrar($entidad, $campo, $id, $valor, $idempleado)
    {
        $tabla = $this->tabla($entidad, $campo);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tb{$entidad}id, {$tabla}valor, tbempleadoid, {$tabla}fecha)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid($tabla),
            (int) $id,
            $valor,
            $idempleado,
            date('Y-m-d H:i:s'),
        ]);
    }

    // Del más antiguo al más reciente, con el nombre de quien hizo el cambio
    public function listar($entidad, $campo, $id)
    {
        $tabla = $this->tabla($entidad, $campo);
        $sql = "SELECT h.{$tabla}valor AS valor, h.{$tabla}fecha AS fecha, e.tbempleadonombre AS hechopor
                FROM {$tabla} h
                LEFT JOIN tbempleado e ON e.tbempleadoid = h.tbempleadoid
                WHERE h.tb{$entidad}id = ?
                ORDER BY h.{$tabla}fecha, h.{$tabla}id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $id]);

        return $sentencia->fetchAll();
    }
}