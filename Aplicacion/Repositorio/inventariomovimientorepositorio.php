<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

class inventariomovimientorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbinventariomovimientoid), 0) + 1 AS siguiente FROM tbinventariomovimiento');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function registrar($idinventario, $tipo, $cantidad, $idempleado, $idpedido = null, $idencargo = null)
    {
        $consulta = $this->conexion->prepare('SELECT tbinventariostock FROM tbinventario WHERE tbinventarioid = ?');
        $consulta->execute([(int) $idinventario]);
        $stockfinal = (int) $consulta->fetchColumn();

        $sql = 'INSERT INTO tbinventariomovimiento (tbinventariomovimientoid, tbinventarioid, tbinventariomovimientotipo,
                tbinventariomovimientocantidad, tbinventariomovimientostockfinal, tbpedidoid, tbencargoid, tbempleadoid,
                tbinventariomovimientofecha)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid(),
            (int) $idinventario,
            $tipo,
            (int) $cantidad,
            $stockfinal,
            $idpedido,
            $idencargo,
            $idempleado,
            date('Y-m-d H:i:s'),
        ]);
    }

    public function listar($idinventario, $limite = 20)
    {
        $sql = 'SELECT m.*, e.tbempleadonombre AS hechopor
                FROM tbinventariomovimiento m
                LEFT JOIN tbempleado e ON e.tbempleadoid = m.tbempleadoid
                WHERE m.tbinventarioid = ?
                ORDER BY m.tbinventariomovimientofecha DESC, m.tbinventariomovimientoid DESC
                LIMIT ' . (int) $limite;

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $idinventario]);

        return $sentencia->fetchAll();
    }
}