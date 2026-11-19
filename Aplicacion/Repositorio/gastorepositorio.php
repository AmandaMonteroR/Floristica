<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/gasto.php';

class gastorepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbgastoid), 0) + 1 AS siguiente FROM tbgasto');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Gasto $gasto)
    {
        $gasto->setTbgastoid($this->siguienteid());
        $gasto->setTbgastoestado(1);

        $sql = 'INSERT INTO tbgasto (tbgastoid, tbgastocategoria, tbgastodescripcion, tbgastomonto, tbgastofecha,
                tbempleadoid, tbgastoestado, tbgastoorigen, tbgastoreferencia)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $gasto->getTbgastoid(),
            $gasto->getTbgastocategoria(),
            $gasto->getTbgastodescripcion(),
            $gasto->getTbgastomonto(),
            $gasto->getTbgastofecha(),
            $gasto->getTbempleadoid(),
            $gasto->getTbgastoestado(),
            $gasto->getTbgastoorigen(),
            $gasto->getTbgastoreferencia(),
        ]);

        return $gasto->getTbgastoid();
    }

    public function actualizar(Gasto $gasto)
    {
        $sql = 'UPDATE tbgasto SET tbgastocategoria = ?, tbgastodescripcion = ?, tbgastomonto = ?, tbgastofecha = ?
                WHERE tbgastoid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $gasto->getTbgastocategoria(),
            $gasto->getTbgastodescripcion(),
            $gasto->getTbgastomonto(),
            $gasto->getTbgastofecha(),
            $gasto->getTbgastoid(),
        ]);
    }

    public function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbgasto SET tbgastoestado = ? WHERE tbgastoid = ?');
        return $sentencia->execute([(int) $estado, (int) $id]);
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbgasto WHERE tbgastoid = ?');
        $sentencia->execute([(int) $id]);
        $fila = $sentencia->fetch();

        return $fila ? new Gasto($fila) : null;
    }

    public function obtenerpororigen($origen, $referencia)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbgasto WHERE tbgastoorigen = ? AND tbgastoreferencia = ?');
        $sentencia->execute([$origen, (int) $referencia]);
        $fila = $sentencia->fetch();

        return $fila ? new Gasto($fila) : null;
    }

    public function listar($desde, $hasta, $categoria = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbgasto WHERE tbgastofecha BETWEEN ? AND ?';
        $parametros = [$desde, $hasta];

        if ($categoria !== '') {
            $sql .= ' AND tbgastocategoria = ?';
            $parametros[] = $categoria;
        }
        if ($estado !== null) {
            $sql .= ' AND tbgastoestado = ?';
            $parametros[] = (int) $estado;
        }
        $sql .= ' ORDER BY tbgastofecha DESC, tbgastoid DESC';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $lista = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $lista[] = new Gasto($fila);
        }

        return $lista;
    }

    public function totalesporcategoria($desde, $hasta)
    {
        $sentencia = $this->conexion->prepare('SELECT tbgastocategoria AS categoria, SUM(tbgastomonto) AS total FROM tbgasto
                WHERE tbgastoestado = 1 AND tbgastofecha BETWEEN ? AND ? GROUP BY tbgastocategoria ORDER BY total DESC');
        $sentencia->execute([$desde, $hasta]);

        $totales = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $totales[$fila['categoria']] = (float) $fila['total'];
        }

        return $totales;
    }

    public function primerafecha()
    {
        $fecha = $this->conexion->query('SELECT MIN(tbgastofecha) AS fecha FROM tbgasto')->fetch()['fecha'];
        return $fecha ?: null;
    }

    public function existeigual($categoria, $descripcion, $monto, $fecha, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) AS total FROM tbgasto WHERE tbgastoestado = 1
                AND tbgastocategoria = ? AND tbgastodescripcion = ? AND tbgastomonto = ? AND tbgastofecha = ? AND tbgastoid <> ?');
        $sentencia->execute([$categoria, $descripcion, $monto, $fecha, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}