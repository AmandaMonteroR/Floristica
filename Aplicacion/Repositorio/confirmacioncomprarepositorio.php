<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/confirmacioncompra.php';

class confirmacioncomprarepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbconfirmacioncompraid), 0) + 1 AS siguiente FROM tbconfirmacioncompra');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Confirmacioncompra $confirmacion)
    {
        $confirmacion->setTbconfirmacioncompraid($this->siguienteid());
        $confirmacion->setTbconfirmacioncomprafecha(date('Y-m-d'));

        $sql = 'INSERT INTO tbconfirmacioncompra (tbconfirmacioncompraid, tbpedidoid, tbconfirmacioncomprafecha,
                tbconfirmacioncompratipopago, tbconfirmacioncompramonto, tbconfirmacioncompraplazodias)
                VALUES (?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $confirmacion->getTbconfirmacioncompraid(),
            $confirmacion->getTbpedidoid(),
            $confirmacion->getTbconfirmacioncomprafecha(),
            $confirmacion->getTbconfirmacioncompratipopago(),
            $confirmacion->getTbconfirmacioncompramonto(),
            $confirmacion->getTbconfirmacioncompraplazodias(),
        ]);

        return $confirmacion->getTbconfirmacioncompraid();
    }

    public function obtenerporpedido($idpedido)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbconfirmacioncompra WHERE tbpedidoid = ?');
        $sentencia->execute([$idpedido]);
        $fila = $sentencia->fetch();

        return $fila ? new Confirmacioncompra($fila) : null;
    }
}