<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/empleado.php';

class empleadorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbempleadoid), 0) + 1 AS siguiente FROM tbempleado');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Empleado $empleado)
    {
        $empleado->setTbempleadoid($this->siguienteid());
        $empleado->setTbempleadoestado(1);

        $sql = 'INSERT INTO tbempleado (tbempleadoid, tbempleadonombre, tbempleadotelefono, tbempleadorol, tbempleadoestado)
                VALUES (?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $empleado->getTbempleadoid(),
            $empleado->getTbempleadonombre(),
            $empleado->getTbempleadotelefono(),
            $empleado->getTbempleadorol(),
            $empleado->getTbempleadoestado(),
        ]);

        return $empleado->getTbempleadoid();
    }

    public function actualizar(Empleado $empleado)
    {
        $sql = 'UPDATE tbempleado SET tbempleadonombre = ?, tbempleadotelefono = ?, tbempleadorol = ?
                WHERE tbempleadoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $empleado->getTbempleadonombre(),
            $empleado->getTbempleadotelefono(),
            $empleado->getTbempleadorol(),
            $empleado->getTbempleadoid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbempleado SET tbempleadoestado = ? WHERE tbempleadoid = ?');
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
        $sentencia = $this->conexion->prepare('SELECT * FROM tbempleado WHERE tbempleadoid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Empleado($fila) : null;
    }

    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbempleado WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND tbempleadonombre LIKE ?';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbempleadoestado = ?';
            $parametros[] = (int) $estado;
        }

        // La dueña aparece primero
        $sql .= " ORDER BY tbempleadorol = 'Dueña' DESC, tbempleadonombre";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $empleados = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $empleados[] = new Empleado($fila);
        }

        return $empleados;
    }

    public function obtenerduena()
    {
        $sentencia = $this->conexion->query("SELECT * FROM tbempleado WHERE tbempleadorol = 'Dueña' AND tbempleadoestado = 1 LIMIT 1");
        $fila = $sentencia->fetch();

        return $fila ? new Empleado($fila) : null;
    }

    public function existetelefono($telefono, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) AS total FROM tbempleado WHERE tbempleadotelefono = ? AND tbempleadoid <> ?');
        $sentencia->execute([$telefono, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }

    public function existeduenaactiva($idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare("SELECT COUNT(*) AS total FROM tbempleado
                WHERE tbempleadorol = 'Dueña' AND tbempleadoestado = 1 AND tbempleadoid <> ?");
        $sentencia->execute([(int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}
