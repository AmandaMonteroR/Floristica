<?php
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariocategoriarepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/../Repositorio/colorrepositorio.php';
require_once __DIR__ . '/../Repositorio/tipoflorrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariootroproveedorrepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';

class inventariocontrolador
{
    private $repositorio;
    private $atributos;
    private $proveedores;
    private $movimientos;
    private $historico;
    private $temporadas;
    private $preciosvigentes = null;
    private $colores;
    private $tiposflor;
    // Otros proveedores a los que también se les compra el insumo, cada uno con su precio
    private $otrosproveedores;
    private $conteootros = null;
    // Atributos de la categoría que tiene el producto que se está editando (un color o tipo de flor
    // que se inactivó después se puede dejar como estaba)
    private $actuales = [];
    private $categorias = ['Flor', 'Envoltura', 'Base', 'Chocolate', 'Peluche', 'Tarjeta', 'Spray', 'Accesorio'];

    // Opciones de las listas de cada categoría. La vista las usa para llenar los select
    // y el controlador para validar, así las dos siempre coinciden.
    public static function opciones()
    {
        return [
            // Flor o follaje. Los tipos de flor (Rosa, Eucalipto...) y los colores están en las tablas
            // catálogo tbtipoflor y tbcolor, y se manejan en Inventario → "Colores y tipos de flor".
            'tiposflor'               => ['Flor', 'Follaje'],
            'clasesenvoltura'         => ['Papel', 'Cinta'],
            'tiposenvoltura'          => [
                'Papel' => ['Coreano', 'Kraft', 'Seda', 'Celofán', 'Encerado'],
                'Cinta' => ['Satín', 'Organza', 'Yute', 'Raso', 'Papel'],
            ],
            'acabadospapel'           => ['Mate', 'Brillante', 'Metalizado', 'Transparente'],
            'tiposbase'               => ['Plástica', 'Canasta', 'Caja', 'Florero', 'Madera', 'Espuma floral'],
            'materialesbase'          => ['Plástico', 'Madera', 'Vidrio', 'Cerámica', 'Cartón', 'Mimbre'],
            'tiposchocolate'          => ['Con leche', 'Oscuro', 'Blanco', 'Relleno'],
            'presentacioneschocolate' => ['Barra', 'Bombón', 'Trufa', 'Caja de chocolates'],
            'tipospeluche'            => ['Oso', 'Conejo', 'Perro', 'Corazón'],
            'materialespeluche'       => ['Felpa', 'Algodón', 'Poliéster'],
            'tipostarjeta'            => ['Felicitación', 'Dedicatoria', 'Mensaje', 'Etiqueta colgante'],
            'tematicastarjeta'        => ['Cumpleaños', 'Amor', 'Aniversario', 'Graduación', 'Condolencias', 'Agradecimiento', 'Otra'],
            'materialestarjeta'       => ['Cartulina', 'Papel kraft', 'Papel perlado', 'Opalina'],
            'elaboracionestarjeta'    => ['Hecha en el negocio', 'Comprada'],
            // Spray: la pintura floral se cobra por cada flor pintada
            'tiposspray'              => ['Pintura floral', 'Abrillantador de follaje', 'Aromatizante floral'],
            // Accesorios que se le agregan a un arreglo
            'tiposaccesorio'          => ['Corona', 'Perfume', 'Splash', 'Moño', 'Topper', 'Otro'],
            'tamanosaccesorio'        => ['Pequeño', 'Mediano', 'Grande', 'Único'],
            // Personalizable (cinta y tarjeta): 1 = sí, 0 = no
            'personalizable'          => ['1' => 'Sí', '0' => 'No'],
        ];
    }

    public function __construct()
    {
        $this->repositorio = new inventariorepositorio();
        $this->atributos = new inventariocategoriarepositorio();
        $this->proveedores = new proveedorrepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->historico = new historicocontrolador();
        $this->temporadas = new preciotemporadacontrolador();
        $this->colores = new colorrepositorio();
        $this->tiposflor = new tipoflorrepositorio();
        $this->otrosproveedores = new inventariootroproveedorrepositorio();
    }

    /* ---------- Catálogos de colores y tipos de flor ---------- */

    public function catalogos()
    {
        $colores = [];
        foreach ($this->colores->listar() as $color) {
            $colores[] = [
                'id'     => (int) $color->getTbcolorid(),
                'nombre' => $color->getTbcolornombre(),
                'estado' => (int) $color->getTbcolorestado(),
                'usos'   => $this->colores->usos($color->getTbcolorid()),
            ];
        }

        $tipos = [];
        foreach ($this->tiposflor->listar() as $tipo) {
            $tipos[] = [
                'id'     => (int) $tipo->getTbtipoflorid(),
                'nombre' => $tipo->getTbtipoflornombre(),
                'clase'  => $tipo->getTbtipoflorclase(),
                'estado' => (int) $tipo->getTbtipoflorestado(),
                'usos'   => $this->tiposflor->usos($tipo->getTbtipoflorid()),
            ];
        }

        return $this->respuesta(true, '', ['colores' => $colores, 'tiposflor' => $tipos]);
    }

