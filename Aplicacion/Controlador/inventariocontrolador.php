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
require_once __DIR__ . '/gastocontrolador.php';

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
    private $otrosproveedores;
    private $conteootros = null;
    private $gastos;
    private $actuales = [];
    private $existente = null;
    private $categorias = ['Flor', 'Envoltura', 'Base', 'Chocolate', 'Peluche', 'Tarjeta', 'Spray', 'Accesorio'];
    private $carpetafotos;
    private $tiposfoto = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private $tamanomaximo = 2097152;

    public static function opciones()
    {
        return [
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
            'tiposspray'              => ['Pintura floral', 'Abrillantador de follaje', 'Aromatizante floral'],
            'tiposaccesorio'          => ['Corona', 'Perfume', 'Splash', 'Moño', 'Topper', 'Otro'],
            'tamanosaccesorio'        => ['Pequeño', 'Mediano', 'Grande', 'Único'],
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
        $this->carpetafotos = __DIR__ . '/../../Publico/imagenes/productos/';
        $this->gastos = new gastocontrolador();
    }

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
        } elseif ($catalogo === 'color' && !self::escolor($nombre)) {
            $errores['nombre'] = '"' . $nombre . '" no es un color. Escriba un color, por ejemplo "Turquesa", "Verde lima" o "Azul oscuro".';
        } elseif ($repositorio->existenombre($nombre, $id)) {
            $errores['nombre'] = $catalogo === 'color' ? 'Ya existe un color con ese nombre.' : 'Ya existe un tipo de flor con ese nombre.';
        }

        if ($catalogo === 'tipoflor' && !in_array($clase, self::opciones()['tiposflor'], true)) {
            $errores['clase'] = 'Elija si es flor o follaje.';
        }

        if ($errores) {
            return $this->respuesta(false, reset($errores), ['errores' => $errores]);
        }

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
            return $this->respuesta(false, 'Revise los datos ingresados.',
                ['errores' => $errores] + ($this->existente ? ['existente' => $this->existente] : []));
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

        $anteriores = $this->datoshistorico($inventario);

        $inventario->setTbproveedorid($datos['tbproveedorid']);
        $inventario->setTbinventarionombre($datos['tbinventarionombre']);
        $inventario->setTbinventariocategoria($datos['tbinventariocategoria']);
        $inventario->setTbinventariopreciopaquete($datos['tbinventariopreciopaquete']);
        $inventario->setTbinventariounidadespaquete($datos['tbinventariounidadespaquete']);
        $inventario->setTbinventariopreciounidad($this->calcularpreciounidad($datos));
        $inventario->setTbinventarioprecioventa($datos['tbinventarioprecioventa']);
        $inventario->setTbinventariostockminimo($datos['tbinventariostockminimo']);

        $this->entransaccion(function () use ($inventario, $datos, $preciostemporada, $otros, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($inventario);
            $this->temporadas->guardar('inventario', $id, $preciostemporada, $usuario['id']);
            $this->guardarotros($id, $otros, $usuario['id']);
            $this->historico->registrar('inventario', $id, $anteriores, $this->datoshistorico($inventario), $usuario['id']);
            $this->atributos->eliminar($id);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);
        });

        return $this->respuesta(true, 'Insumo actualizado correctamente.');
    }

    const TIPOCOMPRA = 'Compra';
    const TIPODANADO = 'Dañado';

    public function movimientostock($datos, $usuario)
    {
        $id = (int) ($datos['tbinventarioid'] ?? 0);
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }
        if (!$inventario->estaActivo()) {
            return $this->respuesta(false, 'El producto está inactivo. Reactívelo para cambiarle el stock.');
        }

        $sentido = (string) ($datos['sentido'] ?? '');
        $stock = (int) $inventario->getTbinventariostock();
        $unidadespaquete = max(1, (int) $inventario->getTbinventariounidadespaquete());
        $errores = [];
        $idproveedor = null;
        $paquetes = 0;
        $pagado = 0;

        if ($sentido === 'sumar') {
            $idproveedor = (int) ($datos['tbproveedorid'] ?? 0);
            $permitidos = array_keys($this->otrosproveedores->listarporinsumo($id));
            $permitidos[] = (int) $inventario->getTbproveedorid();
            $proveedor = $idproveedor > 0 ? $this->proveedores->obtenerporid($idproveedor) : null;
            if ($idproveedor <= 0) {
                $errores['tbproveedorid'] = 'Elija a qué proveedor se lo compró.';
            } elseif (!in_array($idproveedor, $permitidos, true) || !$proveedor) {
                $errores['tbproveedorid'] = 'Ese proveedor no vende este insumo. Agréguelo antes en "Otros proveedores".';
            } elseif (!$proveedor->estaActivo()) {
                $errores['tbproveedorid'] = 'Ese proveedor está inactivo.';
            }

            $texto = trim((string) ($datos['paquetes'] ?? ''));
            if ($texto === '') {
                $errores['paquetes'] = 'Escriba cuántos paquetes compró.';
            } elseif (!preg_match('/^\d{1,4}$/', $texto) || (int) $texto < 1) {
                $errores['paquetes'] = 'Los paquetes deben ser un número entero de 1 a 9999.';
            } elseif ($stock + (int) $texto * $unidadespaquete > 999999) {
                $errores['paquetes'] = 'El stock no puede pasar de 999999 unidades.';
            }
            $paquetes = (int) $texto;
            $cantidad = $paquetes * $unidadespaquete;
            $preciopaquete = $idproveedor === (int) $inventario->getTbproveedorid()
                ? (float) $inventario->getTbinventariopreciopaquete()
                : (float) ($this->otrosproveedores->listarporinsumo($id)[$idproveedor] ?? 0);
            $pagado = round($paquetes * $preciopaquete, 2);
        } elseif ($sentido === 'restar') {
            $texto = trim((string) ($datos['cantidad'] ?? ''));
            if ($texto === '') {
                $errores['cantidad'] = 'Escriba cuántas unidades se dañaron.';
            } elseif (!preg_match('/^\d{1,6}$/', $texto) || (int) $texto < 1) {
                $errores['cantidad'] = 'La cantidad debe ser un número entero mayor que 0.';
            } elseif ((int) $texto > $stock) {
                $errores['cantidad'] = 'No puede rebajar más de lo que hay en stock (' . $stock . ').';
            }
            $cantidad = (int) $texto;
        } else {
            return $this->respuesta(false, 'Elija si va a agregar stock o rebajar las dañadas.');
        }

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $this->entransaccion(function () use ($id, $sentido, $cantidad, $idproveedor, $usuario, $inventario, $paquetes, $pagado) {
            if ($sentido === 'sumar') {
                $this->repositorio->sumarstock($id, $cantidad);
                $idmovimiento = $this->movimientos->registrar($id, self::TIPOCOMPRA, $cantidad, $usuario['id'], null, null, $idproveedor);
                if ($pagado > 0) {
                    $proveedor = $this->proveedores->obtenerporid($idproveedor);
                    $this->gastos->registrarcompra('Compra', $idmovimiento, 'Compra sin pedido: ' . $paquetes
                        . ($paquetes === 1 ? ' paquete de ' : ' paquetes de ') . $inventario->getTbinventarionombre()
                        . ' a ' . ($proveedor ? $proveedor->getTbproveedornombreempresa() : 'proveedor'),
                        $pagado, date('Y-m-d'), $usuario['id']);
                }
            } else {
                $this->repositorio->restarstock($id, $cantidad);
                $this->movimientos->registrar($id, self::TIPODANADO, -$cantidad, $usuario['id']);
            }
        });

        $nuevo = $sentido === 'sumar' ? $stock + $cantidad : $stock - $cantidad;
        $mensaje = $sentido === 'sumar'
            ? 'Se sumaron ' . $paquetes . ($paquetes === 1 ? ' paquete' : ' paquetes') . ' (' . $cantidad . ' unidades). Ahora hay ' . $nuevo . ' en stock.'
                . ($pagado > 0 ? ' Los ' . historicocontrolador::monto($pagado) . ' quedaron como gasto de hoy.' : '')
            : 'Se rebajaron ' . $cantidad . ($cantidad === 1 ? ' unidad dañada' : ' unidades dañadas') . '. Ahora hay ' . $nuevo . ' en stock.';

        return $this->respuesta(true, $mensaje, ['stock' => $nuevo]);
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

    public function subirfoto($id, $foto, $usuario)
    {
        $id = (int) $id;
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        $anterior = (string) $inventario->getTbinventariofoto();
        $quitar = $foto === null;

        if ($quitar && $anterior === '') {
            return $this->respuesta(false, 'El producto no tiene foto.');
        }

        $nueva = '';
        if (!$quitar) {
            $error = $this->validarfoto($foto);
            if ($error !== '') {
                return $this->respuesta(false, $error, ['errores' => ['tbinventariofoto' => $error]]);
            }
            $nueva = $this->guardarfoto($foto, $id);
        }

        try {
            $this->entransaccion(function () use ($id, $nueva, $usuario) {
                $this->repositorio->guardarfoto($id, $nueva);
                $this->historico->registrar('inventario', $id, [], ['foto' => $nueva], $usuario['id']);
            });
        } catch (Throwable $excepcion) {
            $this->eliminarfoto($nueva);
            throw $excepcion;
        }

        $this->eliminarfoto($anterior);

        $mensaje = $quitar ? 'Se quitó la foto del producto.'
            : ($anterior === '' ? 'Se agregó la foto del producto.' : 'Se cambió la foto del producto.');

        return $this->respuesta(true, $mensaje, ['tbinventariofoto' => $nueva]);
    }

    private function validarfoto($foto)
    {
        if (!is_array($foto) || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Elija la foto del producto.';
        }
        if ($foto['error'] !== UPLOAD_ERR_OK) {
            return 'No se pudo subir la foto. Verifique que pese menos de 2 MB.';
        }
        if ($foto['size'] > $this->tamanomaximo) {
            return 'La foto no puede pesar más de 2 MB.';
        }
        $imagen = @getimagesize($foto['tmp_name']);
        if (!$imagen || !isset($this->tiposfoto[$imagen['mime']])) {
            return 'La foto debe ser una imagen JPG, PNG o WEBP.';
        }

        return '';
    }

    private function guardarfoto($foto, $id)
    {
        if (!is_dir($this->carpetafotos)) {
            mkdir($this->carpetafotos, 0775, true);
        }

        $tipo = getimagesize($foto['tmp_name'])['mime'];
        $nombre = 'producto_' . $id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $this->tiposfoto[$tipo];

        if (!move_uploaded_file($foto['tmp_name'], $this->carpetafotos . $nombre)) {
            throw new RuntimeException('No se pudo guardar la foto del producto.');
        }

        return 'imagenes/productos/' . $nombre;
    }

    private function eliminarfoto($ruta)
    {
        $archivo = __DIR__ . '/../../Publico/' . $ruta;
        if ($ruta !== '' && strpos($ruta, 'imagenes/productos/producto_') === 0 && is_file($archivo)) {
            unlink($archivo);
        }
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
            'foto'        => 'Foto',
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
                    [$idproveedor, $precio] = explode('|', (string) $valor) + [1 => ''];
                    return ($nombres[$idproveedor] ?? 'Proveedor eliminado') . ': '
                        . ($precio === '' ? 'ya no se le compra' : historicocontrolador::monto($precio) . ' el paquete');
                case 'estado':
                    return historicocontrolador::estado($valor);
                case 'foto':
                    return (string) $valor === '' ? 'Sin foto' : 'Foto nueva';
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('inventario', $id, $etiquetas, $formatear, ['preciotemporada', 'otroproveedor']));
    }

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

    private function validartemporadas($preciostemporada, $datos, $errores)
    {
        $error = $this->temporadas->validar($preciostemporada);

        return $error !== '' ? ['temporadas' => $error] : [];
    }

    public function temporadas()
    {
        return $this->respuesta(true, '', $this->temporadas->paraformulario('inventario', 0));
    }

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

    private function convertirmovimientos($idinventario)
    {
        $lista = [];
        foreach (array_reverse($this->movimientos->listar($idinventario)) as $fila) {
            switch ($fila['tbinventariomovimientotipo']) {
                case 'Inicial':
                    $motivo = 'Stock inicial al registrar el producto';
                    break;
                case 'Entrada':
                    $motivo = 'Pedido N.º ' . $fila['tbpedidoid'] . ' recibido';
                    break;
                case 'Salida':
                    $motivo = 'Encargo N.º ' . $fila['tbsolicitudencargoid'] . ' terminado';
                    break;
                case self::TIPOCOMPRA:
                    $motivo = 'Compra sin pedido' . (!empty($fila['proveedor']) ? ' a ' . $fila['proveedor'] : '');
                    break;
                case self::TIPODANADO:
                    $motivo = 'Se dañaron o se marchitaron';
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

        $medidas = ['tbinventariocategoriabasemedida', 'tbinventariocategoriaenvolturamedida', 'tbinventariocategoriatarjetamedida'];
        foreach ($medidas as $campo) {
            if (isset($limpios[$campo])) {
                $limpios[$campo] = preg_replace('/\s*[xX×]\s*/u', ' x ', $limpios[$campo]);
            }
        }

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

        foreach (['tbinventariocategoriaaccesoriocolor', 'tbinventariocategoriaaccesoriomaterial'] as $campo) {
            if (($limpios[$campo] ?? null) === '') {
                $limpios[$campo] = null;
            }
        }

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

    const PALABRASCOLOR = ['rojo', 'roja', 'rojizo', 'rojiza', 'azul', 'azulado', 'azulada', 'verde', 'verdoso', 'verdosa',
        'amarillo', 'amarilla', 'naranja', 'anaranjado', 'anaranjada', 'morado', 'morada', 'violeta', 'lila', 'rosado', 'rosada',
        'rosa', 'fucsia', 'blanco', 'blanca', 'negro', 'negra', 'gris', 'grisaceo', 'cafe', 'marron', 'beige', 'crema', 'dorado',
        'dorada', 'plateado', 'plateada', 'plata', 'oro', 'celeste', 'turquesa', 'vino', 'tinto', 'borgona', 'burdeos', 'granate',
        'coral', 'salmon', 'durazno', 'melocoton', 'lavanda', 'magenta', 'purpura', 'terracota', 'ocre', 'mostaza', 'champan',
        'champagne', 'marfil', 'perla', 'hueso', 'cobre', 'cobrizo', 'bronce', 'ambar', 'aguamarina', 'cian', 'indigo', 'ciruela',
        'berenjena', 'carmesi', 'escarlata', 'cereza', 'frambuesa', 'fresa', 'oliva', 'menta', 'lima', 'esmeralda', 'jade', 'caqui',
        'kaki', 'arena', 'chocolate', 'canela', 'miel', 'mandarina', 'limon', 'uva', 'mora', 'natural', 'surtido', 'surtida',
        'transparente', 'multicolor', 'tornasol', 'tornasolado', 'nude', 'tierra', 'grafito', 'plomo', 'ceniza', 'petroleo'];
    const PALABRASMATIZ = ['claro', 'clara', 'oscuro', 'oscura', 'pastel', 'fuerte', 'suave', 'intenso', 'intensa', 'palido',
        'palida', 'brillante', 'mate', 'metalico', 'metalica', 'metalizado', 'metalizada', 'agua', 'cielo', 'rey', 'marino',
        'bebe', 'chicle', 'viejo', 'vieja', 'palo', 'de', 'y', 'con', 'neon', 'electrico', 'electrica', 'militar', 'botella',
        'musgo', 'acero', 'humo', 'vivo', 'viva', 'profundo', 'profunda', 'quemado', 'quemada', 'perlado', 'perlada', 'nacarado',
        'nacarada', 'empolvado', 'empolvada', 'tenue', 'medio', 'media', 'satinado', 'satinada'];

    public static function escolor($nombre)
    {
        $texto = mb_strtolower(trim((string) $nombre));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        $palabras = preg_split('/\s+/u', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $tienecolor = false;
        foreach ($palabras as $palabra) {
            if (in_array($palabra, self::PALABRASCOLOR, true)) {
                $tienecolor = true;
            } elseif (!in_array($palabra, self::PALABRASMATIZ, true)) {
                return false;
            }
        }

        return $tienecolor;
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
        } elseif ($igual = $this->repositorio->buscarpornombre($datos['tbinventarionombre'], $idexcluir)) {
            $this->existente = $idexcluir === 0 && (int) $igual['tbinventarioestado'] === 1
                ? ['tbinventarioid' => (int) $igual['tbinventarioid'], 'tbinventarionombre' => $igual['tbinventarionombre']]
                : null;
            $errores['tbinventarionombre'] = 'Ya existe "' . $igual['tbinventarionombre'] . '" (de ' . $igual['tbproveedornombreempresa']
                . ((int) $igual['tbinventarioestado'] === 1 ? '' : ', inactivo') . '). Si compró más, súmele el stock a ese insumo; '
                . 'si otro proveedor también lo vende, agréguelo en "Otros proveedores".';
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

        if ($idexcluir === 0) {
            $this->validarentero($errores, $datos, 'tbinventariostock', 0, 999999,
                'El stock es obligatorio.',
                'El stock debe ser un número entero entre 0 y 999999.');
        }

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
        } elseif ($error = $this->errorvencimiento($valor, 'tbinventariocategoriachocolatefechavencimiento')) {
            $errores['tbinventariocategoriachocolatefechavencimiento'] = $error;
        }

        return $errores;
    }

    const DIASVENCIMIENTO = 30;

    private function errorvencimiento($valor, $campo)
    {
        if ($valor === ($this->actuales[$campo] ?? null)) {
            return '';
        }

        $minimo = date('Y-m-d', strtotime('+' . self::DIASVENCIMIENTO . ' days'));
        if ($valor < $minimo) {
            return 'La fecha de vencimiento debe ser al menos ' . self::DIASVENCIMIENTO . ' días después de hoy (desde el '
                . date('d/m/Y', strtotime($minimo)) . ').';
        }

        return '';
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

        $valor = $datos['tbinventariocategoriasprayfechavencimiento'];
        if ($valor !== null) {
            $fecha = DateTime::createFromFormat('Y-m-d', (string) $valor);
            if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
                $errores['tbinventariocategoriasprayfechavencimiento'] = 'La fecha de vencimiento no es válida.';
            } elseif ($error = $this->errorvencimiento($valor, 'tbinventariocategoriasprayfechavencimiento')) {
                $errores['tbinventariocategoriasprayfechavencimiento'] = $error;
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
            'tbinventariofoto'            => (string) $inventario->getTbinventariofoto(),
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