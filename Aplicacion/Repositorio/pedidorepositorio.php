<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/pedido.php';
require_once __DIR__ . '/../Modelo/pedidodetalle.php';

class pedidorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbpedidoid), 0) + 1 AS siguiente FROM tbpedido');
        return (int) $consulta->fetch()['siguiente'];
    }

    private function siguienteiddetalle()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbpedidodetalleid), 0) + 1 AS siguiente FROM tbpedidodetalle');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Pedido $pedido)
    {
        $pedido->setTbpedidoid($this->siguienteid());
        $pedido->setTbpedidoestado('Pendiente');

        $sql = 'INSERT INTO tbpedido (tbpedidoid, tbproveedorid, tbpedidofecha, tbpedidoestado)
                VALUES (?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $pedido->getTbpedidoid(),
            $pedido->getTbproveedorid(),
            $pedido->getTbpedidofecha(),
            $pedido->getTbpedidoestado(),
        ]);

        return $pedido->getTbpedidoid();
    }

    public function actualizar(Pedido $pedido)
    {
        $sql = 'UPDATE tbpedido SET tbproveedorid = ?, tbpedidofecha = ? WHERE tbpedidoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $pedido->getTbproveedorid(),
            $pedido->getTbpedidofecha(),
            $pedido->getTbpedidoid(),
        ]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbpedido SET tbpedidoestado = ? WHERE tbpedidoid = ?');
        return $sentencia->execute([$estado, $id]);
    }

    public function insertardetalle(Pedidodetalle $detalle)
    {
        $detalle->setTbpedidodetalleid($this->siguienteiddetalle());

        $sql = 'INSERT INTO tbpedidodetalle (tbpedidodetalleid, tbpedidoid, tbinventarioid,
                tbpedidodetallecantidad, tbpedidodetalleprecio)
                VALUES (?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $detalle->getTbpedidodetalleid(),
            $detalle->getTbpedidoid(),
            $detalle->getTbinventarioid(),
            $detalle->getTbpedidodetallecantidad(),
            $detalle->getTbpedidodetalleprecio(),
        ]);
    }

    public function eliminardetalles($idpedido)
    {
        $sentencia = $this->conexion->prepare('DELETE FROM tbpedidodetalle WHERE tbpedidoid = ?');
        return $sentencia->execute([$idpedido]);
    }

    public function listardetalles($idpedido)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbpedidodetalle WHERE tbpedidoid = ? ORDER BY tbpedidodetalleid');
        $sentencia->execute([$idpedido]);

        $detalles = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $detalles[] = new Pedidodetalle($fila);
        }

        return $detalles;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbpedido WHERE tbpedidoid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Pedido($fila) : null;
    }

    public function listar($idproveedor = 0, $estado = '')
    {
        $sql = 'SELECT * FROM tbpedido WHERE 1 = 1';
        $parametros = [];

        if ((int) $idproveedor > 0) {
            $sql .= ' AND tbproveedorid = ?';
            $parametros[] = (int) $idproveedor;
        }

        if ($estado !== '') {
            $sql .= ' AND tbpedidoestado = ?';
            $parametros[] = $estado;
        }

        $sql .= ' ORDER BY tbpedidoid';
        
        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $pedidos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $pedidos[] = new Pedido($fila);
        }

        return $pedidos;
    }

    public function total($idpedido)
    {
        $sql = 'SELECT IFNULL(SUM(tbpedidodetallecantidad * tbpedidodetalleprecio), 0) AS total
                FROM tbpedidodetalle WHERE tbpedidoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$idpedido]);

        return (float) $sentencia->fetch()['total'];
    }
}