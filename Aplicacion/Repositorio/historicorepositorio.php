<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

// Repositorio único para los históricos nuevos.
// Cada tabla se llama tb<entidad><campo>historico y tiene:
// id, tb<entidad>id, valor, tbempleadoid (quién hizo el cambio) y fecha.
// En los de empleado, quién hizo el cambio va en tbempleadoidresponsable.
class historicorepositorio
{
    private $conexion;

    // Entidades y datos que llevan histórico. Solo se aceptan estos nombres.
    private $campos = [
        'inventario'    => ['nombre', 'proveedor', 'precio', 'precioventa', 'preciotemporada', 'stockminimo', 'estado'],
        'arreglofloral' => ['nombre', 'precio', 'estado'],
        'temporada'     => ['nombre', 'vigente', 'estado'],
        'empleado'      => ['nombre', 'telefono', 'rol', 'usuario', 'clave', 'estado'],
        'pedido'        => ['estado'],
        'solicitudencargo' => ['fechaentrega', 'total', 'foto'],
        'cliente'       => ['nombre', 'telefono', 'direccion', 'estado'],
        'zonaentrega'   => ['nombre', 'precio', 'estado'],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tabla($entidad, $campo)
    {
        if (!in_array($campo, $this->campos[$entidad] ?? [], true)) {
            throw new InvalidArgumentException('Histórico no válido.');
        }

        return 'tb' . $entidad . $campo . 'historico';
    }

    // Columna de quién hizo el cambio. En los históricos de empleado se llama distinto,
    // porque tbempleadoid ya es el empleado al que se le hizo el cambio.
    private function columnaresponsable($entidad)
    {
        return $entidad === 'empleado' ? 'tbempleadoidresponsable' : 'tbempleadoid';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function registrar($entidad, $campo, $id, $valor, $idempleado)
    {
        $tabla = $this->tabla($entidad, $campo);
        $responsable = $this->columnaresponsable($entidad);
        $sql = "INSERT INTO {$tabla} ({$tabla}id, tb{$entidad}id, {$tabla}valor, {$responsable}, {$tabla}fecha)
                VALUES (?, ?, ?, ?, ?)";

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $this->siguienteid($tabla),
            (int) $id,
            $valor,
            $idempleado,
            date('Y-m-d H:i:s'),
        ]);
    }

    // Del más antiguo al más reciente, con el nombre de quien hizo el cambio
    public function listar($entidad, $campo, $id)
    {
        $tabla = $this->tabla($entidad, $campo);
        $responsable = $this->columnaresponsable($entidad);
        $sql = "SELECT h.{$tabla}valor AS valor, h.{$tabla}fecha AS fecha, e.tbempleadonombre AS hechopor
                FROM {$tabla} h
                LEFT JOIN tbempleado e ON e.tbempleadoid = h.{$responsable}
                WHERE h.tb{$entidad}id = ?
                ORDER BY h.{$tabla}fecha, h.{$tabla}id";

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([(int) $id]);

        return $sentencia->fetchAll();
    }
}
