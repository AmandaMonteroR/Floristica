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

        return 'tbsolicitudencargo' . $campo . 'historico';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function registrar($campo, $idencargo, $valor, $idempleado)
    {
        $tabla = $this->tabla($campo);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tbsolicitudencargoid, {$tabla}valor, tbempleadoid, {$tabla}fecha)
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
        $sql = "SELECT 'estado' AS campo, h.tbsolicitudencargoestadohistoricoid AS id, h.tbsolicitudencargoestadohistoricovalor AS valor,
                       NULL AS nombreasignado, h.tbsolicitudencargoestadohistoricofecha AS fecha, quien.tbempleadonombre AS hechopor
                FROM tbsolicitudencargoestadohistorico h
                LEFT JOIN tbempleado quien ON quien.tbempleadoid = h.tbempleadoid
                WHERE h.tbsolicitudencargoid = ?
                UNION ALL
                SELECT 'empleado', h.tbsolicitudencargoempleadohistoricoid, h.tbsolicitudencargoempleadohistoricovalor,
                       asignado.tbempleadonombre, h.tbsolicitudencargoempleadohistoricofecha, quien.tbempleadonombre
                FROM tbsolicitudencargoempleadohistorico h
                LEFT JOIN tbempleado asignado ON asignado.tbempleadoid = h.tbsolicitudencargoempleadohistoricovalor
                LEFT JOIN tbempleado quien ON quien.tbempleadoid = h.tbempleadoid
                WHERE h.tbsolicitudencargoid = ?
                ORDER BY fecha, campo DESC, id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $idencargo, (int) $idencargo]);

        return $sentencia->fetchAll();
    }
}