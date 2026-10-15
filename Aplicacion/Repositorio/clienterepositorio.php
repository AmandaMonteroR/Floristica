<?php
require_once __DIR__ . '/../../Configuracion/Basedatos.php';
require_once __DIR__ . '/../Modelo/cliente.php';

class clienterepositorio
{
    private $conexion;

    public function __construct()
    {
        $this->conexion = Basedatos::conectar();
    }

    private function siguienteid()
    {
        $consulta = $this->conexion->query('SELECT IFNULL(MAX(tbclienteid), 0) + 1 AS siguiente FROM tbcliente');
        return (int) $consulta->fetch()['siguiente'];
    }

    public function insertar(Cliente $cliente)
    {
        $cliente->setTbclienteid($this->siguienteid());
        $cliente->setTbclienteestado(1);
        $cliente->setTbclientefecharegistro(date('Y-m-d'));

        $sql = 'INSERT INTO tbcliente (tbclienteid, tbclientenombre, tbclientetelefono, tbclientecorreo,
                tbclientedireccion, tbclienteestado, tbclientefecharegistro)
                VALUES (?, ?, ?, ?, ?, ?, ?)';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([
            $cliente->getTbclienteid(),
            $cliente->getTbclientenombre(),
            $cliente->getTbclientetelefono(),
            $cliente->getTbclientecorreo(),
            $cliente->getTbclientedireccion(),
            $cliente->getTbclienteestado(),
            $cliente->getTbclientefecharegistro(),
        ]);

        return $cliente->getTbclienteid();
    }

    public function actualizar(Cliente $cliente)
    {
        $sql = 'UPDATE tbcliente SET tbclientenombre = ?, tbclientetelefono = ?, tbclientecorreo = ?, tbclientedireccion = ?
                WHERE tbclienteid = ?';

        $sentencia = $this->conexion->prepare($sql);
        return $sentencia->execute([
            $cliente->getTbclientenombre(),
            $cliente->getTbclientetelefono(),
            $cliente->getTbclientecorreo(),
            $cliente->getTbclientedireccion(),
            $cliente->getTbclienteid(),
        ]);
    }

    private function cambiarestado($id, $estado)
    {
        $sentencia = $this->conexion->prepare('UPDATE tbcliente SET tbclienteestado = ? WHERE tbclienteid = ?');
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

    public function obtenerporid($id)
    {
        $sentencia = $this->conexion->prepare('SELECT * FROM tbcliente WHERE tbclienteid = ?');
        $sentencia->execute([$id]);
        $fila = $sentencia->fetch();

        return $fila ? new Cliente($fila) : null;
    }

    // Busca por nombre o por teléfono (con o sin guion)
    public function listar($texto = '', $estado = null)
    {
        $sql = 'SELECT * FROM tbcliente WHERE 1 = 1';
        $parametros = [];
        $texto = trim($texto);

        if ($texto !== '') {
            $sql .= " AND (tbclientenombre LIKE ? OR REPLACE(tbclientetelefono, '-', '') LIKE ?)";
            $parametros[] = '%' . $texto . '%';
            $parametros[] = '%' . str_replace('-', '', $texto) . '%';
        }

        if ($estado !== null && $estado !== '') {
            $sql .= ' AND tbclienteestado = ?';
            $parametros[] = (int) $estado;
        }

        $sql .= ' ORDER BY tbclientenombre';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute($parametros);

        $clientes = [];
        foreach ($sentencia->fetchAll() as $fila) {
            $clientes[] = new Cliente($fila);
        }

        return $clientes;
    }

    public function existetelefono($telefono, $idexcluir = 0)
    {
        $sentencia = $this->conexion->prepare('SELECT COUNT(*) AS total FROM tbcliente WHERE tbclientetelefono = ? AND tbclienteid <> ?');
        $sentencia->execute([$telefono, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }

    public function existecorreo($correo, $idexcluir = 0)
    {
        $sql = 'SELECT COUNT(*) AS total FROM tbcliente
                WHERE LOWER(TRIM(tbclientecorreo)) = LOWER(TRIM(?)) AND tbclienteid <> ?';

        $sentencia = $this->conexion->prepare($sql);
        $sentencia->execute([$correo, (int) $idexcluir]);

        return (int) $sentencia->fetch()['total'] > 0;
    }

    // Cantidad de solicitudes de cada cliente: [idcliente => ['total' => n, 'abiertas' => n]]
    public function contarsolicitudes()
    {
        $sql = "SELECT tbclienteid,
                       COUNT(*) AS total,
                       SUM(tbsolicitudencargoestado IN ('Pendiente', 'En proceso')) AS abiertas
                FROM tbsolicitudencargo
                GROUP BY tbclienteid";

        $conteos = [];
        foreach ($this->conexion->query($sql)->fetchAll() as $fila) {
            $conteos[(int) $fila['tbclienteid']] = [
                'total'    => (int) $fila['total'],
                'abiertas' => (int) $fila['abiertas'],
            ];
        }

        return $conteos;
    }
}