    // Agrega o corrige un color o un tipo de flor. $datos: catalogo (color o tipoflor), id (vacío si es nuevo), nombre, clase
    public function guardarcatalogo($datos)
    {
        $catalogo = (string) ($datos['catalogo'] ?? '');
        $id = (int) ($datos['id'] ?? 0);
        $nombre = $this->limpiartexto($datos['nombre'] ?? '');
        $clase = $this->limpiartexto($datos['clase'] ?? '');
        $errores = [];

        if (!in_array($catalogo, ['color', 'tipoflor'], true)) {
            return $this->respuesta(false, 'Catálogo no válido.');
        }

        $repositorio = $catalogo === 'color' ? $this->colores : $this->tiposflor;
        $existente = $id > 0 ? $repositorio->obtenerporid($id) : null;
        if ($id > 0 && !$existente) {
            return $this->respuesta(false, $catalogo === 'color' ? 'El color no existe.' : 'El tipo de flor no existe.');
        }

        $maximo = $catalogo === 'color' ? 30 : 50;
        if ($nombre === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > $maximo) {
            $errores['nombre'] = 'El nombre debe tener entre 3 y ' . $maximo . ' letras.';
        } elseif (!preg_match('/^[\p{L}]+( [\p{L}]+)*$/u', $nombre)) {
            $errores['nombre'] = 'El nombre solo puede contener letras y espacios.';
        } elseif ($repositorio->existenombre($nombre, $id)) {
            $errores['nombre'] = $catalogo === 'color' ? 'Ya existe un color con ese nombre.' : 'Ya existe un tipo de flor con ese nombre.';
        }

        if ($catalogo === 'tipoflor' && !in_array($clase, self::opciones()['tiposflor'], true)) {
            $errores['clase'] = 'Elija si es flor o follaje.';
        }

        if ($errores) {
            return $this->respuesta(false, reset($errores), ['errores' => $errores]);
        }

        // El nombre se guarda con la primera letra en mayúscula: "rosa" → "Rosa"
        $nombre = mb_strtoupper(mb_substr($nombre, 0, 1)) . mb_substr($nombre, 1);

        if ($catalogo === 'color') {
            $modelo = $existente ?: new Color();
            $modelo->setTbcolornombre($nombre);
        } else {
            $modelo = $existente ?: new Tipoflor();
            $modelo->setTbtipoflornombre($nombre);
            $modelo->setTbtipoflorclase($clase);
        }

        if ($existente) {
            $repositorio->actualizar($modelo);
            return $this->respuesta(true, 'Se guardó "' . $nombre . '".');
        }

        $nuevo = $repositorio->insertar($modelo);
        return $this->respuesta(true, 'Se agregó "' . $nombre . '".', ['id' => $nuevo]);
    }

    // Inactivar o reactivar un color o tipo de flor. Uno inactivo ya no se puede elegir en productos nuevos.
    public function estadocatalogo($datos)
    {
        $catalogo = (string) ($datos['catalogo'] ?? '');
        $id = (int) ($datos['id'] ?? 0);
        $estado = (string) ($datos['estado'] ?? '');

        if (!in_array($catalogo, ['color', 'tipoflor'], true) || !in_array($estado, ['0', '1'], true)) {
            return $this->respuesta(false, 'Datos no válidos.');
        }

        $repositorio = $catalogo === 'color' ? $this->colores : $this->tiposflor;
        if (!$repositorio->obtenerporid($id)) {
            return $this->respuesta(false, $catalogo === 'color' ? 'El color no existe.' : 'El tipo de flor no existe.');
        }

        $repositorio->cambiarestado($id, (int) $estado);
        return $this->respuesta(true, $estado === '1' ? 'Se reactivó.' : 'Se inactivó. Los productos que ya lo tienen no cambian.');
    }

