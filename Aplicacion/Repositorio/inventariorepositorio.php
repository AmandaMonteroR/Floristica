<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/inventario.php';

class inventariorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbinventarioid), 0) + 1 AS siguiente FROM tbinventario');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Inventario $inventario)
    {
        $inventario->setTbinventarioid($this->siguienteid());
        $inventario->setTbinventarioestado(1);

        $sql = 'INSERT INTO tbinventario (tbinventarioid, tbproveedorid, tbinventarionombre, tbinventariocategoria,
                tbinventariopreciopaquete, tbinventariounidadespaquete, tbinventariopreciounidad,
                tbinventariostock, tbinventariostockminimo, tbinventarioestado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $inventario->getTbinventarioid(),
            $inventario->getTbproveedorid(),
            $inventario->getTbinventarionombre(),
            $inventario->getTbinventariocategoria(),
            $inventario->getTbinventariopreciopaquete(),
            $inventario->getTbinventariounidadespaquete(),
            $inventario->getTbinventariopreciounidad(),
            $inventario->getTbinventariostock(),
            $inventario->getTbinventariostockminimo(),
            $inventario->getTbinventarioestado(),
        ]);

        return $inventario->getTbinventarioid();
    }

    public function actualizar(Inventario $inventario)
    {
        $sql = 'UPDATE tbinventario SET tbproveedorid = ?, tbinventarionombre = ?, tbinventariocategoria = ?,
                tbinventariopreciopaquete = ?, tbinventariounidadespaquete = ?, tbinventariopreciounidad = ?,
                tbinventariostock = ?, tbinventariostockminimo = ?
                WHERE tbinventarioid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $inventario->getTbproveedorid(),
            $inventario->getTbinventarionombre(),
            $inventario->getTbinventariocategoria(),
            $inventario->getTbinventariopreciopaquete(),
            $inventario->getTbinventariounidadespaquete(),
            $inventario->getTbinventariopreciounidad(),
            $inventario->getTbinventariostock(),
            $inventario->getTbinventariostockminimo(),
            $inventario->getTbinventarioid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventarioestado = ? WHERE tbinventarioid = ?');
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

    public function sumarstock($id, $unidades)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventariostock = tbinventariostock + ? WHERE tbinventarioid = ?');
        return $sentencia->execute([(int) $unidades, $id]);
    }

    public function restarstock($id, $unidades)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventariostock = tbinventariostock - ?
                WHERE tbinventarioid = ? AND tbinventariostock >= ?');
        $sentencia->execute([(int) $unidades, $id, (int) $unidades]);

        return $sentencia->rowCount() === 1;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbinventario WHERE tbinventarioid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Inventario($fila) : null;
    }

    public function listar($texto = '', $categoria = '', $estado = null, $idproveedor = 0)
    {
        $sql = 'SELECT * FROM tbinventario WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND tbinventarionombre LIKE ?';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($categoria !== '') {
            $sql .= ' AND tbinventariocategoria = ?';
            $parametros[] = $categoria;
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbinventarioestado = ?';
            $parametros[] = (int) $estado;
        }

        if ((int) $idproveedor > 0) {
            $sql .= ' AND tbproveedorid = ?';
            $parametros[] = (int) $idproveedor;
        }

        $sql .= ' ORDER BY tbinventarionombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $productos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $productos[] = new Inventario($fila);
        }

        return $productos;
    }

    public function existenombre($nombre, $idproveedor, $idexcluir = 0)
    {
        $sql = 'SELECT COUNT(*) AS total FROM tbinventario
                WHERE LOWER(TRIM(tbinventarionombre)) = LOWER(TRIM(?))
                AND tbproveedorid = ?
                AND tbinventarioid <> ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$nombre, (int) $idproveedor, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}