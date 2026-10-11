<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/arreglofloral.php';
require_once __DIR__ . '/../Modelo/arreglofloraldetalle.php';

class arreglofloralrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbarreglofloralid), 0) + 1 AS siguiente FROM tbarreglofloral');
        return (int) $consulta->fetch()['siguiente'];
    }

    private function siguienteiddetalle()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbarreglofloraldetalleid), 0) + 1 AS siguiente FROM tbarreglofloraldetalle');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Arreglofloral $arreglo)
    {
        $arreglo->setTbarreglofloralid($this->siguienteid());
        $arreglo->setTbarreglofloralestado(1);

        $sql = 'INSERT INTO tbarreglofloral (tbarreglofloralid, tbarreglofloralnombre, tbarreglofloraldescripcion,
                tbarreglofloralfoto, tbarreglofloralprecio, tbarreglofloralestado)
                VALUES (?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $arreglo->getTbarreglofloralid(),
            $arreglo->getTbarreglofloralnombre(),
            $arreglo->getTbarreglofloraldescripcion(),
            $arreglo->getTbarreglofloralfoto(),
            $arreglo->getTbarreglofloralprecio(),
            $arreglo->getTbarreglofloralestado(),
        ]);

        return $arreglo->getTbarreglofloralid();
    }

    public function actualizar(Arreglofloral $arreglo)
    {
        $sql = 'UPDATE tbarreglofloral SET tbarreglofloralnombre = ?, tbarreglofloraldescripcion = ?,
                tbarreglofloralfoto = ?, tbarreglofloralprecio = ?
                WHERE tbarreglofloralid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $arreglo->getTbarreglofloralnombre(),
            $arreglo->getTbarreglofloraldescripcion(),
            $arreglo->getTbarreglofloralfoto(),
            $arreglo->getTbarreglofloralprecio(),
            $arreglo->getTbarreglofloralid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbarreglofloral SET tbarreglofloralestado = ? WHERE tbarreglofloralid = ?');
        return $sentencia->execute([$estado, $id]);
    }

    public function inactivar($id)
    {
        return $this->cambiarestado($id, 0);
    }

    public function reactivar($id)
    {
        return $this->cambiarestado($id, 1);
    }

    public function insertardetalle(Arreglofloraldetalle $detalle)
    {
        $detalle->setTbarreglofloraldetalleid($this->siguienteiddetalle());

        $sql = 'INSERT INTO tbarreglofloraldetalle (tbarreglofloraldetalleid, tbarreglofloralid,
                tbinventarioid, tbarreglofloraldetallecantidad)
                VALUES (?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $detalle->getTbarreglofloraldetalleid(),
            $detalle->getTbarreglofloralid(),
            $detalle->getTbinventarioid(),
            $detalle->getTbarreglofloraldetallecantidad(),
        ]);
    }

    public function eliminardetalles($idarreglo)
    {
        $sentencia = $this->conexion->prepare('DELETE FROM tbarreglofloraldetalle WHERE tbarreglofloralid = ?');
        return $sentencia->execute([$idarreglo]);
    }

    public function listardetalles($idarreglo)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbarreglofloraldetalle WHERE tbarreglofloralid = ? ORDER BY tbarreglofloraldetalleid');
        $sentencia->execute([$idarreglo]);

        $detalles = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $detalles[] = new Arreglofloraldetalle($fila);
        }

        return $detalles;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbarreglofloral WHERE tbarreglofloralid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Arreglofloral($fila) : null;
    }

    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbarreglofloral WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND tbarreglofloralnombre LIKE ?';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbarreglofloralestado = ?';
            $parametros[] = (int) $estado;
        }

        $sql .= ' ORDER BY tbarreglofloralnombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $arreglos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $arreglos[] = new Arreglofloral($fila);
        }

        return $arreglos;
    }

    public function existenombre($nombre, $idexcluir = 0)
    {
        $sql = 'SELECT COUNT(*) AS total FROM tbarreglofloral
                WHERE LOWER(TRIM(tbarreglofloralnombre)) = LOWER(TRIM(?))
                AND tbarreglofloralid <> ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$nombre, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}