    // Color: el id de un color del catálogo
    private function validarcolor(&$errores, $datos, $campo, $nombre, $obligatorio)
    {
        $valor = (string) ($datos[$campo] ?? '');

        if ($valor === '') {
            if ($obligatorio) {
                $errores[$campo] = $nombre . ' es obligatorio.';
            }
            return;
        }

        $color = ctype_digit($valor) ? $this->colores->obtenerporid((int) $valor) : null;
        if (!$color) {
            $errores[$campo] = $nombre . ' seleccionado no es válido.';
        } elseif ((int) $color->getTbcolorestado() !== 1 && (string) ($this->actuales[$campo] ?? '') !== $valor) {
            $errores[$campo] = $nombre . ' seleccionado está inactivo.';
        }
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
        $datos['preciostemporada'] = $this->temporadas->paraformulario('inventario', $inventario->getTbinventarioid());
        $datos['movimientos'] = $this->convertirmovimientos($inventario->getTbinventarioid());
        $datos['otrosproveedores'] = $this->convertirotros($inventario);

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $preciostemporada = $this->temporadas->limpiar($datos['temporadas'] ?? []);
        $otros = $this->limpiarotros($datos['otrosproveedores'] ?? []);
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);
        $errores += $this->validartemporadas($preciostemporada, $datos, $errores);
        $errores += $this->validarotros($otros, $datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbinventariopreciounidad'] = $this->calcularpreciounidad($datos);
        $inventario = new Inventario($datos);

        $id = $this->entransaccion(function () use ($inventario, $datos, $preciostemporada, $otros, $usuario) {
            $id = $this->repositorio->insertar($inventario);
            $this->temporadas->guardar('inventario', $id, $preciostemporada, $usuario['id']);
            $this->guardarotros($id, $otros, $usuario['id']);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);
            $this->movimientos->registrar($id, 'Inicial', (int) $datos['tbinventariostock'], $usuario['id']);
            $this->historico->registrar('inventario', $id, [], $this->datoshistorico($inventario), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Insumo registrado correctamente.', ['tbinventarioid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbinventarioid'] ?? 0);
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        $preciostemporada = $this->temporadas->limpiar($datos['temporadas'] ?? []);
        $otros = $this->limpiarotros($datos['otrosproveedores'] ?? []);
        $datos = $this->limpiar($datos);
        $this->actuales = $this->convertircategoria($inventario);
        $errores = $this->validar($datos, $id);
        $errores += $this->validartemporadas($preciostemporada, $datos, $errores);
        $errores += $this->validarotros($otros, $datos, $this->otrosproveedores->listarporinsumo($id));

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
        $inventario->setTbinventarioprecioventa($datos['tbinventarioprecioventa']);
        $inventario->setTbinventariostock($datos['tbinventariostock']);
        $inventario->setTbinventariostockminimo($datos['tbinventariostockminimo']);

        $this->entransaccion(function () use ($inventario, $datos, $preciostemporada, $otros, $id, $stockanterior, $anteriores, $usuario) {
            $this->repositorio->actualizar($inventario);
            $this->temporadas->guardar('inventario', $id, $preciostemporada, $usuario['id']);
            $this->guardarotros($id, $otros, $usuario['id']);
            $this->historico->registrar('inventario', $id, $anteriores, $this->datoshistorico($inventario), $usuario['id']);
            $this->atributos->eliminar($id);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);

            // Si se corrigió el stock a mano, queda como ajuste
            $diferencia = (int) $datos['tbinventariostock'] - $stockanterior;
            if ($diferencia !== 0) {
                $this->movimientos->registrar($id, 'Ajuste', $diferencia, $usuario['id']);
            }
        });

        return $this->respuesta(true, 'Insumo actualizado correctamente.');
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

        return $this->respuesta(true, 'Insumo inactivado correctamente.');
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

        return $this->respuesta(true, 'Insumo reactivado correctamente.');
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
            'precioventa' => 'Precio de venta',
            'preciotemporada' => 'Precio del proveedor en temporada alta',
            'otroproveedor' => 'Otro proveedor',
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
                case 'precioventa':
                    return historicocontrolador::monto($valor) . ' por unidad';
                case 'preciotemporada':
                    return $this->temporadas->textohistorial($valor);
                case 'otroproveedor':
                    // "2|7500.00" → "Vivero Orosi: ₡7 500,00 el paquete"; "2|" → "Vivero Orosi: ya no"
                    [$idproveedor, $precio] = explode('|', (string) $valor) + [1 => ''];
                    return ($nombres[$idproveedor] ?? 'Proveedor eliminado') . ': '
                        . ($precio === '' ? 'ya no se le compra' : historicocontrolador::monto($precio) . ' el paquete');
                case 'estado':
                    return historicocontrolador::estado($valor);
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('inventario', $id, $etiquetas, $formatear, ['preciotemporada', 'otroproveedor']));
    }

    /* ---------- Otros proveedores del insumo ---------- */

    // Filas del formulario: [['tbproveedorid' => '2', 'preciopaquete' => '7500'], ...]. Las vacías no cuentan.
    private function limpiarotros($filas)
    {
        if (!is_array($filas)) {
            return [];
        }

        $limpias = [];
        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $proveedor = $this->limpiartexto($fila['tbproveedorid'] ?? '');
            $precio = str_replace(',', '.', $this->limpiartexto($fila['preciopaquete'] ?? ''));
            if ($proveedor !== '' || $precio !== '') {
                $limpias[] = ['tbproveedorid' => $proveedor, 'preciopaquete' => $precio];
            }
        }

