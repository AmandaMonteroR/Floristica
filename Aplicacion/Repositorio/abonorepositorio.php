<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/pedidoabono.php';
require_once __DIR__ . '/../Modelo/solicitudencargoabono.php';

class abonorepositorio
{
    private $conexion;

    private $tipos = [
        'pagar'  => ['tabla' => 'tbpedidoabono', 'id' => 'tbpedidoid', 'clase' => 'Pedidoabono'],
        'cobrar' => ['tabla' => 'tbsolicitudencargoabono', 'id' => 'tbsolicitudencargoid', 'clase' => 'Solicitudencargoabono'],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tipo($tipo)
    {
        if (!isset($this->tipos[$tipo])) {
            throw new InvalidArgumentException('Tipo de cuenta no válido.');
        }

        return $this->tipos[$tipo];
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function listar($tipo, $idregistro)
    {
        $t = $this->tipo($tipo);
        $tabla = $t['tabla'];
        $historico = $tabla . 'estadohistorico';
        $sentencia = $this->conexion->prepare("SELECT a.*, h.{$historico}fecha AS fechaanulacion, h.tbempleadoid AS anuladopor
                FROM {$tabla} a
                LEFT JOIN {$historico} h ON h.{$historico}id = (
                    SELECT MAX(h2.{$historico}id) FROM {$historico} h2
                    WHERE h2.{$tabla}id = a.{$tabla}id AND h2.{$historico}valor = 0)
                WHERE a.{$t['id']} = ?
                ORDER BY a.{$tabla}fecha, a.{$tabla}id");
        $sentencia->execute([(int) $idregistro]);

        $abonos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $abono = new $t['clase']($fila);
            $prefijo = 'get' . ucfirst($tabla);
            $abonos[] = [
                'id'             => (int) $abono->{$prefijo . 'id'}(),
                'fecha'          => $abono->{$prefijo . 'fecha'}(),
                'monto'          => (float) $abono->{$prefijo . 'monto'}(),
                'metodo'         => $abono->{$prefijo . 'metodo'}(),
                'nota'           => (string) $abono->{$prefijo . 'nota'}(),
                'tbempleadoid'   => (int) $abono->getTbempleadoid(),
                'estado'         => (int) $abono->{$prefijo . 'estado'}(),
                'motivo'         => (string) $abono->{$prefijo . 'motivoanulacion'}(),
                'fechaanulacion' => $fila['fechaanulacion'],
                'anuladopor'     => (int) $fila['anuladopor'],
            ];
        }

        return $abonos;
    }

    public function obtener($tipo, $idabono)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT * FROM {$t['tabla']} WHERE {$t['tabla']}id = ?");
        $sentencia->execute([(int) $idabono]);
        $fila = $sentencia->fetch();

        return $fila ? new $t['clase']($fila) : null;
    }

    public function anular($tipo, $idabono, $motivo, $idempleado)
    {
        $t = $this->tipo($tipo);
        $tabla = $t['tabla'];
        $historico = $tabla . 'estadohistorico';

        $this->conexion->beginTransaction();
        try {
            $sentencia = $this->conexion->prepare("UPDATE {$tabla} SET {$tabla}estado = 0, {$tabla}motivoanulacion = ?
                    WHERE {$tabla}id = ? AND {$tabla}estado = 1");
            $sentencia->execute([$motivo, (int) $idabono]);
            if ($sentencia->rowCount() !== 1) {
                $this->conexion->rollBack();
                return false;
            }

            $sentencia = $this->conexion->prepare("INSERT INTO {$historico}
                    ({$historico}id, {$tabla}id, {$historico}valor, tbempleadoid, {$historico}fecha)
                    VALUES (?, ?, 0, ?, ?)");
            $sentencia->execute([$this->siguienteid($historico), (int) $idabono, (int) $idempleado, date('Y-m-d H:i:s')]);

            $this->conexion->commit();
            return true;
        } catch (Throwable $error) {
            $this->conexion->rollBack();
            throw $error;
        }
    }

    public function totales($tipo)
    {
        $t = $this->tipo($tipo);
        $totales = [];
        $sql = "SELECT {$t['id']} AS id, SUM({$t['tabla']}monto) AS total FROM {$t['tabla']}
                WHERE {$t['tabla']}estado = 1 GROUP BY {$t['id']}";
        foreach ($this->conexion->query($sql) as $fila) {
            $totales[(int) $fila['id']] = (float) $fila['total'];
        }

        return $totales;
    }

    public function totalde($tipo, $idregistro)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT IFNULL(SUM({$t['tabla']}monto), 0) AS total FROM {$t['tabla']}
                WHERE {$t['id']} = ? AND {$t['tabla']}estado = 1");
        $sentencia->execute([(int) $idregistro]);

        return (float) $sentencia->fetch()['total'];
    }

    public function insertar($tipo, $idregistro, $fecha, $monto, $metodo, $nota, $idempleado)
    {
        $t = $this->tipo($tipo);
        $tabla = $t['tabla'];
        $abono = new $t['clase']([
            $tabla . 'id'       => $this->siguienteid($tabla),
            $t['id']            => (int) $idregistro,
            $tabla . 'fecha'    => $fecha,
            $tabla . 'monto'    => $monto,
            $tabla . 'metodo'   => $metodo,
            $tabla . 'nota'     => $nota,
            'tbempleadoid'      => (int) $idempleado,
        ]);

        $sql = "INSERT INTO {$tabla} ({$tabla}id, {$t['id']}, {$tabla}fecha, {$tabla}monto, {$tabla}metodo, {$tabla}nota, tbempleadoid)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $sentencia = $this->conexion->prepare($sql);
        $prefijo = 'get' . ucfirst($tabla);

        $sentencia->execute([
            $abono->{$prefijo . 'id'}(),
            $abono->{'get' . ucfirst($t['id'])}(),
            $abono->{$prefijo . 'fecha'}(),
            $abono->{$prefijo . 'monto'}(),
            $abono->{$prefijo . 'metodo'}(),
            $abono->{$prefijo . 'nota'}(),
            $abono->getTbempleadoid(),
        ]);

        return (int) $abono->{$prefijo . 'id'}();
    }
}