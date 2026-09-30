<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class proveedorhistoricorepositorio
{
    private $conexion;
    private $campos = ['nombreempresa', 'nombrecontacto', 'telefono', 'correo', 'estado'];

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

    public function registrar($campo, $idproveedor, $valor)
    {
        $tabla = $this->tabla($campo);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tbproveedorid, {$tabla}valor, {$tabla}fecha) VALUES (?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid($tabla),
            $idproveedor,
            $valor,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function listar($campo, $idproveedor)
    {
        $tabla = $this->tabla($campo);
        $sql = "SELECT {$tabla}valor AS valor, {$tabla}fecha AS fecha FROM {$tabla}
                WHERE tbproveedorid = ?
                ORDER BY {$tabla}fecha, {$tabla}id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$idproveedor]);

        return $sentencia->fetchAll();
    }
}