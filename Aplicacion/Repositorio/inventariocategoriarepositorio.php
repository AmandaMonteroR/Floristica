<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/inventariocategoriaflor.php';
require_once __DIR__ . '/../Modelo/inventariocategoriabase.php';
require_once __DIR__ . '/../Modelo/inventariocategoriaadorno.php';
require_once __DIR__ . '/../Modelo/inventariocategoriaextra.php';

class inventariocategoriarepositorio
{
    private $conexion;
    private $categorias = [
        'Flor'   => ['clase' => 'Inventariocategoriaflor', 'campos' => ['tipo', 'color', 'largotallo', 'diasduracion']],
        'Base'   => ['clase' => 'Inventariocategoriabase', 'campos' => ['tipo', 'material', 'color', 'forma', 'medida']],
        'Adorno' => ['clase' => 'Inventariocategoriaadorno', 'campos' => ['tipo', 'material', 'color', 'medida', 'diseno']],
        'Extra'  => ['clase' => 'Inventariocategoriaextra', 'campos' => ['tipo', 'marca', 'tamano', 'color', 'contenido', 'fechavencimiento']],
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