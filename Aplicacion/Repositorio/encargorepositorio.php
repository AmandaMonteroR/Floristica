<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/encargo.php';

class encargorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbsolicitudencargoid), 0) + 1 AS siguiente FROM tbsolicitudencargo');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Encargo $encargo)
    {
        $encargo->setTbsolicitudencargoid($this->siguienteid());
        $encargo->setTbsolicitudencargoestado('Pendiente');
        $encargo->setTbsolicitudencargofecharegistro(date('Y-m-d'));

        $sql = 'INSERT INTO tbsolicitudencargo (tbsolicitudencargoid, tbarreglofloralid, tbempleadoid, tbsolicitudencargocantidad,
                tbsolicitudencargofechaentrega, tbsolicitudencargonota, tbsolicitudencargoestado, tbsolicitudencargofecharegistro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $encargo->getTbsolicitudencargoid(),
            $encargo->getTbarreglofloralid(),
            $encargo->getTbempleadoid(),
            $encargo->getTbsolicitudencargocantidad(),
            $encargo->getTbsolicitudencargofechaentrega(),
            $encargo->getTbsolicitudencargonota(),
            $encargo->getTbsolicitudencargoestado(),
            $encargo->getTbsolicitudencargofecharegistro(),
        ]);

        return $encargo->getTbsolicitudencargoid();
    }

    public function actualizar(Encargo $encargo)
    {
        $sql = 'UPDATE tbsolicitudencargo SET tbarreglofloralid = ?, tbsolicitudencargocantidad = ?, tbsolicitudencargofechaentrega = ?, tbsolicitudencargonota = ?
                WHERE tbsolicitudencargoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $encargo->getTbarreglofloralid(),
            $encargo->getTbsolicitudencargocantidad(),
            $encargo->getTbsolicitudencargofechaentrega(),
            $encargo->getTbsolicitudencargonota(),
            $encargo->getTbsolicitudencargoid(),
        ]);
    }

    public function asignar($id, $idempleado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbsolicitudencargo SET tbempleadoid = ? WHERE tbsolicitudencargoid = ?');
        return $sentencia->execute([$idempleado, $id]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbsolicitudencargo SET tbsolicitudencargoestado = ? WHERE tbsolicitudencargoid = ?');
        return $sentencia->execute([$estado, $id]);
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbsolicitudencargo WHERE tbsolicitudencargoid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Encargo($fila) : null;
    }

    public function listar($estado = '', $idempleado = '')
    {
        $sql = 'SELECT * FROM tbsolicitudencargo WHERE 1 = 1';
        $parametros = [];

        if ($estado === 'abiertos' || $estado === 'atrasados') {
            // Abiertos: los que todavía hay que preparar
            $sql .= " AND tbsolicitudencargoestado IN ('Pendiente', 'En proceso')";

            // Atrasados: abiertos con la fecha de entrega ya pasada
            if ($estado === 'atrasados') {
                $sql .= ' AND tbsolicitudencargofechaentrega < ?';
                $parametros[] = date('Y-m-d');
            }
        } elseif ($estado !== '') {
            $sql .= ' AND tbsolicitudencargoestado = ?';
            $parametros[] = $estado;
        }

        if ($idempleado === 'sinasignar') {
            $sql .= ' AND tbempleadoid IS NULL';
        } elseif ((int) $idempleado > 0) {
            $sql .= ' AND tbempleadoid = ?';
            $parametros[] = (int) $idempleado;
        }

        // Por número de encargo, de menor a mayor
        $sql .= ' ORDER BY tbsolicitudencargoid';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $encargos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $encargos[] = new Encargo($fila);
        }

        return $encargos;
    }

    public function contarabiertosporempleado($idempleado)
    {
        $sentencia = $this->conexion->prepare("SELECT COUNT(*) AS total FROM tbsolicitudencargo
                WHERE tbempleadoid = ? AND tbsolicitudencargoestado IN ('Pendiente', 'En proceso')");
        $sentencia->execute([(int) $idempleado]);

        return (int) $sentencia->fetch()['total'];
    }
}
