<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/pedidoabono.php';
require_once __DIR__ . '/../Modelo/solicitudencargoabono.php';

// Abonos de las cuentas por pagar (a los proveedores, por pedido) y por cobrar (de las clientas, por solicitud).
// Las dos tablas tienen las mismas columnas; cambia el nombre y a qué registro pertenecen.
class abonorepositorio
{
    private $conexion;

    // Tabla, columna del registro y modelo de cada tipo. Solo se aceptan estos.
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

    // Abonos de un pedido o de una solicitud, del más viejo al más nuevo, como arreglos simples
    public function listar($tipo, $idregistro)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT * FROM {$t['tabla']} WHERE {$t['id']} = ?
                ORDER BY {$t['tabla']}fecha, {$t['tabla']}id");
        $sentencia->execute([(int) $idregistro]);

        $abonos = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $abono = new $t['clase']($fila);
            $abonos[] = [
                'fecha'        => $abono->{'get' . ucfirst($t['tabla']) . 'fecha'}(),
                'monto'        => (float) $abono->{'get' . ucfirst($t['tabla']) . 'monto'}(),
                'metodo'       => $abono->{'get' . ucfirst($t['tabla']) . 'metodo'}(),
                'nota'         => (string) $abono->{'get' . ucfirst($t['tabla']) . 'nota'}(),
                'tbempleadoid' => (int) $abono->getTbempleadoid(),
            ];
        }

        return $abonos;
    }

    // Lo abonado a cada registro: [id del pedido o de la solicitud => total abonado]
    public function totales($tipo)
    {
        $t = $this->tipo($tipo);
        $totales = [];
        $sql = "SELECT {$t['id']} AS id, SUM({$t['tabla']}monto) AS total FROM {$t['tabla']} GROUP BY {$t['id']}";
        foreach ($this->conexion->query($sql) as $fila) {
            $totales[(int) $fila['id']] = (float) $fila['total'];
        }

        return $totales;
    }

    public function totalde($tipo, $idregistro)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT IFNULL(SUM({$t['tabla']}monto), 0) AS total FROM {$t['tabla']} WHERE {$t['id']} = ?");
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

        return $sentencia->execute([
            $abono->{$prefijo . 'id'}(),
            $abono->{'get' . ucfirst($t['id'])}(),
            $abono->{$prefijo . 'fecha'}(),
            $abono->{$prefijo . 'monto'}(),
            $abono->{$prefijo . 'metodo'}(),
            $abono->{$prefijo . 'nota'}(),
            $abono->getTbempleadoid(),
        ]);
    }
}
