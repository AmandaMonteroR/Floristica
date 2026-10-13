<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class proveedorhistoricorepositorio
{
    private $conexion;
    private $campos = ['nombreempresa', 'nombrecontacto', 'telefono', 'correo', 'ubicacion', 'estado'];
    
    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tabla($campo)
    {
        if (!in_array($campo, $this->campos, true)) {
            throw new InvalidArgumentException('Campo de histórico no válido.');
        }

        return 'tbproveedor' . $campo . 'historico';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    // $idempleado es quien hizo el cambio
    public function registrar($campo, $idproveedor, $valor, $idempleado)
    {
        $tabla = $this->tabla($campo);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tbproveedorid, {$tabla}valor, tbempleadoid, {$tabla}fecha)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid($tabla),
            $idproveedor,
            $valor,
            $idempleado,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function listar($campo, $idproveedor)
    {
        $tabla = $this->tabla($campo);
        $sql = "SELECT h.{$tabla}valor AS valor, h.{$tabla}fecha AS fecha, e.tbempleadonombre AS hechopor
                FROM {$tabla} h
                LEFT JOIN tbempleado e ON e.tbempleadoid = h.tbempleadoid
                WHERE h.tbproveedorid = ?
                ORDER BY h.{$tabla}fecha, h.{$tabla}id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$idproveedor]);

        return $sentencia->fetchAll();
    }
}