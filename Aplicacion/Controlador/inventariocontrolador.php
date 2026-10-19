<?php
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariocategoriarepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
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
    private $categorias = ['Flor', 'Envoltura', 'Base', 'Chocolate', 'Peluche', 'Tarjeta', 'Spray'];

    // Opciones de las listas de cada categoría. La vista las usa para llenar los select
    // y el controlador para validar, así las dos siempre coinciden.
    public static function opciones()
    {
        return [
            'tiposflor'               => ['Flor', 'Follaje'],
            'variedades'              => [
                'Flor'    => ['Alstroemeria', 'Anturio', 'Áster', 'Cala', 'Clavel', 'Clavelina', 'Dalia', 'Dragón', 'Estatiz',
                    'Estrella', 'Fully', 'Gerbera', 'Girasol', 'Gladiola', 'Gypson', 'Hortensia', 'Lirio', 'Lisianthus',
                    'Margarita', 'Peonía', 'Pinocho', 'Poma', 'Popis', 'Rosa', 'Tulipán'],
                'Follaje' => ['Areca', 'Baby eucalipto', 'Cordeline', 'Dusty miller', 'Espárrago', 'Eucalipto', 'Fénix',
                    'Helecho', 'Hiedra', 'Monstera', 'Ramas', 'Ruscus'],
            ],
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

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $preciostemporada = $this->temporadas->limpiar($datos['temporadas'] ?? []);
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);
        $errores += $this->validartemporadas($preciostemporada, $datos, $errores);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbinventariopreciounidad'] = $this->calcularpreciounidad($datos);
        $inventario = new Inventario($datos);

        $id = $this->entransaccion(function () use ($inventario, $datos, $preciostemporada, $usuario) {
            $id = $this->repositorio->insertar($inventario);
            $this->temporadas->guardar('inventario', $id, $preciostemporada, $usuario['id']);
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

        $preciostemporada = $this->temporadas->limpiar($datos['temporadas'] ?? []);
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);
        $errores += $this->validartemporadas($preciostemporada, $datos, $errores);

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

        $this->entransaccion(function () use ($inventario, $datos, $preciostemporada, $id, $stockanterior, $anteriores, $usuario) {
            $this->repositorio->actualizar($inventario);
            $this->temporadas->guardar('inventario', $id, $preciostemporada, $usuario['id']);
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
            'precioventa' => 'Precio de venta',
            'preciotemporada' => 'Precio del proveedor en temporada',
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
                case 'estado':
                    return historicocontrolador::estado($valor);
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('inventario', $id, $etiquetas, $formatear, ['preciotemporada']));
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

        $this->validarlista($errores, $datos, 'tbinventariocategoriaflortipo', 'El tipo', $opciones['tiposflor']);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaflorvariedad', 'La variedad', 50, true);
        $this->validarletras($errores, $datos, 'tbinventariocategoriaflorcolor', 'El color', 30, true);

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

        $this->validarletras($errores, $datos, 'tbinventariocategoriaenvolturacolor', 'El color', 30, true);
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
        $this->validarletras($errores, $datos, 'tbinventariocategoriabasecolor', 'El color', 30, true);
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

        $this->validarletras($errores, $datos, 'tbinventariocategoriapeluchecolor', 'El color', 30, true);
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
        $this->validarletras($errores, $datos, 'tbinventariocategoriatarjetacolor', 'El color', 30, true);
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetamaterial', 'El material', $opciones['materialestarjeta']);
        $this->validarmedida($errores, $datos, 'tbinventariocategoriatarjetamedida');
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetapersonalizable',
            'Si es personalizable', array_map('strval', array_keys($opciones['personalizable'])));
        $this->validarlista($errores, $datos, 'tbinventariocategoriatarjetaelaboracion', 'La elaboración', $opciones['elaboracionestarjeta']);

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
            $this->validarletras($errores, $datos, 'tbinventariocategoriaspraycolor', 'El color de la pintura', 30, true);

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
        }

        return $datos;
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
            'tbinventarionombre'          => $inventario->getTbinventarionombre(),
            'tbinventariocategoria'       => $inventario->getTbinventariocategoria(),
            'tbinventariopreciopaquete'   => (float) $inventario->getTbinventariopreciopaquete(),
            'tbinventariounidadespaquete' => (int) $inventario->getTbinventariounidadespaquete(),
            'tbinventariopreciounidad'    => (float) $inventario->getTbinventariopreciounidad(),
            'tbinventarioprecioventa'     => (float) $inventario->getTbinventarioprecioventa(),
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
