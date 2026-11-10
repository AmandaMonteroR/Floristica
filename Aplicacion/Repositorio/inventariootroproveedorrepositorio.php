<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/inventariootroproveedor.php';

// Otros proveedores de cada insumo (tbinventariootroproveedor): a quién más se le puede comprar
// el mismo paquete y a qué precio. El proveedor principal está en tbinventario.
class inventariootroproveedorrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbinventariootroproveedorid), 0) + 1 AS siguiente FROM tbinventariootroproveedor');
        return (int) $consulta->fetch()['siguiente'];
    }

    // Otros proveedores de un insumo: [idproveedor => precio del paquete]
    public function listarporinsumo($idinventario)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbinventariootroproveedor WHERE tbinventarioid = ? ORDER BY tbinventariootroproveedorid');
        $sentencia->execute([(int) $idinventario]);

        $precios = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $otro = new Inventariootroproveedor($fila);
            $precios[(int) $otro->getTbproveedorid()] = (float) $otro->getTbinventariootroproveedorpreciopaquete();
        }

        return $precios;
    }

    // Insumos que también vende un proveedor: [idinsumo => precio del paquete]
    public function listarporproveedor($idproveedor)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbinventariootroproveedor WHERE tbproveedorid = ?');
        $sentencia->execute([(int) $idproveedor]);

        $precios = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $otro = new Inventariootroproveedor($fila);
            $precios[(int) $otro->getTbinventarioid()] = (float) $otro->getTbinventariootroproveedorpreciopaquete();
        }

        return $precios;
    }

    // Cuántos otros proveedores tiene cada insumo: [idinsumo => cantidad]
    public function contar()
    {
        $conteos = [];
        $sql = 'SELECT tbinventarioid, COUNT(*) AS total FROM tbinventariootroproveedor GROUP BY tbinventarioid';
        foreach ($this->conexion->query($sql) as $fila) {
            $conteos[(int) $fila['tbinventarioid']] = (int) $fila['total'];
        }

        return $conteos;
    }

    // Guarda o cambia el precio de un proveedor para un insumo
    public function guardar($idinventario, $idproveedor, $precio)
    {
        $this->eliminar($idinventario, $idproveedor);

        $otro = new Inventariootroproveedor([
            'tbinventariootroproveedorid'            => $this->siguienteid(),
            'tbinventarioid'                         => (int) $idinventario,
            'tbproveedorid'                          => (int) $idproveedor,
            'tbinventariootroproveedorpreciopaquete' => $precio,
        ]);

        $sql = 'INSERT INTO tbinventariootroproveedor (tbinventariootroproveedorid, tbinventarioid, tbproveedorid,
                tbinventariootroproveedorpreciopaquete) VALUES (?, ?, ?, ?)';
        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            $otro->getTbinventariootroproveedorid(),
            $otro->getTbinventarioid(),
            $otro->getTbproveedorid(),
            $otro->getTbinventariootroproveedorpreciopaquete(),
        ]);
    }

    public function eliminar($idinventario, $idproveedor)
    {
        $sentencia = $this->conexion->prepare('DELETE FROM tbinventariootroproveedor WHERE tbinventarioid = ? AND tbproveedorid = ?');

        return $sentencia->execute([(int) $idinventario, (int) $idproveedor]);
    }
}