        return $limpias;
    }

    // Cada otro proveedor: que exista, esté activo (o ya estuviera antes), no sea el principal ni se repita,
    // y que su precio del paquete sea un monto válido. $antes: los que ya tenía [idproveedor => precio].
    private function validarotros($otros, $datos, $antes = [])
    {
        $usados = [];
        foreach ($otros as $indice => $otro) {
            $fila = count($otros) > 1 ? 'Otro proveedor ' . ($indice + 1) . ': ' : '';
            $proveedor = ctype_digit($otro['tbproveedorid']) ? $this->proveedores->obtenerporid((int) $otro['tbproveedorid']) : null;

            if ($otro['tbproveedorid'] === '') {
                return ['otrosproveedores' => $fila . 'elija el proveedor.'];
            }
            if (!$proveedor) {
                return ['otrosproveedores' => $fila . 'el proveedor no existe.'];
            }
            $nombre = $proveedor->getTbproveedornombreempresa();
            if (!$proveedor->estaActivo() && !isset($antes[(int) $otro['tbproveedorid']])) {
                return ['otrosproveedores' => $fila . $nombre . ' está inactivo.'];
            }
            if ($otro['tbproveedorid'] === $datos['tbproveedorid']) {
                return ['otrosproveedores' => $fila . $nombre . ' ya es el proveedor principal.'];
            }
            if (in_array($otro['tbproveedorid'], $usados, true)) {
                return ['otrosproveedores' => $fila . $nombre . ' está dos veces.'];
            }
            if ($otro['preciopaquete'] === '') {
                return ['otrosproveedores' => $fila . 'escriba cuánto cobra ' . $nombre . ' por el paquete.'];
            }
            if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $otro['preciopaquete']) || (float) $otro['preciopaquete'] <= 0) {
                return ['otrosproveedores' => $fila . 'el precio de ' . $nombre . ' debe ser un monto mayor que 0, con máximo 2 decimales.'];
            }
            $usados[] = $otro['tbproveedorid'];
        }

        return [];
    }

    // Guarda los otros proveedores y deja en el historial solo lo que cambió ("idproveedor|precio",
    // o "idproveedor|" si se quitó)
    private function guardarotros($id, $otros, $idempleado)
    {
        $antes = $this->otrosproveedores->listarporinsumo($id);
        $nuevos = [];
        foreach ($otros as $otro) {
            $nuevos[(int) $otro['tbproveedorid']] = number_format((float) $otro['preciopaquete'], 2, '.', '');
        }

        foreach ($antes as $idproveedor => $precio) {
            if (!isset($nuevos[$idproveedor])) {
                $this->otrosproveedores->eliminar($id, $idproveedor);
                $this->historico->registrar('inventario', $id, [], ['otroproveedor' => $idproveedor . '|'], $idempleado);
            }
        }

        foreach ($nuevos as $idproveedor => $precio) {
            if (isset($antes[$idproveedor]) && number_format($antes[$idproveedor], 2, '.', '') === $precio) {
                continue;
            }
            $this->otrosproveedores->guardar($id, $idproveedor, $precio);
            $this->historico->registrar('inventario', $id, [], ['otroproveedor' => $idproveedor . '|' . $precio], $idempleado);
        }
    }

    // Los otros proveedores del insumo con su precio del paquete y de la unidad, para compararlos con el principal
    private function convertirotros(Inventario $inventario)
    {
        $unidades = max(1, (int) $inventario->getTbinventariounidadespaquete());
        $lista = [];
        foreach ($this->otrosproveedores->listarporinsumo($inventario->getTbinventarioid()) as $idproveedor => $precio) {
            $proveedor = $this->proveedores->obtenerporid($idproveedor);
            $lista[] = [
                'tbproveedorid'            => $idproveedor,
                'tbproveedornombreempresa' => $proveedor ? $proveedor->getTbproveedornombreempresa() : 'Proveedor eliminado',
                'activo'                   => $proveedor ? $proveedor->estaActivo() : false,
                'preciopaquete'            => $precio,
                'preciounidad'             => round($precio / $unidades, 2),
            ];
        }

        return $lista;
    }

    // Lo que cobra el proveedor por el paquete en cada temporada
    private function validartemporadas($preciostemporada, $datos, $errores)
    {
        $error = $this->temporadas->validar($preciostemporada);

        return $error !== '' ? ['temporadas' => $error] : [];
    }

    // Temporadas para el formulario de un producto nuevo
    public function temporadas()
    {
        return $this->respuesta(true, '', $this->temporadas->paraformulario('inventario', 0));
    }

    // Flores y follajes activos sin precio del proveedor para la temporada vigente (para el aviso flotante).
    // Solo las flores, porque son las que suben de precio en temporada.
    public function pendientestemporada()
    {
        $registros = [];
        foreach ($this->repositorio->listar('', 'Flor', 1) as $inventario) {
            $registros[] = [
                'id'           => (int) $inventario->getTbinventarioid(),
                'nombre'       => $inventario->getTbinventarionombre(),
                'categoria'    => 'Paquete de ' . (int) $inventario->getTbinventariounidadespaquete(),
                'precionormal' => (float) $inventario->getTbinventariopreciopaquete(),
            ];
        }

        return $this->respuesta(true, '', $this->temporadas->pendientes('inventario', $registros));
    }

    // Guarda de una vez lo que cobra el proveedor en la temporada vigente: precios[idinventario] = precio del paquete
    public function guardarpreciostemporada($datos, $usuario)
    {
        if (!$this->temporadas->vigente()) {
            return $this->respuesta(false, 'No hay una temporada en uso.');
        }

        $registros = [];
        foreach ($this->repositorio->listar('', '', 1) as $inventario) {
            $registros[(int) $inventario->getTbinventarioid()] = ['nombre' => $inventario->getTbinventarionombre()];
        }

        $resultado = $this->entransaccion(function () use ($datos, $registros, $usuario) {
            return $this->temporadas->guardarvarios('inventario', $datos['precios'] ?? [], $registros, $usuario['id']);
        });

        if ($resultado['errores']) {
            return $this->respuesta(false, 'Revise los precios.', ['errores' => $resultado['errores']]);
        }

        return $this->respuesta(true, $resultado['guardados'] === 0
            ? 'No se escribió ningún precio.'
            : 'Se guardaron ' . $resultado['guardados'] . ' precios de ' . $this->temporadas->vigente()->getTbtemporadanombre() . '.');
    }

    // Datos del producto que llevan histórico, tal como se guardan
    private function datoshistorico(Inventario $inventario)
    {
        return [
            'nombre'      => $inventario->getTbinventarionombre(),
            'proveedor'   => (int) $inventario->getTbproveedorid(),
            'precio'      => number_format((float) $inventario->getTbinventariopreciopaquete(), 2, '.', '')
                . ' / ' . (int) $inventario->getTbinventariounidadespaquete(),
            'precioventa' => number_format((float) $inventario->getTbinventarioprecioventa(), 2, '.', ''),
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
        // Se piden los 20 más recientes y se muestran del más viejo al más nuevo
        foreach (array_reverse($this->movimientos->listar($idinventario)) as $fila) {
            switch ($fila['tbinventariomovimientotipo']) {
                case 'Inicial':
                    $motivo = 'Stock inicial al registrar el producto';
                    break;
                case 'Entrada':
                    $motivo = 'Pedido N.º ' . $fila['tbpedidoid'] . ' recibido';
                    break;
                case 'Salida':
                    $motivo = 'Solicitud de pedido de arreglo N.º ' . $fila['tbsolicitudencargoid'] . ' terminada';
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
            'tbinventarioprecioventa',
            'tbinventariostock',
            'tbinventariostockminimo',
        ];

        $limpios = [];
        foreach ($campos as $campo) {
            $limpios[$campo] = $this->limpiartexto($datos[$campo] ?? '');
        }

        $limpios['tbinventariopreciopaquete'] = str_replace(',', '.', $limpios['tbinventariopreciopaquete']);
        $limpios['tbinventarioprecioventa'] = str_replace(',', '.', $limpios['tbinventarioprecioventa']);

        if (in_array($limpios['tbinventariocategoria'], $this->categorias, true)) {
            foreach ($this->atributos->campos($limpios['tbinventariocategoria']) as $campo) {
                $limpios[$campo] = $this->limpiartexto($datos[$campo] ?? '');
            }
        }

        // Las medidas se guardan con el formato "20 x 30"
        $medidas = ['tbinventariocategoriabasemedida', 'tbinventariocategoriaenvolturamedida', 'tbinventariocategoriatarjetamedida'];
        foreach ($medidas as $campo) {
            if (isset($limpios[$campo])) {
                $limpios[$campo] = preg_replace('/\s*[xX×]\s*/u', ' x ', $limpios[$campo]);
            }
        }

        // El acabado solo aplica al papel y "personalizable" solo a la cinta; lo que no aplica queda vacío (NULL)
        if (isset($limpios['tbinventariocategoriaenvolturaclase'])) {
            $clase = $limpios['tbinventariocategoriaenvolturaclase'];

            if ($clase !== 'Papel' || $limpios['tbinventariocategoriaenvolturaacabado'] === '') {
                $limpios['tbinventariocategoriaenvolturaacabado'] = null;
            }
            if ($clase !== 'Cinta') {
                $limpios['tbinventariocategoriaenvolturapersonalizable'] = null;
            }
        }

        if (($limpios['tbinventariocategoriachocolatefechavencimiento'] ?? null) === '') {
            $limpios['tbinventariocategoriachocolatefechavencimiento'] = null;
        }

        // Accesorio: el color y el material son opcionales
        foreach (['tbinventariocategoriaaccesoriocolor', 'tbinventariocategoriaaccesoriomaterial'] as $campo) {
            if (($limpios[$campo] ?? null) === '') {
                $limpios[$campo] = null;
            }
        }

        // Spray: el color es de la pintura, el aroma del aromatizante y el cobro por flor solo de la pintura
        if (isset($limpios['tbinventariocategoriaspraytipo'])) {
            $tipo = $limpios['tbinventariocategoriaspraytipo'];
            $limpios['tbinventariocategoriaspraycobroflor'] = str_replace(',', '.', $limpios['tbinventariocategoriaspraycobroflor']);

            if ($tipo !== 'Pintura floral') {
                $limpios['tbinventariocategoriaspraycolor'] = null;
                $limpios['tbinventariocategoriaspraycobroflor'] = null;
            }
            if ($tipo !== 'Aromatizante floral') {
                $limpios['tbinventariocategoriasprayaroma'] = null;
            }
            if ($limpios['tbinventariocategoriasprayfechavencimiento'] === '') {
                $limpios['tbinventariocategoriasprayfechavencimiento'] = null;
            }
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

        // El precio de venta por unidad no puede quedar por debajo de lo que cuesta la unidad
        if ($datos['tbinventarioprecioventa'] === '') {
            $errores['tbinventarioprecioventa'] = 'El precio de venta es obligatorio.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $datos['tbinventarioprecioventa'])
            || (float) $datos['tbinventarioprecioventa'] <= 0) {
            $errores['tbinventarioprecioventa'] = 'El precio de venta debe ser un monto mayor que 0, con máximo 2 decimales.';
        } elseif (!isset($errores['tbinventariopreciopaquete']) && !isset($errores['tbinventariounidadespaquete'])
            && (float) $datos['tbinventarioprecioventa'] < $this->calcularpreciounidad($datos)) {
            $errores['tbinventarioprecioventa'] = 'El precio de venta no puede ser menor que el costo por unidad ('
                . historicocontrolador::monto($this->calcularpreciounidad($datos)) . ').';
        }

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
            case 'Envoltura':
                $errores += $this->validarenvoltura($datos);
                break;
            case 'Base':
                $errores += $this->validarbase($datos);
                break;
            case 'Chocolate':
                $errores += $this->validarchocolate($datos);
                break;
            case 'Peluche':
                $errores += $this->validarpeluche($datos);
                break;
            case 'Tarjeta':
                $errores += $this->validartarjeta($datos);
                break;
            case 'Spray':
                $errores += $this->validarspray($datos);
                break;
            case 'Accesorio':
                $errores += $this->validaraccesorio($datos);
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

    // Revisa que el valor sea una de las opciones de la lista
    private function validarlista(&$errores, $datos, $campo, $nombre, $lista, $obligatorio = true)
    {
        $valor = $datos[$campo];

        if ($valor === '' || $valor === null) {
            if ($obligatorio) {
                $errores[$campo] = $nombre . ' es obligatorio.';
            }
        } elseif (!in_array($valor, $lista, true)) {
            $errores[$campo] = $nombre . ' seleccionado no es válido.';
        }
    }

    private function validarflor($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarlista($errores, $datos, 'tbinventariocategoriaflortipo', 'Si es flor o follaje', $opciones['tiposflor']);

        // El tipo de flor viene del catálogo y tiene que ser de la misma clase (una rosa no puede ser follaje)
        $valor = (string) $datos['tbinventariocategoriaflorvariedad'];
        $tipo = ctype_digit($valor) ? $this->tiposflor->obtenerporid((int) $valor) : null;
        if ($valor === '') {
            $errores['tbinventariocategoriaflorvariedad'] = 'El tipo de flor es obligatorio.';
        } elseif (!$tipo) {
            $errores['tbinventariocategoriaflorvariedad'] = 'El tipo de flor seleccionado no es válido.';
        } elseif ((int) $tipo->getTbtipoflorestado() !== 1 && (string) ($this->actuales['tbinventariocategoriaflorvariedad'] ?? '') !== $valor) {
            $errores['tbinventariocategoriaflorvariedad'] = 'El tipo de flor seleccionado está inactivo.';
        } elseif (!isset($errores['tbinventariocategoriaflortipo']) && $tipo->getTbtipoflorclase() !== $datos['tbinventariocategoriaflortipo']) {
            $errores['tbinventariocategoriaflorvariedad'] = $tipo->getTbtipoflornombre() . ' es ' . mb_strtolower($tipo->getTbtipoflorclase())
                . ', no ' . mb_strtolower($datos['tbinventariocategoriaflortipo']) . '.';
        }

        $this->validarcolor($errores, $datos, 'tbinventariocategoriaflorcolor', 'El color', true);

        $this->validarentero($errores, $datos, 'tbinventariocategoriaflorlargotallo', 1, 300,
            'El largo del tallo es obligatorio.',
            'El largo del tallo debe ser un número entero entre 1 y 300 cm.');

        $this->validarentero($errores, $datos, 'tbinventariocategoriaflordiasduracion', 1, 60,
            'Los días de duración son obligatorios.',
            'Los días de duración deben ser un número entero entre 1 y 60.');

        return $errores;
    }

    // Papel o cinta
    private function validarenvoltura($datos)
    {
        $errores = [];
        $opciones = self::opciones();
        $clase = $datos['tbinventariocategoriaenvolturaclase'];

        $this->validarlista($errores, $datos, 'tbinventariocategoriaenvolturaclase', 'Si es papel o cinta', $opciones['clasesenvoltura']);

        if (!isset($errores['tbinventariocategoriaenvolturaclase'])) {
            $this->validarlista($errores, $datos, 'tbinventariocategoriaenvolturatipo',
                $clase === 'Papel' ? 'El tipo de papel' : 'El tipo de cinta', $opciones['tiposenvoltura'][$clase]);
        }

        $this->validarcolor($errores, $datos, 'tbinventariocategoriaenvolturacolor', 'El color', true);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaenvolturadiseno', 'El diseño', 50, false);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriaenvolturamedida');
        $this->validarletras($errores, $datos, 'tbinventariocategoriaenvolturamaterial', 'El material', 30, false);

        if ($clase === 'Papel') {
            $this->validarlista($errores, $datos, 'tbinventariocategoriaenvolturaacabado', 'El acabado', $opciones['acabadospapel'], false);
        }

        if ($clase === 'Cinta') {
            $this->validarlista($errores, $datos, 'tbinventariocategoriaenvolturapersonalizable',
                'Si es personalizable', array_map('strval', array_keys($opciones['personalizable'])));
        }

        return $errores;
    }

    private function validarbase($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarlista($errores, $datos, 'tbinventariocategoriabasetipo', 'El tipo de base', $opciones['tiposbase']);
        $this->validarlista($errores, $datos, 'tbinventariocategoriabasematerial', 'El material', $opciones['materialesbase']);
        $this->validarcolor($errores, $datos, 'tbinventariocategoriabasecolor', 'El color', true);
        $this->validarletras($errores, $datos, 'tbinventariocategoriabaseforma', 'La forma', 30, false);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriabasemedida');
        $this->validarletras($errores, $datos, 'tbinventariocategoriabasediseno', 'El diseño', 50, false);

        return $errores;
    }

    private function validarchocolate($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarlista($errores, $datos, 'tbinventariocategoriachocolatetipo', 'El tipo de chocolate', $opciones['tiposchocolate']);
        $this->validartexto($errores, $datos, 'tbinventariocategoriachocolatemarca', 'La marca', 50);
        $this->validarlista($errores, $datos, 'tbinventariocategoriachocolatepresentacion', 'La presentación', $opciones['presentacioneschocolate']);

        $this->validarentero($errores, $datos, 'tbinventariocategoriachocolatepeso', 1, 5000,
            'El peso es obligatorio.',
            'El peso debe ser un número entero entre 1 y 5000 gramos.');

        $this->validarletras($errores, $datos, 'tbinventariocategoriachocolatesabor', 'El sabor', 30, false);

        $valor = $datos['tbinventariocategoriachocolatefechavencimiento'];
        $fecha = DateTime::createFromFormat('Y-m-d', (string) $valor);

        if ($valor === null) {
            $errores['tbinventariocategoriachocolatefechavencimiento'] = 'La fecha de vencimiento es obligatoria.';
        } elseif (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            $errores['tbinventariocategoriachocolatefechavencimiento'] = 'La fecha de vencimiento no es válida.';
        } elseif ($valor < date('Y-m-d')) {
            $errores['tbinventariocategoriachocolatefechavencimiento'] = 'La fecha de vencimiento no puede ser anterior a hoy.';
        }

        return $errores;
    }

    private function validarpeluche($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarletras($errores, $datos, 'tbinventariocategoriapeluchetipo', 'El tipo de peluche', 30, true);

        $this->validarentero($errores, $datos, 'tbinventariocategoriapeluchetamano', 1, 200,
            'El tamaño es obligatorio.',
            'El tamaño debe ser un número entero entre 1 y 200 cm.');

        $this->validarcolor($errores, $datos, 'tbinventariocategoriapeluchecolor', 'El color', true);
        $this->validartexto($errores, $datos, 'tbinventariocategoriapeluchediseno', 'El diseño', 50);
        $this->validarlista($errores, $datos, 'tbinventariocategoriapeluchematerial', 'El material', $opciones['materialespeluche'], false);

        return $errores;
    }

    private function validartarjeta($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetatipo', 'El tipo de tarjeta', $opciones['tipostarjeta']);
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetatematica', 'La temática', $opciones['tematicastarjeta']);
        $this->validarcolor($errores, $datos, 'tbinventariocategoriatarjetacolor', 'El color', true);
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetamaterial', 'El material', $opciones['materialestarjeta']);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriatarjetamedida');
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetapersonalizable',
            'Si es personalizable', array_map('strval', array_keys($opciones['personalizable'])));
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetaelaboracion', 'La elaboración', $opciones['elaboracionestarjeta']);

        return $errores;
    }

    private function validaraccesorio($datos)
    {
        $errores = [];
        $opciones = self::opciones();

        $this->validarlista($errores, $datos, 'tbinventariocategoriaaccesoriotipo', 'El tipo de accesorio', $opciones['tiposaccesorio']);
        $this->validarlista($errores, $datos, 'tbinventariocategoriaaccesoriotamano', 'El tamaño', $opciones['tamanosaccesorio']);
        if ($datos['tbinventariocategoriaaccesoriocolor'] !== null) {
            $this->validarcolor($errores, $datos, 'tbinventariocategoriaaccesoriocolor', 'El color', false);
        }
        if ($datos['tbinventariocategoriaaccesoriomaterial'] !== null) {
            $this->validartexto($errores, $datos, 'tbinventariocategoriaaccesoriomaterial', 'El material', 30);
        }

        return $errores;
    }

    private function validarspray($datos)
    {
        $errores = [];
        $opciones = self::opciones();
        $tipo = $datos['tbinventariocategoriaspraytipo'];

        $this->validarlista($errores, $datos, 'tbinventariocategoriaspraytipo', 'El tipo de spray', $opciones['tiposspray']);

        if ($datos['tbinventariocategoriaspraymarca'] === '') {
            $errores['tbinventariocategoriaspraymarca'] = 'La marca es obligatoria.';
        } else {
            $this->validartexto($errores, $datos, 'tbinventariocategoriaspraymarca', 'La marca', 50);
        }

        if ($tipo === 'Pintura floral') {
            $this->validarcolor($errores, $datos, 'tbinventariocategoriaspraycolor', 'El color de la pintura', true);

            $cobro = (string) $datos['tbinventariocategoriaspraycobroflor'];
            if ($cobro === '') {
                $errores['tbinventariocategoriaspraycobroflor'] = 'Escriba cuánto se cobra por cada flor pintada.';
            } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $cobro) || (float) $cobro <= 0) {
                $errores['tbinventariocategoriaspraycobroflor'] = 'El cobro por flor debe ser un monto mayor que 0, con máximo 2 decimales.';
            }
        }

        if ($tipo === 'Aromatizante floral') {
            $this->validarletras($errores, $datos, 'tbinventariocategoriasprayaroma', 'El aroma', 30, true);
        }

        $this->validarentero($errores, $datos, 'tbinventariocategoriaspraycontenido', 1, 5000,
            'El contenido es obligatorio.',
            'El contenido debe ser un número entero entre 1 y 5000 ml.');

        // La fecha de vencimiento es opcional
        $valor = $datos['tbinventariocategoriasprayfechavencimiento'];
        if ($valor !== null) {
            $fecha = DateTime::createFromFormat('Y-m-d', (string) $valor);
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                $errores['tbinventariocategoriasprayfechavencimiento'] = 'La fecha de vencimiento no es válida.';
            } elseif ($valor < date('Y-m-d')) {
                $errores['tbinventariocategoriasprayfechavencimiento'] = 'La fecha de vencimiento no puede ser anterior a hoy.';
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

            // El color y el tipo de flor se guardan como id; también se manda el nombre para mostrarlo
            if (substr($campo, -5) === 'color') {
                $color = $datos[$campo] !== null && $datos[$campo] !== '' ? $this->colores->obtenerporid($datos[$campo]) : null;
                $datos[$campo . 'nombre'] = $color ? $color->getTbcolornombre() : null;
            } elseif ($campo === 'tbinventariocategoriaflorvariedad') {
                $tipo = $datos[$campo] !== '' ? $this->tiposflor->obtenerporid($datos[$campo]) : null;
                $datos[$campo . 'nombre'] = $tipo ? $tipo->getTbtipoflornombre() : null;
            }
        }

        return $datos;
    }

    private function contarotros($id)
    {
        if ($this->conteootros === null) {
            $this->conteootros = $this->otrosproveedores->contar();
        }

        return $this->conteootros[(int) $id] ?? 0;
    }

    private function convertirarreglo(Inventario $inventario, $nombres)
    {
        if ($this->preciosvigentes === null) {
            $this->preciosvigentes = $this->temporadas->productos();
        }

        $id = (int) $inventario->getTbinventarioid();
        $vigente = $this->temporadas->datosvigente();
        $actual = $this->preciosvigentes[$id] ?? null;
        $contemporada = $actual && $actual['contemporada'];

        return [
            // Costo y precio de venta de ahora: los de la temporada en uso (calculados) o los normales
            'preciounidadactual'          => $contemporada ? $actual['costo'] : (float) $inventario->getTbinventariopreciounidad(),
            'precioventaactual'           => $contemporada ? $actual['venta'] : (float) $inventario->getTbinventarioprecioventa(),
            'temporadaprecio'             => $contemporada ? $vigente['tbtemporadanombre'] : '',
            'tbinventarioid'              => $inventario->getTbinventarioid(),
            'tbproveedorid'               => (int) $inventario->getTbproveedorid(),
            'tbproveedornombreempresa'    => $nombres[$inventario->getTbproveedorid()] ?? '',
            'cantidadotrosproveedores'    => $this->contarotros($id),
            'tbinventarionombre'          => $inventario->getTbinventarionombre(),
            'tbinventariocategoria'       => $inventario->getTbinventariocategoria(),
            'tbinventariopreciopaquete'   => (float) $inventario->getTbinventariopreciopaquete(),
            'tbinventariounidadespaquete' => (int) $inventario->getTbinventariounidadespaquete(),
            'tbinventariopreciounidad'    => (float) $inventario->getTbinventariopreciounidad(),
            'tbinventarioprecioventa'     => (float) $inventario->getTbinventarioprecioventa(),
            'tbinventariostock'           => (int) $inventario->getTbinventariostock(),
            'tbinventariostockminimo'     => (int) $inventario->getTbinventariostockminimo(),
            'tbinventariostockreservado'  => (int) $inventario->getTbinventariostockreservado(),
            'tbinventariodisponible'      => $inventario->getDisponible(),
            'tbinventariofaltante'        => $inventario->getFaltante(),
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
