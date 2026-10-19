<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/encargo.php';
require_once __DIR__ . '/../Modelo/solicitudencargoinsumo.php';

// Solicitudes de pedido de arreglo (tbsolicitudencargo) y sus productos (tbsolicitudencargoinsumo)
class encargorepositorio
{
    private $conexion;

    // Columnas que se guardan al crear y al editar (en ese orden)
    private $columnas = [
        'tbclienteid', 'tbsolicitudencargotipo', 'tbarreglofloralid', 'tbsolicitudencargocantidad',
        'tbsolicitudencargoprecioarreglo', 'tbsolicitudencargomodalidad', 'tbsolicitudencargopresupuesto',
        'tbsolicitudencargoexpress', 'tbzonaentregaid', 'tbsolicitudencargodireccionentrega',
        'tbsolicitudencargocostoexpress', 'tbsolicitudencargosubtotal', 'tbsolicitudencargototal',
        'tbsolicitudencargofechaentrega', 'tbsolicitudencargonota', 'tbtemporadaid',
        'tbsolicitudencargosprayid', 'tbsolicitudencargoflorespintadas', 'tbsolicitudencargopreciopintura',
        'tbsolicitudencargocostopintura', 'tbsolicitudencargoporcentaje', 'tbsolicitudencargocosto',
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    private function valores(Encargo $encargo)
    {
        $valores = [];
        foreach ($this->columnas as $columna) {
            $valores[] = $encargo->{'get' . ucfirst($columna)}();
        }

        return $valores;
    }

    public function insertar(Encargo $encargo)
    {
        $encargo->setTbsolicitudencargoid($this->siguienteid('tbsolicitudencargo'));
        $encargo->setTbsolicitudencargoestado('Pendiente');
        $encargo->setTbsolicitudencargofecharegistro(date('Y-m-d'));

        $columnas = array_merge(['tbsolicitudencargoid', 'tbempleadoid'], $this->columnas,
            ['tbsolicitudencargoestado', 'tbsolicitudencargofecharegistro']);
        $sql = 'INSERT INTO tbsolicitudencargo (' . implode(', ', $columnas) . ')
                VALUES (' . implode(', ', array_fill(0, count($columnas), '?')) . ')';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute(array_merge(
            [$encargo->getTbsolicitudencargoid(), $encargo->getTbempleadoid()],
            $this->valores($encargo),
            [$encargo->getTbsolicitudencargoestado(), $encargo->getTbsolicitudencargofecharegistro()]
        ));

        return $encargo->getTbsolicitudencargoid();
    }

    public function actualizar(Encargo $encargo)
    {
        $sql = 'UPDATE tbsolicitudencargo SET ' . implode(' = ?, ', $this->columnas) . ' = ?
                WHERE tbsolicitudencargoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute(array_merge($this->valores($encargo), [$encargo->getTbsolicitudencargoid()]));
    }

    /* ---------- Productos de la solicitud ---------- */

    // Borra los productos anteriores y guarda los nuevos
    public function guardarinsumos($idsolicitud, $insumos)
    {
        $sentencia = $this->conexion->prepare('DELETE FROM tbsolicitudencargoinsumo WHERE tbsolicitudencargoid = ?');
        $sentencia->execute([(int) $idsolicitud]);

        $sql = 'INSERT INTO tbsolicitudencargoinsumo (tbsolicitudencargoinsumoid, tbsolicitudencargoid, tbinventarioid,
                tbsolicitudencargoinsumocantidad, tbsolicitudencargoinsumopreciounitario)
                VALUES (?, ?, ?, ?, ?)';
        $sentencia = $this->conexion->prepare($sql);

        foreach ($insumos as $insumo) {
            $sentencia->execute([
                $this->siguienteid('tbsolicitudencargoinsumo'),
                (int) $idsolicitud,
                $insumo->getTbinventarioid(),
                $insumo->getTbsolicitudencargoinsumocantidad(),
                $insumo->getTbsolicitudencargoinsumopreciounitario(),
            ]);
        }
    }

    public function listarinsumos($idsolicitud)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbsolicitudencargoinsumo
                WHERE tbsolicitudencargoid = ? ORDER BY tbsolicitudencargoinsumoid');
        $sentencia->execute([(int) $idsolicitud]);

        $insumos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $insumos[] = new Solicitudencargoinsumo($fila);
        }

        return $insumos;
    }

    /* ---------- Asignación y estado ---------- */

    public function asignar($id, $idempleado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbsolicitudencargo SET tbempleadoid = ? WHERE tbsolicitudencargoid = ?');
        return $sentencia->execute([$idempleado, $id]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbsolicitudencargo SET tbsolicitudencargoestado = ? WHERE tbsolicitudencargoid = ?');
        return $sentencia->execute([$estado, $id]);
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbsolicitudencargo WHERE tbsolicitudencargoid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Encargo($fila) : null;
    }

    public function listar($estado = '', $idempleado = '', $tipo = '')
    {
        $sql = 'SELECT * FROM tbsolicitudencargo WHERE 1 = 1';
        $parametros = [];

        if ($estado === 'abiertos' || $estado === 'atrasados') {
            // Abiertas: las que todavía hay que preparar
            $sql .= " AND tbsolicitudencargoestado IN ('Pendiente', 'En proceso')";

            // Atrasadas: abiertas con la fecha de entrega ya pasada
            if ($estado === 'atrasados') {
                $sql .= ' AND tbsolicitudencargofechaentrega < ?';
                $parametros[] = date('Y-m-d');
            }
        } elseif ($estado !== '') {
            $sql .= ' AND tbsolicitudencargoestado = ?';
            $parametros[] = $estado;
        }

        if ($idempleado === 'sinasignar') {
            $sql .= ' AND tbempleadoid IS NULL';
        } elseif ((int) $idempleado > 0) {
            $sql .= ' AND tbempleadoid = ?';
            $parametros[] = (int) $idempleado;
        }

        if ($tipo === 'Predefinido' || $tipo === 'Personalizado') {
            $sql .= ' AND tbsolicitudencargotipo = ?';
            $parametros[] = $tipo;
        }

        // Por número de solicitud, de menor a mayor
        $sql .= ' ORDER BY tbsolicitudencargoid';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $encargos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $encargos[] = new Encargo($fila);
        }

        return $encargos;
    }

    public function contarabiertosporempleado($idempleado)
    {
        $sentencia = $this->conexion->prepare("SELECT COUNT(*) AS total FROM tbsolicitudencargo
                WHERE tbempleadoid = ? AND tbsolicitudencargoestado IN ('Pendiente', 'En proceso')");
        $sentencia->execute([(int) $idempleado]);

        return (int) $sentencia->fetch()['total'];
    }
}
