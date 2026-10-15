<?php
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariocategoriarepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

class inventariocontrolador
{
    private $repositorio;
    private $atributos;
    private $proveedores;
    private $movimientos;
    private $historico;
    private $categorias = ['Flor', 'Base', 'Adorno', 'Extra'];
    private $tiposbase = ['Caja', 'Base'];
    private $materialesbase = ['Plástico', 'Madera'];
    private $tiposadorno = ['Cinta', 'Papel', 'Tarjeta'];
    private $tiposextra = ['Peluche', 'Chocolate', 'Maquillaje', 'Juguete'];
    private $extrasconvencimiento = ['Chocolate', 'Maquillaje'];

    public function __construct()
    {
        $this->repositorio = new inventariorepositorio();
        $this->atributos = new inventariocategoriarepositorio();
        $this->proveedores = new proveedorrepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->historico = new historicocontrolador();
    }

    public function proveedores()
    {
        $lista = [];
        foreach ($this->proveedores->listar('', 1) as $proveedor) {
            $lista[] = [
                'tbproveedorid'            => $proveedor->getTbproveedorid(),
                'tbproveedornombreempresa' => $proveedor->getTbproveedornombreempresa(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function listar($texto = '', $categoria = '', $estado = null)
    {
        $nombres = $this->nombresproveedores();

        $lista = [];
        foreach ($this->repositorio->listar($texto, $categoria, $estado) as $inventario) {
            $lista[] = $this->convertirarreglo($inventario, $nombres);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $inventario = $this->repositorio->obtenerporid((int) $id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        $datos = $this->convertirarreglo($inventario, $this->nombresproveedores());
        $datos['categoria'] = $this->convertircategoria($inventario);
        $datos['movimientos'] = $this->convertirmovimientos($inventario->getTbinventarioid());

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbinventariopreciounidad'] = $this->calcularpreciounidad($datos);
        $inventario = new Inventario($datos);

        $id = $this->entransaccion(function () use ($inventario, $datos, $usuario) {
            $id = $this->repositorio->insertar($inventario);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);
            $this->movimientos->registrar($id, 'Inicial', (int) $datos['tbinventariostock'], $usuario['id']);
            $this->historico->registrar('inventario', $id, [], $this->datoshistorico($inventario), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Producto registrado correctamente.', ['tbinventarioid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbinventarioid'] ?? 0);
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $stockanterior = (int) $inventario->getTbinventariostock();
        $anteriores = $this->datoshistorico($inventario);
        $inventario->setTbproveedorid($datos['tbproveedorid']);
        $inventario->setTbinventarionombre($datos['tbinventarionombre']);
        $inventario->setTbinventariocategoria($datos['tbinventariocategoria']);
        $inventario->setTbinventariopreciopaquete($datos['tbinventariopreciopaquete']);
        $inventario->setTbinventariounidadespaquete($datos['tbinventariounidadespaquete']);
        $inventario->setTbinventariopreciounidad($this->calcularpreciounidad($datos));
        $inventario->setTbinventariostock($datos['tbinventariostock']);
        $inventario->setTbinventariostockminimo($datos['tbinventariostockminimo']);

        $this->entransaccion(function () use ($inventario, $datos, $id, $stockanterior, $anteriores, $usuario) {
            $this->repositorio->actualizar($inventario);
            $this->historico->registrar('inventario', $id, $anteriores, $this->datoshistorico($inventario), $usuario['id']);
            $this->atributos->eliminar($id);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);

            // Si se corrigió el stock a mano, queda como ajuste
            $diferencia = (int) $datos['tbinventariostock'] - $stockanterior;
            if ($diferencia !== 0) {
                $this->movimientos->registrar($id, 'Ajuste', $diferencia, $usuario['id']);
            }
        });

        return $this->respuesta(true, 'Producto actualizado correctamente.');
    }

    public function inactivar($id, $usuario)
    {
        $id = (int) $id;
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        if (!$inventario->estaActivo()) {
            return $this->respuesta(false, 'El producto ya está inactivo.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('inventario', $id, ['estado' => 1], ['estado' => 0], $usuario['id']);
        });

        return $this->respuesta(true, 'Producto inactivado correctamente.');
    }

    public function reactivar($id, $usuario)
    {
        $id = (int) $id;
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        if ($inventario->estaActivo()) {
            return $this->respuesta(false, 'El producto ya está activo.');
        }

        $this->entransaccion(function () use ($id, $usuario) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('inventario', $id, ['estado' => 0], ['estado' => 1], $usuario['id']);
        });

        return $this->respuesta(true, 'Producto reactivado correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        $etiquetas = [
            'nombre'      => 'Nombre',
            'proveedor'   => 'Proveedor',
            'precio'      => 'Precio del paquete',
            'stockminimo' => 'Stock mínimo',
            'estado'      => 'Estado',
        ];

        $nombres = $this->nombresproveedores();

        $formatear = function ($campo, $valor) use ($nombres) {
            switch ($campo) {
                case 'proveedor':
                    return $nombres[$valor] ?? 'Proveedor eliminado';
                case 'precio':
                    [$precio, $unidades] = array_map('trim', explode('/', $valor) + [1 => '']);
                    return historicocontrolador::monto($precio) . ' por ' . $unidades . ' unidades';
                case 'estado':
                    return historicocontrolador::estado($valor);
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('inventario', $id, $etiquetas, $formatear));
    }

    // Datos del producto que llevan histórico, tal como se guardan
    private function datoshistorico(Inventario $inventario)
    {
        return [
            'nombre'      => $inventario->getTbinventarionombre(),
            'proveedor'   => (int) $inventario->getTbproveedorid(),
            'precio'      => number_format((float) $inventario->getTbinventariopreciopaquete(), 2, '.', '')
                . ' / ' . (int) $inventario->getTbinventariounidadespaquete(),
            'stockminimo' => (int) $inventario->getTbinventariostockminimo(),
            'estado'      => (int) $inventario->getTbinventarioestado(),
        ];
    }

    private function nombresproveedores()
    {
        $nombres = [];
        foreach ($this->proveedores->listar() as $proveedor) {
            $nombres[$proveedor->getTbproveedorid()] = $proveedor->getTbproveedornombreempresa();
        }

        return $nombres;
    }

    // Movimientos del stock en palabras, para mostrarlos en "Ver"
    private function convertirmovimientos($idinventario)
    {
        $lista = [];
        foreach (array_reverse($this->movimientos->listar($idinventario)) as $fila) {            switch ($fila['tbinventariomovimientotipo']) {
                case 'Inicial':
                    $motivo = 'Stock inicial al registrar el producto';
                    break;
                case 'Entrada':
                    $motivo = 'Pedido N.º ' . $fila['tbpedidoid'] . ' recibido';
                    break;
                case 'Salida':
                    $motivo = 'Encargo N.º ' . $fila['tbsolicitudencargoid'] . ' terminado';
                    break;
                default:
                    $motivo = 'Ajuste manual al editar el producto';
            }

            $lista[] = [
                'fecha'      => $fila['tbinventariomovimientofecha'],
                'tipo'       => $fila['tbinventariomovimientotipo'],
                'motivo'     => $motivo,
                'cantidad'   => (int) $fila['tbinventariomovimientocantidad'],
                'stockfinal' => (int) $fila['tbinventariomovimientostockfinal'],
                'hechopor'   => $fila['hechopor'] ?? '',
            ];
        }

        return $lista;
    }

    private function calcularpreciounidad($datos)
    {
        return round((float) $datos['tbinventariopreciopaquete'] / (int) $datos['tbinventariounidadespaquete'], 2);
    }

    private function entransaccion($operacion)
    {
        $conexion = Basedatos::conectar();
        $conexion->beginTransaction();

        try {
            $resultado = $operacion();
            $conexion->commit();
            return $resultado;
        } catch (Throwable $excepcion) {
            $conexion->rollBack();
            throw $excepcion;
        }
    }

    private function limpiar($datos)
    {
        $campos = [
            'tbproveedorid',
            'tbinventarionombre',
            'tbinventariocategoria',
            'tbinventariopreciopaquete',
            'tbinventariounidadespaquete',
            'tbinventariostock',
            'tbinventariostockminimo',
        ];

        $limpios = [];
        foreach ($campos as $campo) {
            $limpios[$campo] = $this->limpiartexto($datos[$campo] ?? '');
        }

        $limpios['tbinventariopreciopaquete'] = str_replace(',', '.', $limpios['tbinventariopreciopaquete']);

        if (in_array($limpios['tbinventariocategoria'], $this->categorias, true)) {
            foreach ($this->atributos->campos($limpios['tbinventariocategoria']) as $campo) {
                $limpios[$campo] = $this->limpiartexto($datos[$campo] ?? '');
            }
        }

        // Las medidas se guardan con el formato "20 x 30"
        foreach (['tbinventariocategoriabasemedida', 'tbinventariocategoriaadornomedida'] as $campo) {
            if (isset($limpios[$campo])) {
                $limpios[$campo] = preg_replace('/\s*[xX×]\s*/u', ' x ', $limpios[$campo]);
            }
        }

        if ($limpios['tbinventariocategoria'] === 'Extra'
            && !in_array($limpios['tbinventariocategoriaextratipo'], $this->extrasconvencimiento, true)) {
            $limpios['tbinventariocategoriaextrafechavencimiento'] = '';
        }

        if (($limpios['tbinventariocategoriaextrafechavencimiento'] ?? null) === '') {
            $limpios['tbinventariocategoriaextrafechavencimiento'] = null;
        }

        return $limpios;
    }

    private function limpiartexto($valor)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $valor));
    }

    private function validar($datos, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbproveedorid'] === '') {
            $errores['tbproveedorid'] = 'El proveedor es obligatorio.';
        } else {
            $proveedor = $this->proveedores->obtenerporid((int) $datos['tbproveedorid']);
            if (!$proveedor) {
                $errores['tbproveedorid'] = 'El proveedor seleccionado no existe.';
            } elseif (!$proveedor->estaActivo()) {
                $errores['tbproveedorid'] = 'El proveedor seleccionado está inactivo.';
            }
        }

        if ($datos['tbinventarionombre'] === '') {
            $errores['tbinventarionombre'] = 'El nombre del producto es obligatorio.';
        } elseif (mb_strlen($datos['tbinventarionombre']) > 100) {
            $errores['tbinventarionombre'] = 'El nombre del producto no puede superar 100 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbinventarionombre'])) {
            $errores['tbinventarionombre'] = 'El nombre del producto debe contener al menos una letra.';
        } elseif (!preg_match("/^[\p{L}\d\s.,'&()-]+$/u", $datos['tbinventarionombre'])) {
            $errores['tbinventarionombre'] = 'El nombre del producto solo puede contener letras, números y los signos . , - \' & ( ).';
        } elseif (!isset($errores['tbproveedorid'])
            && $this->repositorio->existenombre($datos['tbinventarionombre'], $datos['tbproveedorid'], $idexcluir)) {
            $errores['tbinventarionombre'] = 'Este proveedor ya tiene un producto con ese nombre.';
        }

        if ($datos['tbinventariocategoria'] === '') {
            $errores['tbinventariocategoria'] = 'La categoría es obligatoria.';
        } elseif (!in_array($datos['tbinventariocategoria'], $this->categorias, true)) {
            $errores['tbinventariocategoria'] = 'La categoría seleccionada no es válida.';
        }

        if ($datos['tbinventariopreciopaquete'] === '') {
            $errores['tbinventariopreciopaquete'] = 'El precio del paquete es obligatorio.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $datos['tbinventariopreciopaquete'])
            || (float) $datos['tbinventariopreciopaquete'] <= 0) {
            $errores['tbinventariopreciopaquete'] = 'El precio del paquete debe ser un monto mayor que 0, con máximo 2 decimales.';
        }

        $this->validarentero($errores, $datos, 'tbinventariounidadespaquete', 1, 10000,
            'Las unidades por paquete son obligatorias.',
            'Las unidades por paquete deben ser un número entero entre 1 y 10000.');

        $this->validarentero($errores, $datos, 'tbinventariostock', 0, 999999,
            'El stock es obligatorio.',
            'El stock debe ser un número entero entre 0 y 999999.');

        $this->validarentero($errores, $datos, 'tbinventariostockminimo', 0, 999999,
            'El stock mínimo es obligatorio.',
            'El stock mínimo debe ser un número entero entre 0 y 999999.');

        switch ($datos['tbinventariocategoria']) {
            case 'Flor':
                $errores += $this->validarflor($datos);
                break;
            case 'Base':
                $errores += $this->validarbase($datos);
                break;
            case 'Adorno':
                $errores += $this->validaradorno($datos);
                break;
            case 'Extra':
                $errores += $this->validarextra($datos);
                break;
        }

        return $errores;
    }

    private function validarentero(&$errores, $datos, $campo, $minimo, $maximo, $mensajeobligatorio, $mensajerango)
    {
        $valor = $datos[$campo];

        if ($valor === '') {
            $errores[$campo] = $mensajeobligatorio;
        } elseif (!ctype_digit($valor) || strlen($valor) > 7 || (int) $valor < $minimo || (int) $valor > $maximo) {
            $errores[$campo] = $mensajerango;
        }
    }

    private function validarletras(&$errores, $datos, $campo, $nombre, $maximo, $obligatorio)
    {
        $valor = $datos[$campo];

        if ($valor === '') {
            if ($obligatorio) {
                $errores[$campo] = $nombre . ' es obligatorio.';
            }
        } elseif (mb_strlen($valor) > $maximo) {
            $errores[$campo] = $nombre . ' no puede superar ' . $maximo . ' caracteres.';
        } elseif (!preg_match('/^[\p{L}\s]+$/u', $valor)) {
            $errores[$campo] = $nombre . ' solo puede contener letras.';
        }
    }

    private function validartexto(&$errores, $datos, $campo, $nombre, $maximo)
    {
        $valor = $datos[$campo];

        if ($valor === '') {
            return;
        }

        if (mb_strlen($valor) > $maximo) {
            $errores[$campo] = $nombre . ' no puede superar ' . $maximo . ' caracteres.';
        } elseif (!preg_match("/^[\p{L}\d\s.,'&-]+$/u", $valor)) {
            $errores[$campo] = $nombre . ' solo puede contener letras, números y los signos . , - \' &.';
        }
    }

    private function validarmedida(&$errores, $datos, $campo)
    {
        $valor = $datos[$campo];

        if ($valor === '') {
            return;
        }

        if (mb_strlen($valor) > 30
            || !preg_match('/^\d{1,3}(\.\d{1,2})?( x \d{1,3}(\.\d{1,2})?){0,2}$/', $valor)) {
            $errores[$campo] = 'La medida debe indicarse en centímetros, por ejemplo: 20 o 20 x 30.';
        }
    }

    private function validarflor($datos)
    {
        $errores = [];

        $this->validarletras($errores, $datos, 'tbinventariocategoriaflortipo', 'El tipo de flor', 50, true);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaflorcolor', 'El color', 30, true);

        $this->validarentero($errores, $datos, 'tbinventariocategoriaflorlargotallo', 1, 300,
            'El largo del tallo es obligatorio.',
            'El largo del tallo debe ser un número entero entre 1 y 300 cm.');

        $this->validarentero($errores, $datos, 'tbinventariocategoriaflordiasduracion', 1, 60,
            'Los días de duración son obligatorios.',
            'Los días de duración deben ser un número entero entre 1 y 60.');

        return $errores;
    }

    private function validarbase($datos)
    {
        $errores = [];

        if ($datos['tbinventariocategoriabasetipo'] === '') {
            $errores['tbinventariocategoriabasetipo'] = 'El tipo de base es obligatorio.';
        } elseif (!in_array($datos['tbinventariocategoriabasetipo'], $this->tiposbase, true)) {
            $errores['tbinventariocategoriabasetipo'] = 'El tipo de base seleccionado no es válido.';
        }

        if ($datos['tbinventariocategoriabasematerial'] === '') {
            $errores['tbinventariocategoriabasematerial'] = 'El material es obligatorio.';
        } elseif (!in_array($datos['tbinventariocategoriabasematerial'], $this->materialesbase, true)) {
            $errores['tbinventariocategoriabasematerial'] = 'El material seleccionado no es válido.';
        }

        $this->validarletras($errores, $datos, 'tbinventariocategoriabasecolor', 'El color', 30, true);
        $this->validarletras($errores, $datos, 'tbinventariocategoriabaseforma', 'La forma', 30, false);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriabasemedida');

        return $errores;
    }

    private function validaradorno($datos)
    {
        $errores = [];

        if ($datos['tbinventariocategoriaadornotipo'] === '') {
            $errores['tbinventariocategoriaadornotipo'] = 'El tipo de adorno es obligatorio.';
        } elseif (!in_array($datos['tbinventariocategoriaadornotipo'], $this->tiposadorno, true)) {
            $errores['tbinventariocategoriaadornotipo'] = 'El tipo de adorno seleccionado no es válido.';
        }

        $this->validarletras($errores, $datos, 'tbinventariocategoriaadornomaterial', 'El material', 50, false);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaadornocolor', 'El color', 30, false);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriaadornomedida');

        if ($datos['tbinventariocategoriaadornotipo'] === 'Tarjeta' && $datos['tbinventariocategoriaadornodiseno'] === '') {
            $errores['tbinventariocategoriaadornodiseno'] = 'En las tarjetas, el diseño u ocasión es obligatorio.';
        } else {
            $this->validartexto($errores, $datos, 'tbinventariocategoriaadornodiseno', 'El diseño', 50);
        }

        return $errores;
    }

