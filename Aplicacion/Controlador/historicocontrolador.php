<?php
require_once __DIR__ . '/../Repositorio/historicorepositorio.php';

// Ayuda a los demás controladores a guardar y mostrar históricos
class historicocontrolador
{
    private $repositorio;

    public function __construct()
    {
        $this->repositorio = new historicorepositorio();
    }

    // Guarda solo los datos que cambiaron. Al crear, $anteriores va vacío y se guarda todo.
    public function registrar($entidad, $id, $anteriores, $nuevos, $idempleado)
    {
        foreach ($nuevos as $campo => $valor) {
            if (array_key_exists($campo, $anteriores) && (string) $anteriores[$campo] === (string) $valor) {
                continue;
            }

            $this->repositorio->registrar($entidad, $campo, $id, $valor, $idempleado);
        }
    }

    // Une todos los históricos de un registro, del más antiguo al más reciente.
    // $etiquetas: ['campo' => 'Nombre visible']; $formatear: función que convierte el valor en texto.
    // $agrupados: campos cuyo valor es "grupo|valor" (por ejemplo el precio de cada temporada);
    // en esos, el valor anterior se busca dentro del mismo grupo.
    public function listar($entidad, $id, $etiquetas, $formatear, $agrupados = [])
    {
        $cambios = [];

        foreach ($etiquetas as $campo => $etiqueta) {
            $anteriores = [];

            foreach ($this->repositorio->listar($entidad, $campo, $id) as $fila) {
                $valor = $formatear($campo, $fila['valor']);
                $grupo = in_array($campo, $agrupados, true) ? explode('|', (string) $fila['valor'])[0] : '';

                $cambios[] = [
                    'campo'    => $campo,
                    'etiqueta' => $etiqueta,
                    'anterior' => $anteriores[$grupo] ?? null,
                    'nuevo'    => $valor,
                    'hechopor' => $fila['hechopor'] ?? '',
                    'fecha'    => $fila['fecha'],
                ];

                $anteriores[$grupo] = $valor;
            }
        }
        
        // Por fecha y hora; si coinciden en el mismo segundo, primero el registro inicial
        usort($cambios, function ($a, $b) {
            return [$a['fecha'], $a['anterior'] !== null] <=> [$b['fecha'], $b['anterior'] !== null];
        });

        return $cambios;
    }

    // Formatos que comparten varios módulos
    public static function monto($valor)
    {
        return '₡' . number_format((float) $valor, 2, ',', ' ');
    }

    public static function estado($valor)
    {
        return (int) $valor === 1 ? 'Activo' : 'Inactivo';
    }
}