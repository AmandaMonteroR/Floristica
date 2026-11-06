<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';

// Huellas de las fotos del catálogo (para la búsqueda por foto).
// Se guardan para no volver a calcularlas en cada búsqueda. Si la foto del arreglo cambia,
// la huella guardada ya no coincide con la foto y se calcula de nuevo.
class huellarepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    // [idarreglo => ['foto' => 'imagenes/...', 'huella' => [...]], ...]
    public function listar()
    {
        $lista = [];
        foreach ($this->conexion->query('SELECT * FROM tbarreglofloralhuella')->fetchAll() as $fila) {
            $lista[(int) $fila['tbarreglofloralid']] = [
                'foto'   => $fila['tbarreglofloralhuellafoto'],
                'huella' => json_decode($fila['tbarreglofloralhuellavalor'], true),
            ];
        }

        return $lista;
    }

    public function guardar($idarreglo, $foto, $huella)
    {
        $borrar = $this->conexion->prepare('DELETE FROM tbarreglofloralhuella WHERE tbarreglofloralid = ?');
        $borrar->execute([(int) $idarreglo]);

        $insertar = $this->conexion->prepare('INSERT INTO tbarreglofloralhuella VALUES (?, ?, ?)');
        return $insertar->execute([(int) $idarreglo, $foto, json_encode($huella)]);
    }
}
