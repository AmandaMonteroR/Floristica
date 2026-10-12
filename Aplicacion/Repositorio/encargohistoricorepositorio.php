<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class encargohistoricorepositorio
{
    private $conexion;
    private $campos = ['estado', 'empleado'];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tabla($campo)
    {
        if (!in_array($campo, $this->campos, true)) {
            throw new InvalidArgumentException('Campo de histórico no válido.');
        }

        return 'tbencargo' . $campo . 'historico';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function registrar($campo, $idencargo, $valor, $idempleado)
    {
        $tabla = $this->tabla($campo);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tbencargoid, {$tabla}valor, tbempleadoid, {$tabla}fecha)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid($tabla),
            $idencargo,
            $valor,
            $idempleado,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function listar($idencargo)
    {
        $sql = "SELECT 'estado' AS campo, h.tbencargoestadohistoricoid AS id, h.tbencargoestadohistoricovalor AS valor,
                       NULL AS nombreasignado, h.tbencargoestadohistoricofecha AS fecha, quien.tbempleadonombre AS hechopor
                FROM tbencargoestadohistorico h
                LEFT JOIN tbempleado quien ON quien.tbempleadoid = h.tbempleadoid
                WHERE h.tbencargoid = ?
                UNION ALL
                SELECT 'empleado', h.tbencargoempleadohistoricoid, h.tbencargoempleadohistoricovalor,
                       asignado.tbempleadonombre, h.tbencargoempleadohistoricofecha, quien.tbempleadonombre
                FROM tbencargoempleadohistorico h
                LEFT JOIN tbempleado asignado ON asignado.tbempleadoid = h.tbencargoempleadohistoricovalor
                LEFT JOIN tbempleado quien ON quien.tbempleadoid = h.tbempleadoid
                WHERE h.tbencargoid = ?
                ORDER BY fecha, campo DESC, id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $idencargo, (int) $idencargo]);

        return $sentencia->fetchAll();
    }
}