    private function validarextra($datos)
    {
        $errores = [];

        if ($datos['tbinventariocategoriaextratipo'] === '') {
            $errores['tbinventariocategoriaextratipo'] = 'El tipo de extra es obligatorio.';
        } elseif (!in_array($datos['tbinventariocategoriaextratipo'], $this->tiposextra, true)) {
            $errores['tbinventariocategoriaextratipo'] = 'El tipo de extra seleccionado no es válido.';
        }

        $this->validartexto($errores, $datos, 'tbinventariocategoriaextramarca', 'La marca', 50);
        $this->validartexto($errores, $datos, 'tbinventariocategoriaextratamano', 'El tamaño', 30);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaextracolor', 'El color', 30, false);
        $this->validartexto($errores, $datos, 'tbinventariocategoriaextracontenido', 'El contenido', 30);

        if (in_array($datos['tbinventariocategoriaextratipo'], $this->extrasconvencimiento, true)) {
            $valor = $datos['tbinventariocategoriaextrafechavencimiento'];
            $fecha = DateTime::createFromFormat('Y-m-d', (string) $valor);

            if ($valor === null) {
                $errores['tbinventariocategoriaextrafechavencimiento'] = 'La fecha de vencimiento es obligatoria en chocolates y maquillaje.';
            } elseif (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                $errores['tbinventariocategoriaextrafechavencimiento'] = 'La fecha de vencimiento no es válida.';
            } elseif ($valor < date('Y-m-d')) {
                $errores['tbinventariocategoriaextrafechavencimiento'] = 'La fecha de vencimiento no puede ser anterior a hoy.';
            }
        }

        return $errores;
    }

