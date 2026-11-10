<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/color.php';

// Catálogo de colores
class colorrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbcolorid), 0) + 1 AS siguiente FROM tbcolor');
        return (int) $consulta->fetch()['siguiente'];
    }

    // $estado: null = todos, 1 = activos, 0 = inactivos
    public function listar($estado = null)
    {
        $sql = 'SELECT * FROM tbcolor';
        $parametros = [];
        if ($estado !== null) {
            $sql .= ' WHERE tbcolorestado = ?';
            $parametros[] = (int) $estado;
        }
        $sql .= ' ORDER BY tbcolornombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $lista = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $lista[] = new Color($fila);
        }
        return $lista;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbcolor WHERE tbcolorid = ?');
        $sentencia->execute([(int) $id]);
        $fila = $sentencia->fetch();
        return $fila ? new Color($fila) : null;
    }

    public function existenombre($nombre, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) FROM tbcolor WHERE LOWER(tbcolornombre) = LOWER(?) AND tbcolorid <> ?');
        $sentencia->execute([$nombre, (int) $idexcluir]);
        return (int) $sentencia->fetchColumn() > 0;
    }

    public function insertar(Color $color)
    {
        $color->setTbcolorid($this->siguienteid());
        $sentencia = $this->conexion->prepare('INSERT INTO tbcolor (tbcolorid, tbcolornombre, tbcolorestado) VALUES (?, ?, 1)');
        $sentencia->execute([$color->getTbcolorid(), $color->getTbcolornombre()]);
        return $color->getTbcolorid();
    }

    public function actualizar(Color $color)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbcolor SET tbcolornombre = ? WHERE tbcolorid = ?');
        return $sentencia->execute([$color->getTbcolornombre(), $color->getTbcolorid()]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbcolor SET tbcolorestado = ? WHERE tbcolorid = ?');
        return $sentencia->execute([(int) $estado, (int) $id]);
    }

    // En cuántos productos del inventario se usa el color
    public function usos($id)
    {
        $total = 0;
        foreach (['flor', 'envoltura', 'base', 'peluche', 'tarjeta', 'spray', 'accesorio'] as $categoria) {
            $sentencia = $this->conexion->prepare("SELECT COUNT(*) FROM tbinventariocategoria{$categoria} WHERE tbcolorid = ?");
            $sentencia->execute([(int) $id]);
            $total += (int) $sentencia->fetchColumn();
        }
        return $total;
    }
}
