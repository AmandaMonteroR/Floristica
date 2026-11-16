<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/inventario.php';

class inventariorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbinventarioid), 0) + 1 AS siguiente FROM tbinventario');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Inventario $inventario)
    {
        $inventario->setTbinventarioid($this->siguienteid());
        $inventario->setTbinventarioestado(1);

        $sql = 'INSERT INTO tbinventario (tbinventarioid, tbproveedorid, tbinventarionombre, tbinventariocategoria,
                tbinventariopreciopaquete, tbinventariounidadespaquete, tbinventariopreciounidad,
                tbinventarioprecioventa, tbinventariostock, tbinventariostockminimo, tbinventarioestado)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $inventario->getTbinventarioid(),
            $inventario->getTbproveedorid(),
            $inventario->getTbinventarionombre(),
            $inventario->getTbinventariocategoria(),
            $inventario->getTbinventariopreciopaquete(),
            $inventario->getTbinventariounidadespaquete(),
            $inventario->getTbinventariopreciounidad(),
            $inventario->getTbinventarioprecioventa(),
            $inventario->getTbinventariostock(),
            $inventario->getTbinventariostockminimo(),
            $inventario->getTbinventarioestado(),
        ]);

        return $inventario->getTbinventarioid();
    }

    public function actualizar(Inventario $inventario)
    {
        $sql = 'UPDATE tbinventario SET tbproveedorid = ?, tbinventarionombre = ?, tbinventariocategoria = ?,
                tbinventariopreciopaquete = ?, tbinventariounidadespaquete = ?, tbinventariopreciounidad = ?,
                tbinventarioprecioventa = ?, tbinventariostock = ?, tbinventariostockminimo = ?
                WHERE tbinventarioid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $inventario->getTbproveedorid(),
            $inventario->getTbinventarionombre(),
            $inventario->getTbinventariocategoria(),
            $inventario->getTbinventariopreciopaquete(),
            $inventario->getTbinventariounidadespaquete(),
            $inventario->getTbinventariopreciounidad(),
            $inventario->getTbinventarioprecioventa(),
            $inventario->getTbinventariostock(),
            $inventario->getTbinventariostockminimo(),
            $inventario->getTbinventarioid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventarioestado = ? WHERE tbinventarioid = ?');
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

    public function sumarstock($id, $unidades)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventariostock = tbinventariostock + ? WHERE tbinventarioid = ?');
        return $sentencia->execute([(int) $unidades, $id]);
    }

    public function restarstock($id, $unidades)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbinventario SET tbinventariostock = tbinventariostock - ?
                WHERE tbinventarioid = ? AND tbinventariostock >= ?');
        $sentencia->execute([(int) $unidades, $id, (int) $unidades]);

        return $sentencia->rowCount() === 1;
    }

    // Stock reservado ("previsibilidad"): se vuelve a calcular con todas las solicitudes abiertas
    // (pendientes o en proceso). Predefinidas: la receta del arreglo × cantidad; las dos: sus productos.
    // Se llama cada vez que se crea, edita, cancela o termina una solicitud.
    public function recalcularreservado()
    {
        $sql = "UPDATE tbinventario i SET tbinventariostockreservado = COALESCE((
                    SELECT SUM(x.cantidad) FROM (
                        SELECT d.tbinventarioid, d.tbarreglofloralinsumocantidad * s.tbsolicitudencargocantidad AS cantidad
                        FROM tbsolicitudencargo s
                        INNER JOIN tbarreglofloralinsumo d ON d.tbarreglofloralid = s.tbarreglofloralid
                        WHERE s.tbsolicitudencargoestado IN ('Pendiente', 'En proceso') AND s.tbsolicitudencargotipo = 'Predefinido'
                        UNION ALL
                        SELECT n.tbinventarioid, n.tbsolicitudencargoinsumocantidad AS cantidad
                        FROM tbsolicitudencargo s
                        INNER JOIN tbsolicitudencargoinsumo n ON n.tbsolicitudencargoid = s.tbsolicitudencargoid
                        WHERE s.tbsolicitudencargoestado IN ('Pendiente', 'En proceso')
                    ) x WHERE x.tbinventarioid = i.tbinventarioid), 0)";

        return $this->conexion->exec($sql) !== false;
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbinventario WHERE tbinventarioid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Inventario($fila) : null;
    }

    public function listar($texto = '', $categoria = '', $estado = null, $idproveedor = 0)
    {
        $sql = 'SELECT * FROM tbinventario WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND tbinventarionombre LIKE ?';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($categoria !== '') {
            $sql .= ' AND tbinventariocategoria = ?';
            $parametros[] = $categoria;
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbinventarioestado = ?';
            $parametros[] = (int) $estado;
        }

        if ((int) $idproveedor > 0) {
            $sql .= ' AND tbproveedorid = ?';
            $parametros[] = (int) $idproveedor;
        }

        $sql .= ' ORDER BY tbinventarionombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $productos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $productos[] = new Inventario($fila);
        }

        return $productos;
    }

    // Un insumo se registra una sola vez, sin importar el proveedor (los demás van en "Otros proveedores").
    // Devuelve el que tiene el mismo nombre (sin distinguir mayúsculas ni tildes), o null.
    // Se compara en PHP para que funcione igual con cualquier configuración de la base.
    public function buscarpornombre($nombre, $idexcluir = 0)
    {
        $sql = 'SELECT i.tbinventarioid, i.tbinventarionombre, i.tbinventarioestado, p.tbproveedornombreempresa
                FROM tbinventario i
                LEFT JOIN tbproveedor p ON p.tbproveedorid = i.tbproveedorid
                WHERE i.tbinventarioid <> ?
                ORDER BY i.tbinventarioestado DESC, i.tbinventarioid';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $idexcluir]);

        $buscado = self::normalizar($nombre);
        foreach ($sentencia->fetchAll() as $fila) {
            if (self::normalizar($fila['tbinventarionombre']) === $buscado) {
                return $fila;
            }
        }

        return null;
    }

    // "  Rósa  ROJA " -> "rosa roja"
    public static function normalizar($texto)
    {
        $texto = mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $texto)), 'UTF-8');
        return strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u']);
    }
}
