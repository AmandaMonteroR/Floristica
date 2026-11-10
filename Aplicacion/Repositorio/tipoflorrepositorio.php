<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/tipoflor.php';

// Catálogo de tipos de flor y de follaje
class tipoflorrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbtipoflorid), 0) + 1 AS siguiente FROM tbtipoflor');
        return (int) $consulta->fetch()['siguiente'];
    }

    // $estado: null = todos, 1 = activos, 0 = inactivos
    public function listar($estado = null)
    {
        $sql = 'SELECT * FROM tbtipoflor';
        $parametros = [];
        if ($estado !== null) {
            $sql .= ' WHERE tbtipoflorestado = ?';
            $parametros[] = (int) $estado;
        }
        $sql .= ' ORDER BY tbtipoflorclase, tbtipoflornombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $lista = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $lista[] = new Tipoflor($fila);
        }
        return $lista;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbtipoflor WHERE tbtipoflorid = ?');
        $sentencia->execute([(int) $id]);
        $fila = $sentencia->fetch();
        return $fila ? new Tipoflor($fila) : null;
    }

    public function existenombre($nombre, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM tbtipoflor WHERE LOWER(tbtipoflornombre) = LOWER(?) AND tbtipoflorid <> ?');
        $sentencia->execute([$nombre, (int) $idexcluir]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    public function insertar(Tipoflor $tipo)
    {
        $tipo->setTbtipoflorid($this->siguienteid());
        $sentencia = $this->conexion->prepare('INSERT INTO tbtipoflor (tbtipoflorid, tbtipoflornombre, tbtipoflorclase, tbtipoflorestado)
                                               VALUES (?, ?, ?, 1)');
        $sentencia->execute([$tipo->getTbtipoflorid(), $tipo->getTbtipoflornombre(), $tipo->getTbtipoflorclase()]);
        return $tipo->getTbtipoflorid();
    }

    public function actualizar(Tipoflor $tipo)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbtipoflor SET tbtipoflornombre = ?, tbtipoflorclase = ? WHERE tbtipoflorid = ?');
        return $sentencia->execute([$tipo->getTbtipoflornombre(), $tipo->getTbtipoflorclase(), $tipo->getTbtipoflorid()]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbtipoflor SET tbtipoflorestado = ? WHERE tbtipoflorid = ?');
        return $sentencia->execute([(int) $estado, (int) $id]);
    }

    // En cuántos productos del inventario se usa el tipo
    public function usos($id)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM tbinventariocategoriaflor WHERE tbtipoflorid = ?');
        $sentencia->execute([(int) $id]);
        return (int) $sentencia->fetchColumn();
    }
}
