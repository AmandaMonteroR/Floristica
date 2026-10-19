<?php
require_once __DIR__ . '/../Repositorio/temporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/preciotemporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Precios de temporada alta. Lo único que se escribe es lo que cobra el proveedor por el paquete
// de cada flor en temporada alta. Todo lo demás se calcula solo, con la misma ganancia en colones:
// - Precio de venta del producto: el normal + lo que subió su costo por unidad.
// - Precio de un arreglo: el normal + lo que subió el costo de todo lo que lleva.
// Los precios calculados se redondean hacia arriba a ₡100.
// Si un producto no tiene precio para la temporada, se usa el de temporada baja.
class preciotemporadacontrolador
{
    private $temporadas;
    private $precios;
    private $inventario;
    private $arreglos;
    private $historico;
    private $vigente = false;
    private $productos = [];

    public function __construct()
    {
        $this->temporadas = new temporadarepositorio();
        $this->precios = new preciotemporadarepositorio();
        $this->inventario = new inventariorepositorio();
        $this->arreglos = new arreglofloralrepositorio();
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

    // La temporada alta (hay una sola), esté encendida o no
    public function alta()
    {
        $lista = $this->temporadas->listar();
        return $lista[0] ?? null;
    }

    public function idalta()
    {
        $alta = $this->alta();
        return $alta ? (int) $alta->getTbtemporadaid() : 0;
    }

    // Redondea hacia arriba a ₡100 (así se cobran los precios calculados)
    public static function redondear($monto)
    {
        return (float) (ceil(round($monto, 2) / 100) * 100);
    }

    // Costo por unidad y precio de venta de cada producto en una temporada.
    // $idtemporada: null = la temporada en uso (si no hay, los precios normales).
    // Devuelve [idproducto => ['costo' => ..., 'venta' => ..., 'contemporada' => true/false]]
    public function productos($idtemporada = null)
    {
        if ($idtemporada === null) {
            $vigente = $this->vigente();
            $idtemporada = $vigente ? (int) $vigente->getTbtemporadaid() : 0;
        }

        if (!isset($this->productos[$idtemporada])) {
            $paquetes = $idtemporada ? $this->precios->listarportemporada('inventario', $idtemporada) : [];

            $lista = [];
            foreach ($this->inventario->listar() as $producto) {
                $id = (int) $producto->getTbinventarioid();
                $costo = (float) $producto->getTbinventariopreciounidad();
                $venta = (float) $producto->getTbinventarioprecioventa();
                $paquete = (float) $producto->getTbinventariopreciopaquete();
                $unidades = (int) $producto->getTbinventariounidadespaquete();

                // El precio de venta sube lo mismo que subió el costo por unidad (misma ganancia en colones)
                $contemporada = false;
                if (isset($paquetes[$id]) && $unidades > 0 && $paquete > 0 && abs($paquetes[$id] - $paquete) >= 0.01) {
                    $costonuevo = round($paquetes[$id] / $unidades, 2);
                    $venta = self::redondear($venta + $costonuevo - $costo);
                    $costo = $costonuevo;
                    $contemporada = true;
                }

                $lista[$id] = ['costo' => $costo, 'venta' => $venta, 'contemporada' => $contemporada];
            }

            $this->productos[$idtemporada] = $lista;
        }

        return $this->productos[$idtemporada];
    }

    // Precio y costo de un arreglo en una temporada (null = la que está en uso).
    // El precio sube lo mismo que subió el costo de lo que lleva (misma ganancia en colones).
    public function arreglo($arreglo, $idtemporada = null)
    {
        $normales = $this->productos(0);
        $productos = $this->productos($idtemporada);

        $costonormal = 0;
        $costo = 0;
        foreach ($this->arreglos->listardetalles($arreglo->getTbarreglofloralid()) as $detalle) {
            $id = (int) $detalle->getTbinventarioid();
            $cantidad = (int) $detalle->getTbarreglofloralinsumocantidad();
            $costonormal += $cantidad * ($normales[$id]['costo'] ?? 0);
            $costo += $cantidad * ($productos[$id]['costo'] ?? 0);
        }

        $precionormal = (float) $arreglo->getTbarreglofloralprecio();
        $cambia = $costonormal > 0 && abs($costo - $costonormal) >= 0.01;

        return [
            'precio'       => $cambia ? self::redondear($precionormal + $costo - $costonormal) : $precionormal,
            'costo'        => round($costo, 2),
            'costonormal'  => round($costonormal, 2),
            'contemporada' => $cambia,
        ];
    }

    // Lo que cobra el proveedor por el paquete en cada temporada, para el formulario del producto:
    // todas las temporadas activas, y también las inactivas en las que ya tiene precio
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

    // temporadas[idtemporada] = "14000" → [3 => '14000', ...]. Lo vacío significa "sin precio".
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

    // Devuelve el mensaje de error o '' si todo está bien
    public function validar($precios)
    {
        foreach ($precios as $idtemporada => $precio) {
            if ($precio === '') {
                continue;
            }

            $temporada = $this->temporadas->obtenerporid($idtemporada);
            if (!$temporada) {
                return 'Una de las temporadas no existe.';
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $precio) || (float) $precio <= 0) {
                return 'El precio del paquete en ' . $temporada->getTbtemporadanombre()
                    . ' debe ser un monto mayor que 0, con máximo 2 decimales.';
            }
        }

        return '';
    }

    // Guarda solo lo que cambió y deja cada cambio en el historial del producto
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

        $this->productos = [];
    }

    // Convierte "1|14000.00" en "Temporada alta: ₡14 000,00 el paquete" para el historial
    public function textohistorial($valor)
    {
        [$idtemporada, $precio] = explode('|', (string) $valor) + [1 => ''];
        $temporada = $this->temporadas->obtenerporid((int) $idtemporada);
        $nombre = $temporada ? $temporada->getTbtemporadanombre() : 'Temporada eliminada';

        return $nombre . ': ' . ($precio === '' ? 'sin precio (usa el normal)' : historicocontrolador::monto($precio) . ' el paquete');
    }

    // De una lista de productos [id, nombre, precio normal...], los que no tienen precio en la temporada vigente
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

    // Guarda varios precios de la temporada vigente a la vez: [idproducto => precio del paquete].
    // $registros: [idproducto => ['nombre' => ...]] de los que se pueden cambiar.
    // Devuelve ['errores' => [idproducto => mensaje], 'guardados' => cantidad]
    public function guardarvarios($tipo, $entrada, $registros, $idempleado)
    {
        $vigente = $this->vigente();
        $errores = [];
        $validos = [];

        foreach ((array) $entrada as $idregistro => $precio) {
            $precio = str_replace(',', '.', trim((string) $precio));
            if (!isset($registros[$idregistro]) || $precio === '') {
                continue;
            }

            $error = $this->validar([(int) $vigente->getTbtemporadaid() => $precio]);
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
