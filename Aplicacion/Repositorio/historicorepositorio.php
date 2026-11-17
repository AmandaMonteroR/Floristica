<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

// Repositorio de los históricos: tablas tb<entidad><campo>historico
class historicorepositorio
{
    private $conexion;

    // Campos con histórico por entidad
    private $campos = [
        'inventario'    => ['nombre', 'proveedor', 'precio', 'precioventa', 'preciotemporada', 'otroproveedor', 'stockminimo', 'estado', 'foto'],
        'arreglofloral' => ['nombre', 'precio', 'estado'],
        'temporada'     => ['nombre', 'vigente', 'estado'],
        'empleado'      => ['nombre', 'telefono', 'rol', 'usuario', 'clave', 'estado'],
        'pedido'        => ['estado'],
        'solicitudencargo' => ['fechaentrega', 'total', 'foto', 'referencia'],
        'cliente'       => ['nombre', 'telefono', 'direccion', 'estado'],
        'zonaentrega'   => ['nombre', 'precio', 'estado'],
        'gasto'         => ['categoria', 'descripcion', 'monto', 'fecha', 'estado'],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // Nombre de la tabla; solo campos permitidos
    private function tabla($entidad, $campo)
    {
        if (!in_array($campo, $this->campos[$entidad] ?? [], true)) {
            throw new InvalidArgumentException('Histórico no válido.');
        }

        return 'tb' . $entidad . $campo . 'historico';
    }

    // Quién hizo el cambio (en empleado es otra columna)
    private function columnaresponsable($entidad)
    {
        return $entidad === 'empleado' ? 'tbempleadoidresponsable' : 'tbempleadoid';
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    // Guarda un cambio
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

    // Cambios de un registro, del más viejo al más nuevo
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