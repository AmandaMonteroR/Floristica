<?php
require_once __DIR__ . '/../Repositorio/temporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/preciotemporadarepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Reglas de los precios por temporada. Las usan el catálogo, el inventario y las solicitudes.
// $tipo es 'arreglofloral' (precio del arreglo) o 'inventario' (precio de venta por unidad).
// Si una temporada está vigente y el arreglo o producto tiene precio en ella, se usa ese precio;
// si no, se usa el precio normal.
class preciotemporadacontrolador
{
    private $temporadas;
    private $precios;
    private $historico;
    private $vigente = false;

    public function __construct()
    {
        $this->temporadas = new temporadarepositorio();
        $this->precios = new preciotemporadarepositorio();
        $this->historico = new historicocontrolador();
    }

    // Temporada cuyos precios se están usando, o null
    public function vigente()
    {
        if ($this->vigente === false) {
            $this->vigente = $this->temporadas->obtenervigente();
        }

        return $this->vigente;
    }

    public function datosvigente()
    {
        $vigente = $this->vigente();

        return $vigente ? [
            'tbtemporadaid'     => (int) $vigente->getTbtemporadaid(),
            'tbtemporadanombre' => $vigente->getTbtemporadanombre(),
        ] : null;
    }

    // Precios de la temporada vigente: [idregistro => precio]; vacío si no hay temporada vigente
    public function preciosvigentes($tipo)
    {
        $vigente = $this->vigente();

        return $vigente ? $this->precios->listarportemporada($tipo, $vigente->getTbtemporadaid()) : [];
    }

    // Precios de un arreglo o producto para el formulario: todas las temporadas activas,
    // y también las inactivas en las que ya tiene precio
    public function paraformulario($tipo, $idregistro)
    {
        $precios = $idregistro ? $this->precios->listarporregistro($tipo, $idregistro) : [];

        $lista = [];
        foreach ($this->temporadas->listar() as $temporada) {
            $id = (int) $temporada->getTbtemporadaid();
            if (!$temporada->estaActivo() && !isset($precios[$id])) {
                continue;
            }

            $lista[] = [
                'tbtemporadaid'     => $id,
                'tbtemporadanombre' => $temporada->getTbtemporadanombre(),
                'vigente'           => $temporada->estaVigente(),
                'activa'            => $temporada->estaActivo(),
                'precio'            => $precios[$id] ?? null,
            ];
        }

        return $lista;
    }

    // temporadas[idtemporada] = "45000" → ['3' => '45000', ...]. Lo vacío significa "sin precio".
    public function limpiar($entrada)
    {
        $limpios = [];
        foreach ((array) $entrada as $idtemporada => $precio) {
            if (ctype_digit((string) $idtemporada)) {
                $limpios[(int) $idtemporada] = str_replace(',', '.', trim((string) $precio));
            }
        }

        return $limpios;
    }

    // Devuelve el mensaje de error o '' si todo está bien.
    // $minimo: el precio no puede ser menor (en el inventario es el costo por unidad).
    public function validar($precios, $minimo = 0, $textominimo = '')
    {
        foreach ($precios as $idtemporada => $precio) {
            if ($precio === '') {
                continue;
            }

            $temporada = $this->temporadas->obtenerporid($idtemporada);
            $nombre = $temporada ? $temporada->getTbtemporadanombre() : '';

            if (!$temporada) {
                return 'Una de las temporadas no existe.';
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precio) || (float) $precio <= 0) {
                return 'El precio de ' . $nombre . ' debe ser un monto mayor que 0, con máximo 2 decimales.';
            }
            if ($minimo > 0 && (float) $precio < $minimo) {
                return 'El precio de ' . $nombre . ' no puede ser menor que ' . $textominimo
                    . ' (' . historicocontrolador::monto($minimo) . ').';
            }
        }

        return '';
    }

    // Guarda solo lo que cambió y deja cada cambio en el historial del arreglo o producto
    public function guardar($tipo, $idregistro, $precios, $idempleado)
    {
        $actuales = $this->precios->listarporregistro($tipo, $idregistro);

        foreach ($precios as $idtemporada => $precio) {
            $antes = $actuales[$idtemporada] ?? null;

            if ($precio === '') {
                if ($antes !== null) {
                    $this->precios->eliminar($tipo, $idregistro, $idtemporada);
                    $this->historico->registrar($tipo, $idregistro, [], ['preciotemporada' => $idtemporada . '|'], $idempleado);
                }
                continue;
            }

            $nuevo = number_format((float) $precio, 2, '.', '');
            if ($antes !== null && number_format($antes, 2, '.', '') === $nuevo) {
                continue;
            }

            $this->precios->guardar($tipo, $idregistro, $idtemporada, $nuevo);
            $this->historico->registrar($tipo, $idregistro, [], ['preciotemporada' => $idtemporada . '|' . $nuevo], $idempleado);
        }
    }

    // Convierte "3|45000.00" en "San Valentín: ₡45 000,00" para el historial
    public function textohistorial($valor)
    {
        [$idtemporada, $precio] = explode('|', (string) $valor) + [1 => ''];
        $temporada = $this->temporadas->obtenerporid((int) $idtemporada);
        $nombre = $temporada ? $temporada->getTbtemporadanombre() : 'Temporada eliminada';

        return $nombre . ': ' . ($precio === '' ? 'sin precio (usa el normal)' : historicocontrolador::monto($precio));
    }

    // De una lista de [id => nombre, precio normal, mínimo], los que no tienen precio en la temporada vigente
    public function pendientes($tipo, $registros)
    {
        $vigente = $this->vigente();
        if (!$vigente) {
            return ['temporada' => null, 'pendientes' => []];
        }

        $conprecio = $this->precios->listarportemporada($tipo, $vigente->getTbtemporadaid());

        $pendientes = [];
        foreach ($registros as $registro) {
            if (!isset($conprecio[(int) $registro['id']])) {
                $pendientes[] = $registro;
            }
        }

        return ['temporada' => $this->datosvigente(), 'pendientes' => $pendientes];
    }

    // Guarda varios precios de la temporada vigente a la vez: [idregistro => precio].
    // $registros: [idregistro => ['nombre' => ..., 'minimo' => ...]] de los que se pueden cambiar.
    // Devuelve ['errores' => [idregistro => mensaje], 'guardados' => cantidad]
    public function guardarvarios($tipo, $entrada, $registros, $textominimo, $idempleado)
    {
        $vigente = $this->vigente();
        $errores = [];
        $validos = [];

        foreach ((array) $entrada as $idregistro => $precio) {
            $precio = str_replace(',', '.', trim((string) $precio));
            if (!isset($registros[$idregistro]) || $precio === '') {
                continue;
            }

            $error = $this->validar([(int) $vigente->getTbtemporadaid() => $precio], $registros[$idregistro]['minimo'], $textominimo);
            if ($error !== '') {
                $errores[$idregistro] = $registros[$idregistro]['nombre'] . ': ' . $error;
            } else {
                $validos[$idregistro] = $precio;
            }
        }

        if (!$errores) {
            foreach ($validos as $idregistro => $precio) {
                $this->guardar($tipo, $idregistro, [(int) $vigente->getTbtemporadaid() => $precio], $idempleado);
            }
        }

        return ['errores' => $errores, 'guardados' => $errores ? 0 : count($validos)];
    }
}