    private function convertircategoria(Inventario $inventario)
    {
        $categoria = $inventario->getTbinventariocategoria();

        if (!in_array($categoria, $this->categorias, true)) {
            return [];
        }

        $atributos = $this->atributos->obtener($categoria, $inventario->getTbinventarioid());

        $datos = [];
        foreach ($this->atributos->campos($categoria) as $campo) {
            $datos[$campo] = $atributos ? $atributos->{'get' . ucfirst($campo)}() : '';
        }

        return $datos;
    }

    private function convertirarreglo(Inventario $inventario, $nombres)
    {
        return [
            'tbinventarioid'              => $inventario->getTbinventarioid(),
            'tbproveedorid'               => (int) $inventario->getTbproveedorid(),
            'tbproveedornombreempresa'    => $nombres[$inventario->getTbproveedorid()] ?? '',
            'tbinventarionombre'          => $inventario->getTbinventarionombre(),
            'tbinventariocategoria'       => $inventario->getTbinventariocategoria(),
            'tbinventariopreciopaquete'   => (float) $inventario->getTbinventariopreciopaquete(),
            'tbinventariounidadespaquete' => (int) $inventario->getTbinventariounidadespaquete(),
            'tbinventariopreciounidad'    => (float) $inventario->getTbinventariopreciounidad(),
            'tbinventariostock'           => (int) $inventario->getTbinventariostock(),
            'tbinventariostockminimo'     => (int) $inventario->getTbinventariostockminimo(),
            'tbinventarioestado'          => (int) $inventario->getTbinventarioestado(),
            'stockbajo'                   => $inventario->tieneStockBajo(),
        ];
    }

    private function respuesta($exito, $mensaje, $datos = [])
    {
        return [
            'exito'   => $exito,
            'mensaje' => $mensaje,
            'datos'   => $datos,
        ];
    }
}