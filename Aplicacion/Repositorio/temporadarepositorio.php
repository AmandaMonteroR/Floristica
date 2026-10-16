<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/temporada.php';

class temporadarepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbtemporadaid), 0) + 1 AS siguiente FROM tbtemporada');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Temporada $temporada)
    {
        $temporada->setTbtemporadaid($this->siguienteid());
        $temporada->setTbtemporadavigente(0);
        $temporada->setTbtemporadaestado(1);

        $sql = 'INSERT INTO tbtemporada (tbtemporadaid, tbtemporadanombre, tbtemporadadescripcion,
                tbtemporadavigente, tbtemporadaestado)
                VALUES (?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $temporada->getTbtemporadaid(),
            $temporada->getTbtemporadanombre(),
            $temporada->getTbtemporadadescripcion(),
            $temporada->getTbtemporadavigente(),
            $temporada->getTbtemporadaestado(),
        ]);

        return $temporada->getTbtemporadaid();
    }

    public function actualizar(Temporada $temporada)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbtemporada SET tbtemporadanombre = ?, tbtemporadadescripcion = ?
                WHERE tbtemporadaid = ?');

        return $sentencia->execute([
            $temporada->getTbtemporadanombre(),
            $temporada->getTbtemporadadescripcion(),
            $temporada->getTbtemporadaid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbtemporada SET tbtemporadaestado = ? WHERE tbtemporadaid = ?');
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

    // Deja vigente solo esta temporada (o ninguna si $id es null)
    public function hacervigente($id)
    {
        $this->conexion->exec('UPDATE tbtemporada SET tbtemporadavigente = 0');

        if ($id !== null) {
            $sentencia = $this->conexion->prepare('UPDATE tbtemporada SET tbtemporadavigente = 1 WHERE tbtemporadaid = ?');
            $sentencia->execute([(int) $id]);
        }
    }

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbtemporada WHERE tbtemporadaid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Temporada($fila) : null;
    }

    // La temporada cuyos precios se están usando, o null si se usan los precios normales
    public function obtenervigente()
    {
        $consulta = $this->conexion->query('SELECT * FROM tbtemporada WHERE tbtemporadavigente = 1 AND tbtemporadaestado = 1 LIMIT 1');
        $fila = $consulta->fetch();

        return $fila ? new Temporada($fila) : null;
    }

    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbtemporada WHERE 1 = 1';
        $parametros = [];

        if (trim($texto) !== '') {
            $sql .= ' AND (tbtemporadanombre LIKE ? OR tbtemporadadescripcion LIKE ?)';
            $parametros[] = '%' . trim($texto) . '%';
            $parametros[] = '%' . trim($texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbtemporadaestado = ?';
            $parametros[] = (int) $estado;
        }

        $sql .= ' ORDER BY tbtemporadavigente DESC, tbtemporadanombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $temporadas = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $temporadas[] = new Temporada($fila);
        }

        return $temporadas;
    }

    public function existenombre($nombre, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) AS total FROM tbtemporada
                WHERE LOWER(TRIM(tbtemporadanombre)) = LOWER(TRIM(?)) AND tbtemporadaid <> ?');
        $sentencia->execute([$nombre, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }
}
