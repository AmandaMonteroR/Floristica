<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/arreglofloraltemporada.php';
require_once __DIR__ . '/../Modelo/inventariotemporada.php';

// Precios por temporada de los arreglos (tbarreglofloraltemporada)
// y de los productos del inventario (tbinventariotemporada). Funcionan igual.
class preciotemporadarepositorio
{
    private $conexion;

    // Tabla, columna del registro, columna del precio y modelo de cada tipo. Solo se aceptan estos.
    private $tipos = [
        'arreglofloral' => [
            'tabla'  => 'tbarreglofloraltemporada',
            'id'     => 'tbarreglofloralid',
            'precio' => 'tbarreglofloraltemporadaprecio',
            'clase'  => 'Arreglofloraltemporada',
        ],
        'inventario' => [
            'tabla'  => 'tbinventariotemporada',
            'id'     => 'tbinventarioid',
            'precio' => 'tbinventariotemporadaprecioventa',
            'clase'  => 'Inventariotemporada',
        ],
    ];

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function tipo($tipo)
    {
        if (!isset($this->tipos[$tipo])) {
            throw new InvalidArgumentException('Tipo de precio de temporada no válido.');
        }

        return $this->tipos[$tipo];
    }

    private function siguienteid($tabla)
    {
        $consulta = $this->conexion->query("SELECT IFNULL(MAX({$tabla}id), 0) + 1 AS siguiente FROM {$tabla}");
        return (int) $consulta->fetch()['siguiente'];
    }

    // Precios de un arreglo o producto: [idtemporada => precio]
    public function listarporregistro($tipo, $idregistro)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT * FROM {$t['tabla']} WHERE {$t['id']} = ?");
        $sentencia->execute([(int) $idregistro]);

        $precios = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $modelo = new $t['clase']($fila);
            $precios[(int) $modelo->getTbtemporadaid()] = (float) $modelo->{'get' . ucfirst($t['precio'])}();
        }

        return $precios;
    }

    // Precios de una temporada: [idregistro => precio]
    public function listarportemporada($tipo, $idtemporada)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("SELECT * FROM {$t['tabla']} WHERE tbtemporadaid = ?");
        $sentencia->execute([(int) $idtemporada]);

        $precios = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $modelo = new $t['clase']($fila);
            $precios[(int) $modelo->{'get' . ucfirst($t['id'])}()] = (float) $modelo->{'get' . ucfirst($t['precio'])}();
        }

        return $precios;
    }

    // Guarda o cambia el precio de un arreglo o producto en una temporada
    public function guardar($tipo, $idregistro, $idtemporada, $precio)
    {
        $t = $this->tipo($tipo);
        $this->eliminar($tipo, $idregistro, $idtemporada);

        $modelo = new $t['clase']([
            $t['tabla'] . 'id' => $this->siguienteid($t['tabla']),
            $t['id']           => (int) $idregistro,
            'tbtemporadaid'    => (int) $idtemporada,
            $t['precio']       => $precio,
        ]);

        $sql = "INSERT INTO {$t['tabla']} ({$t['tabla']}id, {$t['id']}, tbtemporadaid, {$t['precio']}) VALUES (?, ?, ?, ?)";
        $sentencia = $this->conexion->prepare($sql);

        return $sentencia->execute([
            $modelo->{'get' . ucfirst($t['tabla']) . 'id'}(),
            $modelo->{'get' . ucfirst($t['id'])}(),
            $modelo->getTbtemporadaid(),
            $modelo->{'get' . ucfirst($t['precio'])}(),
        ]);
    }

    public function eliminar($tipo, $idregistro, $idtemporada)
    {
        $t = $this->tipo($tipo);
        $sentencia = $this->conexion->prepare("DELETE FROM {$t['tabla']} WHERE {$t['id']} = ? AND tbtemporadaid = ?");

        return $sentencia->execute([(int) $idregistro, (int) $idtemporada]);
    }

    // Cuántos arreglos o productos tienen precio en cada temporada: [idtemporada => cantidad]
    public function contarportemporada($tipo)
    {
        $t = $this->tipo($tipo);
        $conteos = [];
        foreach ($this->conexion->query("SELECT tbtemporadaid, COUNT(*) AS total FROM {$t['tabla']} GROUP BY tbtemporadaid") as $fila) {
            $conteos[(int) $fila['tbtemporadaid']] = (int) $fila['total'];
        }

        return $conteos;
    }
}
