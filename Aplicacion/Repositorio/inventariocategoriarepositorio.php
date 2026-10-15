<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/inventariocategoriaflor.php';
require_once __DIR__ . '/../Modelo/inventariocategoriaenvoltura.php';
require_once __DIR__ . '/../Modelo/inventariocategoriabase.php';
require_once __DIR__ . '/../Modelo/inventariocategoriachocolate.php';
require_once __DIR__ . '/../Modelo/inventariocategoriapeluche.php';
require_once __DIR__ . '/../Modelo/inventariocategoriatarjeta.php';

class inventariocategoriarepositorio
{
    private $conexion;
    // Cada categoría guarda sus propios atributos en tbinventariocategoria<categoría>
    private $categorias = [
        'Flor'      => ['clase' => 'Inventariocategoriaflor', 'campos' => ['tipo', 'variedad', 'color', 'largotallo', 'diasduracion']],
        'Envoltura' => ['clase' => 'Inventariocategoriaenvoltura', 'campos' => ['clase', 'tipo', 'color', 'diseno', 'medida', 'material', 'acabado', 'personalizable']],
        'Base'      => ['clase' => 'Inventariocategoriabase', 'campos' => ['tipo', 'material', 'color', 'forma', 'medida', 'diseno']],
        'Chocolate' => ['clase' => 'Inventariocategoriachocolate', 'campos' => ['tipo', 'marca', 'presentacion', 'peso', 'sabor', 'fechavencimiento']],
        'Peluche'   => ['clase' => 'Inventariocategoriapeluche', 'campos' => ['tipo', 'tamano', 'color', 'diseno', 'material']],
        'Tarjeta'   => ['clase' => 'Inventariocategoriatarjeta', 'campos' => ['tipo', 'tematica', 'color', 'material', 'medida', 'personalizable', 'elaboracion']],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tabla($categoria)
    {
        if (!isset($this->categorias[$categoria])) {
            throw new InvalidArgumentException('Categoría de inventario no válida.');
        }

        return 'tbinventariocategoria' . strtolower($categoria);
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    public function campos($categoria)
    {
        $tabla = $this->tabla($categoria);

        $campos = [];
        foreach ($this->categorias[$categoria]['campos'] as $campo) {
            $campos[] = $tabla . $campo;
        }

        return $campos;
    }

    public function insertar($categoria, $idinventario, $datos)
    {
        $tabla = $this->tabla($categoria);
        $campos = $this->campos($categoria);
        $clase = $this->categorias[$categoria]['clase'];

        $modelo = new $clase($datos);
        $modelo->{'set' . ucfirst($tabla) . 'id'}($this->siguienteid($tabla));
        $modelo->setTbinventarioid($idinventario);

        $sql = "INSERT INTO {$tabla} ({$tabla}id, tbinventarioid, " . implode(', ', $campos) . ')
                VALUES (?, ?, ' . implode(', ', array_fill(0, count($campos), '?')) . ')';

        $valores = [
            $modelo->{'get' . ucfirst($tabla) . 'id'}(),
            $modelo->getTbinventarioid(),
        ];
        foreach ($campos as $campo) {
            $valores[] = $modelo->{'get' . ucfirst($campo)}();
        }

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute($valores);
    }

    public function obtener($categoria, $idinventario)
    {
        $tabla = $this->tabla($categoria);
        $clase = $this->categorias[$categoria]['clase'];

        $sentencia = $this->conexion->prepare("SELECT * FROM {$tabla} WHERE tbinventarioid = ?");
        $sentencia->execute([$idinventario]);
        $fila = $sentencia->fetch();

        return $fila ? new $clase($fila) : null;
    }

    public function eliminar($idinventario)
    {
        foreach (array_keys($this->categorias) as $categoria) {
            $tabla = $this->tabla($categoria);
            $sentencia = $this->conexion->prepare("DELETE FROM {$tabla} WHERE tbinventarioid = ?");
            $sentencia->execute([$idinventario]);
        }

        return true;
    }
}
