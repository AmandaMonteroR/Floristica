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
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbencargoid), 0) + 1 AS siguiente FROM tbencargo');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Encargo $encargo)
    {
        $encargo->setTbencargoid($this->siguienteid());
        $encargo->setTbencargoestado('Pendiente');
        $encargo->setTbencargofecharegistro(date('Y-m-d'));

        $sql = 'INSERT INTO tbencargo (tbencargoid, tbarreglofloralid, tbempleadoid, tbencargocantidad,
                tbencargofechaentrega, tbencargonota, tbencargoestado, tbencargofecharegistro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $encargo->getTbencargoid(),
            $encargo->getTbarreglofloralid(),
            $encargo->getTbempleadoid(),
            $encargo->getTbencargocantidad(),
            $encargo->getTbencargofechaentrega(),
            $encargo->getTbencargonota(),
            $encargo->getTbencargoestado(),
            $encargo->getTbencargofecharegistro(),
        ]);

        return $encargo->getTbencargoid();
    }

    public function actualizar(Encargo $encargo)
    {
        $sql = 'UPDATE tbencargo SET tbarreglofloralid = ?, tbencargocantidad = ?, tbencargofechaentrega = ?, tbencargonota = ?
                WHERE tbencargoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $encargo->getTbarreglofloralid(),
            $encargo->getTbencargocantidad(),
            $encargo->getTbencargofechaentrega(),
            $encargo->getTbencargonota(),
            $encargo->getTbencargoid(),
        ]);
    }

    public function asignar($id, $idempleado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbencargo SET tbempleadoid = ? WHERE tbencargoid = ?');
        return $sentencia->execute([$idempleado, $id]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbencargo SET tbencargoestado = ? WHERE tbencargoid = ?');
        return $sentencia->execute([$estado, $id]);
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbencargo WHERE tbencargoid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Encargo($fila) : null;
    }

    public function listar($estado = '', $idempleado = '')
    {
        $sql = 'SELECT * FROM tbencargo WHERE 1 = 1';
        $parametros = [];

        if ($estado === 'abiertos' || $estado === 'atrasados') {
            // Abiertos: los que todavía hay que preparar
            $sql .= " AND tbencargoestado IN ('Pendiente', 'En proceso')";

            // Atrasados: abiertos con la fecha de entrega ya pasada
            if ($estado === 'atrasados') {
                $sql .= ' AND tbencargofechaentrega < ?';
                $parametros[] = date('Y-m-d');
            }
        } elseif ($estado !== '') {
            $sql .= ' AND tbencargoestado = ?';
            $parametros[] = $estado;
        }

        if ($idempleado === 'sinasignar') {
            $sql .= ' AND tbempleadoid IS NULL';
        } elseif ((int) $idempleado > 0) {
            $sql .= ' AND tbempleadoid = ?';
            $parametros[] = (int) $idempleado;
        }

        // Por número de encargo, de menor a mayor
        $sql .= ' ORDER BY tbencargoid';

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
        $sentencia = $this->conexion->prepare("SELECT COUNT(*) AS total FROM tbencargo
                WHERE tbempleadoid = ? AND tbencargoestado IN ('Pendiente', 'En proceso')");
        $sentencia->execute([(int) $idempleado]);

        return (int) $sentencia->fetch()['total'];
    }
}
