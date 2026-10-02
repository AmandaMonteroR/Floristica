<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/proveedor.php';

class proveedorrepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbproveedorid), 0) + 1 AS siguiente FROM tbproveedor');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Proveedor $proveedor)
    {
        $proveedor->setTbproveedorid($this->siguienteid());
        $proveedor->setTbproveedorestado(1);
        $proveedor->setTbproveedorfecharegistro(date('Y-m-d'));

        $sql = 'INSERT INTO tbproveedor (tbproveedorid, tbproveedornombreempresa, tbproveedornombrecontacto,
                tbproveedortelefono, tbproveedorcorreo, tbproveedorprovincia, tbproveedorcanton,
                tbproveedordistrito, tbproveedordireccion, tbproveedordescripcion,
                tbproveedorestado, tbproveedorfecharegistro)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $proveedor->getTbproveedorid(),
            $proveedor->getTbproveedornombreempresa(),
            $proveedor->getTbproveedornombrecontacto(),
            $proveedor->getTbproveedortelefono(),
            $proveedor->getTbproveedorcorreo(),
            $proveedor->getTbproveedorprovincia(),
            $proveedor->getTbproveedorcanton(),
            $proveedor->getTbproveedordistrito(),
            $proveedor->getTbproveedordireccion(),
            $proveedor->getTbproveedordescripcion(),
            $proveedor->getTbproveedorestado(),
            $proveedor->getTbproveedorfecharegistro(),
        ]);

        return $proveedor->getTbproveedorid();
    }

    public function actualizar(Proveedor $proveedor)
    {
        $sql = 'UPDATE tbproveedor SET tbproveedornombreempresa = ?, tbproveedornombrecontacto = ?,
                tbproveedortelefono = ?, tbproveedorcorreo = ?, tbproveedorprovincia = ?,
                tbproveedorcanton = ?, tbproveedordistrito = ?, tbproveedordireccion = ?,
                tbproveedordescripcion = ?
                WHERE tbproveedorid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $proveedor->getTbproveedornombreempresa(),
            $proveedor->getTbproveedornombrecontacto(),
            $proveedor->getTbproveedortelefono(),
            $proveedor->getTbproveedorcorreo(),
            $proveedor->getTbproveedorprovincia(),
            $proveedor->getTbproveedorcanton(),
            $proveedor->getTbproveedordistrito(),
            $proveedor->getTbproveedordireccion(),
            $proveedor->getTbproveedordescripcion(),
            $proveedor->getTbproveedorid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbproveedor SET tbproveedorestado = ? WHERE tbproveedorid = ?');
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

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbproveedor WHERE tbproveedorid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Proveedor($fila) : null;
    }

    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbproveedor WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND (tbproveedornombreempresa LIKE ? OR tbproveedornombrecontacto LIKE ?)';
            $parametros[] = '%' . trim($texto) . '%';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbproveedorestado = ?';
            $parametros[] = (int) $estado;
        }

        $sql .= ' ORDER BY tbproveedornombreempresa';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $proveedores = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $proveedores[] = new Proveedor($fila);
        }

        return $proveedores;
    }

    public function existenombreempresa($nombre, $idexcluir = 0)
    {
        $sql = 'SELECT COUNT(*) AS total FROM tbproveedor
                WHERE LOWER(TRIM(tbproveedornombreempresa)) = LOWER(TRIM(?))
                AND tbproveedorid <> ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$nombre, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}