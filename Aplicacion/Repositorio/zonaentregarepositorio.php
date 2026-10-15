<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/zonaentrega.php';

class zonaentregarepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbzonaentregaid), 0) + 1 AS siguiente FROM tbzonaentrega');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Zonaentrega $zona)
    {
        $zona->setTbzonaentregaid($this->siguienteid());
        $zona->setTbzonaentregaestado(1);

        $sql = 'INSERT INTO tbzonaentrega (tbzonaentregaid, tbzonaentreganombre, tbzonaentregadescripcion,
                tbzonaentregaprecio, tbzonaentregaestado)
                VALUES (?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $zona->getTbzonaentregaid(),
            $zona->getTbzonaentreganombre(),
            $zona->getTbzonaentregadescripcion(),
            $zona->getTbzonaentregaprecio(),
            $zona->getTbzonaentregaestado(),
        ]);

        return $zona->getTbzonaentregaid();
    }

    public function actualizar(Zonaentrega $zona)
    {
        $sql = 'UPDATE tbzonaentrega SET tbzonaentreganombre = ?, tbzonaentregadescripcion = ?, tbzonaentregaprecio = ?
                WHERE tbzonaentregaid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $zona->getTbzonaentreganombre(),
            $zona->getTbzonaentregadescripcion(),
            $zona->getTbzonaentregaprecio(),
            $zona->getTbzonaentregaid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbzonaentrega SET tbzonaentregaestado = ? WHERE tbzonaentregaid = ?');
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
        $sentencia = $this->conexion->prepare('SELECT * FROM tbzonaentrega WHERE tbzonaentregaid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Zonaentrega($fila) : null;
    }

    // De la más barata a la más cara (de la más cercana a la más lejana)
    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbzonaentrega WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND (tbzonaentreganombre LIKE ? OR tbzonaentregadescripcion LIKE ?)';
            $parametros[] = '%' . trim($texto) . '%';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbzonaentregaestado = ?';
            $parametros[] = (int) $estado;
        }

        $sql .= ' ORDER BY tbzonaentregaprecio, tbzonaentreganombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $zonas = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $zonas[] = new Zonaentrega($fila);
        }

        return $zonas;
    }

    public function existenombre($nombre, $idexcluir = 0)
    {
        $sql = 'SELECT COUNT(*) AS total FROM tbzonaentrega
                WHERE LOWER(TRIM(tbzonaentreganombre)) = LOWER(TRIM(?)) AND tbzonaentregaid <> ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$nombre, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }

    // Solicitudes sin terminar ni cancelar que usan la zona (no se puede inactivar si hay)
    public function solicitudesabiertas($id)
    {
        $sql = "SELECT COUNT(*) AS total FROM tbsolicitudencargo
                WHERE tbzonaentregaid = ? AND tbsolicitudencargoestado IN ('Pendiente', 'En proceso')";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $id]);

        return (int) $sentencia->fetch()['total'];
    }
}
