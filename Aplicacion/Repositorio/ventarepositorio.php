<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/encargo.php';

// Ventas: las solicitudes terminadas. La fecha de la venta es cuando se marcó como "Terminado".
class ventarepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // Devuelve [['encargo' => Encargo, 'fecha' => '2026-10-11 11:40:00'], ...], de la más reciente a la más antigua
    public function listar($desde, $hasta)
    {
        $sql = "SELECT s.*, h.fechaventa
                FROM tbsolicitudencargo s
                INNER JOIN (
                    SELECT tbsolicitudencargoid, MAX(tbsolicitudencargoestadohistoricofecha) AS fechaventa
                    FROM tbsolicitudencargoestadohistorico
                    WHERE tbsolicitudencargoestadohistoricovalor = 'Terminado'
                    GROUP BY tbsolicitudencargoid
                ) h ON h.tbsolicitudencargoid = s.tbsolicitudencargoid
                WHERE s.tbsolicitudencargoestado = 'Terminado'
                  AND DATE(h.fechaventa) BETWEEN ? AND ?
                ORDER BY h.fechaventa DESC, s.tbsolicitudencargoid DESC";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$desde, $hasta]);

        $ventas = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $ventas[] = ['encargo' => new Encargo($fila), 'fecha' => $fila['fechaventa']];
        }

        return $ventas;
    }

    // Fecha de la primera venta (para el filtro "Todo")
    public function primerafecha()
    {
        $consulta = $this->conexion->query("SELECT MIN(DATE(tbsolicitudencargoestadohistoricofecha)) AS fecha
                FROM tbsolicitudencargoestadohistorico WHERE tbsolicitudencargoestadohistoricovalor = 'Terminado'");
        return $consulta->fetch()['fecha'] ?? null;
    }
}
