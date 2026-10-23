<?php
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/clienterepositorio.php';
require_once __DIR__ . '/../Repositorio/zonaentregarepositorio.php';
require_once __DIR__ . '/../Repositorio/encargohistoricorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';
require_once __DIR__ . '/preciotemporadacontrolador.php';
require_once __DIR__ . '/../Repositorio/temporadarepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariocategoriarepositorio.php';

// Solicitudes de pedido de arreglo:
// - Predefinida: un arreglo del catálogo (el cliente lo eligió del catálogo de WhatsApp) + adicionales.
// - Personalizada: el cliente elige los productos. "Presupuesto": no se puede pasar del monto que dio.
//   "Libre": se eligen los productos y el sistema calcula el total para cotizarle.
//   Cada producto se cobra a su costo + el % de ganancia que se elige (100% = el doble del costo).
// - Cada solicitud guarda lo que costaron sus flores y productos, para la ganancia en Ventas.
// - Express: se cobra el precio de la zona de entrega (lo define el propietario).
class encargocontrolador
{
    private $repositorio;
    private $empleados;
    private $arreglos;
    private $inventario;
    private $clientes;
    private $zonas;
    private $historico;
    private $movimientos;
    private $generales;
    private $temporadas;
    private $carpetafotos;
    private $tiposfoto = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    private $tamanomaximo = 2097152;

    public function __construct()
    {
        $this->repositorio = new encargorepositorio();
        $this->empleados = new empleadorepositorio();
        $this->arreglos = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
        $this->clientes = new clienterepositorio();
        $this->zonas = new zonaentregarepositorio();
        $this->historico = new encargohistoricorepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->generales = new historicocontrolador();
        $this->temporadas = new preciotemporadacontrolador();
        $this->carpetafotos = __DIR__ . '/../../Publico/imagenes/solicitudes/';
    }

    // Precio que se cobra hoy. Con una temporada en uso, el de un producto sube con lo que cobra el proveedor
    // y el de un arreglo con el costo de todo lo que lleva (los dos con el mismo porcentaje de ganancia).
    private function precioactual($tipo, $id, $precionormal)
    {
        if ($tipo === 'arreglofloral') {
            $arreglo = $this->arreglos->obtenerporid((int) $id);
            return $arreglo ? $this->temporadas->arreglo($arreglo)['precio'] : (float) $precionormal;
        }

        $productos = $this->temporadas->productos();
        return $productos[(int) $id]['venta'] ?? (float) $precionormal;
    }

    // Personalizados: costo por unidad + el % de ganancia, redondeado hacia arriba a ₡10
    public static function preciopersonalizado($costo, $porcentaje)
    {
        return (float) (ceil(round($costo * (1 + $porcentaje / 100), 2) / 10) * 10);
    }

    // Cuántas flores y follajes lleva un arreglo del catálogo (las que se pueden pintar)
    private function floresdearreglo($idarreglo)
    {
        $flores = 0;
        foreach ($this->arreglos->listardetalles($idarreglo) as $detalle) {
            $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
            if ($producto && $producto->getTbinventariocategoria() === 'Flor') {
                $flores += (int) $detalle->getTbarreglofloralinsumocantidad();
            }
        }

        return $flores;
    }

    // Pinturas florales activas, con lo que se cobra por cada flor pintada
    private function pinturas()
    {
        $atributos = new inventariocategoriarepositorio();
        $lista = [];
        foreach ($this->inventario->listar('', 'Spray', 1) as $spray) {
            $datos = $atributos->obtener('Spray', $spray->getTbinventarioid());
            if ($datos && $datos->getTbinventariocategoriaspraytipo() === 'Pintura floral') {
                $lista[] = [
                    'tbinventarioid'     => (int) $spray->getTbinventarioid(),
                    'tbinventarionombre' => $spray->getTbinventarionombre(),
                    'color'              => (string) $datos->getTbinventariocategoriaspraycolor(),
                    'cobroflor'          => (float) $datos->getTbinventariocategoriaspraycobroflor(),
                ];
            }
        }

        return $lista;
    }

    // Todo lo que se necesita para llenar el formulario, en una sola consulta
    public function opciones()
    {
        $datos = ['arreglos' => [], 'empleados' => [], 'clientes' => [], 'zonas' => [], 'productos' => [],
            'temporada' => $this->temporadas->datosvigente(), 'pinturas' => $this->pinturas(), 'porcentaje' => 100];

        $idalta = $this->temporadas->idalta();
        $normales = $this->temporadas->productos(0);
        $altos = $this->temporadas->productos($idalta);

        foreach ($this->arreglos->listar('', 1) as $arreglo) {
            $datos['arreglos'][] = [
                'tbarreglofloralid'     => (int) $arreglo->getTbarreglofloralid(),
                'tbarreglofloralnombre' => $arreglo->getTbarreglofloralnombre(),
                'tbarreglofloralprecio' => $this->precioactual('arreglofloral', $arreglo->getTbarreglofloralid(), $arreglo->getTbarreglofloralprecio()),
                'precionormal'          => (float) $arreglo->getTbarreglofloralprecio(),
                'precioalto'            => $this->temporadas->arreglo($arreglo, $idalta)['precio'],
                'tbarreglofloralfoto'   => $arreglo->getTbarreglofloralfoto(),
                'flores'                => $this->floresdearreglo($arreglo->getTbarreglofloralid()),
            ];
        }

        foreach ($this->empleados->listar('', 1) as $empleado) {
            $datos['empleados'][] = [
                'tbempleadoid'     => (int) $empleado->getTbempleadoid(),
                'tbempleadonombre' => $empleado->getTbempleadonombre(),
                'tbempleadorol'    => $empleado->getTbempleadorol(),
            ];
        }

        foreach ($this->clientes->listar('', 1) as $cliente) {
            $datos['clientes'][] = [
                'tbclienteid'        => (int) $cliente->getTbclienteid(),
                'tbclientenombre'    => $cliente->getTbclientenombre(),
                'tbclientetelefono'  => $cliente->getTbclientetelefono(),
                'tbclientedireccion' => (string) $cliente->getTbclientedireccion(),
            ];
        }

        foreach ($this->zonas->listar('', 1) as $zona) {
            $datos['zonas'][] = [
                'tbzonaentregaid'     => (int) $zona->getTbzonaentregaid(),
                'tbzonaentreganombre' => $zona->getTbzonaentreganombre(),
                'tbzonaentregaprecio' => (float) $zona->getTbzonaentregaprecio(),
            ];
        }

        foreach ($this->inventario->listar('', '', 1) as $producto) {
            // El spray no se vende suelto: la pintura se cobra por flor pintada
            if ($producto->getTbinventariocategoria() === 'Spray') {
                continue;
            }
            $datos['productos'][] = [
                'tbinventarioid'          => (int) $producto->getTbinventarioid(),
                'tbinventarionombre'      => $producto->getTbinventarionombre(),
                'tbinventariocategoria'   => $producto->getTbinventariocategoria(),
                'tbinventarioprecioventa' => $this->precioactual('inventario', $producto->getTbinventarioid(), $producto->getTbinventarioprecioventa()),
                'tbinventariostock'       => (int) $producto->getTbinventariostock(),
                // Costo por unidad y venta, normal y en temporada alta
                'costonormal'             => $normales[(int) $producto->getTbinventarioid()]['costo'] ?? 0,
                'costoalto'               => $altos[(int) $producto->getTbinventarioid()]['costo'] ?? 0,
                'ventanormal'             => $normales[(int) $producto->getTbinventarioid()]['venta'] ?? 0,
                'ventaalta'               => $altos[(int) $producto->getTbinventarioid()]['venta'] ?? 0,
            ];
        }

        return $this->respuesta(true, '', $datos);
    }

    public function listar($estado = '', $idempleado = '', $tipo = '')
    {
        $nombres = $this->nombres();

        $lista = [];
        foreach ($this->repositorio->listar((string) $estado, (string) $idempleado, (string) $tipo) as $encargo) {
            $lista[] = $this->convertirarreglo($encargo, $nombres);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        $datos = $this->convertirarreglo($encargo, $this->nombres());
        $datos['insumos'] = $this->convertirinsumos($encargo);
        $datos['materiales'] = $this->materiales($encargo);
        $datos['historial'] = $this->historial($encargo->getTbsolicitudencargoid());

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);

        // Un empleado solo puede dejarla sin asignar o asignársela a sí mismo
        if ($usuario['rol'] !== 'Propietario' && !in_array($datos['tbempleadoid'], ['', (string) $usuario['id']], true)) {
            return $this->respuesta(false, 'Revise los datos ingresados.',
                ['errores' => ['tbempleadoid' => 'Solo puede asignarse la solicitud a usted mismo.']]);
        }

        $errores = $this->validar($datos, null);
        $vigente = $this->temporadas->vigente();
        $datos['tbtemporadaid'] = $vigente ? (int) $vigente->getTbtemporadaid() : null;

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $encargo = new Encargo($datos);

        $id = $this->entransaccion(function () use ($encargo, $datos, $usuario) {
            $id = $this->repositorio->insertar($encargo);
            $this->repositorio->guardarinsumos($id, $datos['lineas']);
            $this->historico->registrar('estado', $id, 'Pendiente', $usuario['id']);
            $this->generales->registrar('solicitudencargo', $id, [], $this->datoshistorico($encargo), $usuario['id']);

            if ($encargo->getTbempleadoid() !== null) {
                $this->historico->registrar('empleado', $id, (int) $encargo->getTbempleadoid(), $usuario['id']);
            }

            return $id;
        });

        return $this->respuesta(true, 'Solicitud de pedido de arreglo N.º ' . $id . ' registrada. Total: '
            . historicocontrolador::monto($encargo->getTbsolicitudencargototal()) . '.',
            ['tbsolicitudencargoid' => $id, 'total' => (float) $encargo->getTbsolicitudencargototal()]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbsolicitudencargoid'] ?? 0);
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden editar solicitudes pendientes.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $encargo);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($encargo);

        // La asignación no se cambia aquí, sino con "Asignar"
        $datos['tbempleadoid'] = $encargo->getTbempleadoid();
        $datos['tbsolicitudencargoid'] = $id;
        $datos['tbsolicitudencargoestado'] = $encargo->getTbsolicitudencargoestado();
        $datos['tbsolicitudencargofecharegistro'] = $encargo->getTbsolicitudencargofecharegistro();
        $datos['tbtemporadaid'] = $encargo->getTbtemporadaid();
        $nuevo = new Encargo($datos);

        $this->entransaccion(function () use ($nuevo, $datos, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($nuevo);
            $this->repositorio->guardarinsumos($id, $datos['lineas']);
            $this->generales->registrar('solicitudencargo', $id, $anteriores, $this->datoshistorico($nuevo), $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud de pedido de arreglo N.º ' . $id . ' actualizada. Total: '
            . historicocontrolador::monto($nuevo->getTbsolicitudencargototal()) . '.');
    }

    public function asignar($datos, $usuario)
    {
        $id = (int) ($datos['tbsolicitudencargoid'] ?? 0);
        $idempleado = trim((string) ($datos['tbempleadoid'] ?? ''));
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden asignar solicitudes pendientes o en proceso.');
        }

        $error = $this->validarempleado($idempleado);
        if ($error !== '') {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => ['tbempleadoid' => $error]]);
        }

        $empleado = $this->empleados->obtenerporid((int) $idempleado);

        // Si se elige a la misma persona no hay nada que cambiar ni que guardar en el historial
        if ((int) $encargo->getTbempleadoid() === (int) $idempleado) {
            return $this->respuesta(true, 'La solicitud ya estaba asignada a ' . $empleado->getTbempleadonombre() . '.');
        }

        $this->entransaccion(function () use ($id, $idempleado, $usuario) {
            $this->repositorio->asignar($id, (int) $idempleado);
            $this->historico->registrar('empleado', $id, (int) $idempleado, $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud asignada a ' . $empleado->getTbempleadonombre() . '.');
    }

    // Quien tiene la sesión abierta (propietario o empleado) se asigna la solicitud
    public function asignarme($id, $usuario)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if ($encargo && $usuario['rol'] !== 'Propietario' && $encargo->getTbempleadoid()
            && (int) $encargo->getTbempleadoid() !== (int) $usuario['id']) {
            return $this->respuesta(false, 'Esta solicitud ya está asignada a otra persona.');
        }

        return $this->asignar(['tbsolicitudencargoid' => $id, 'tbempleadoid' => (string) $usuario['id']], $usuario);
    }

    // Un empleado solo puede mover sus propias solicitudes
    private function esdeotro(Encargo $encargo, $usuario)
    {
        return $usuario['rol'] !== 'Propietario' && (int) $encargo->getTbempleadoid() !== (int) $usuario['id'];
    }

    public function iniciar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden iniciar solicitudes pendientes.');
        }

        if (!$encargo->getTbempleadoid()) {
            return $this->respuesta(false, 'Asigne la solicitud a alguien antes de iniciarla.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede iniciar las solicitudes asignadas a usted.');
        }

        $this->cambiarestado($id, 'En proceso', $usuario);

        return $this->respuesta(true, 'La solicitud pasó a "En proceso".');
    }

    public function terminar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede terminar las solicitudes asignadas a usted.');
        }

        if ($encargo->getTbsolicitudencargoestado() !== 'En proceso') {
            return $this->respuesta(false, 'Solo se pueden terminar solicitudes en proceso.');
        }

        $materiales = $this->materiales($encargo);
        $faltantes = [];
        foreach ($materiales as $material) {
            if (!$material['alcanza']) {
                $faltantes[] = $material['tbinventarionombre'] . ' (faltan ' . ($material['necesarias'] - $material['stock']) . ')';
            }
        }

        if ($faltantes) {
            return $this->respuesta(false, 'No alcanza el inventario para terminarla: ' . implode(', ', $faltantes) . '.');
        }

        $this->entransaccion(function () use ($materiales, $id, $usuario) {
            foreach ($materiales as $material) {
                if (!$this->inventario->restarstock($material['tbinventarioid'], $material['necesarias'])) {
                    throw new RuntimeException('El stock de ' . $material['tbinventarionombre'] . ' cambió y ya no alcanza. Intente de nuevo.');
                }

                $this->movimientos->registrar($material['tbinventarioid'], 'Salida', -$material['necesarias'],
                    $usuario['id'], null, $id);
            }

            $this->repositorio->cambiarestado($id, 'Terminado');
            $this->historico->registrar('estado', $id, 'Terminado', $usuario['id']);
        });

        return $this->respuesta(true, 'Solicitud terminada. Se descontaron los productos del inventario.');
    }

    public function cancelar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden cancelar solicitudes pendientes o en proceso.');
        }

        $this->cambiarestado($id, 'Cancelado', $usuario);

        return $this->respuesta(true, 'Solicitud cancelada.');
    }

    // Foto de cómo quedó el arreglo personalizado. Se puede agregar o cambiar en cualquier momento
    // (menos si se canceló). La sube el propietario o quien tiene asignada la solicitud.
    public function subirfoto($id, $foto, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'La solicitud de pedido de arreglo no existe.');
        }

        if (!$encargo->esPersonalizado()) {
            return $this->respuesta(false, 'Solo los arreglos personalizados llevan foto; los del catálogo ya tienen la suya.');
        }

        if ($encargo->getTbsolicitudencargoestado() === 'Cancelado') {
            return $this->respuesta(false, 'No se le puede agregar foto a una solicitud cancelada.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede agregar la foto de las solicitudes asignadas a usted.');
        }

        $error = $this->validarfoto($foto);
        if ($error !== '') {
            return $this->respuesta(false, $error, ['errores' => ['tbsolicitudencargofoto' => $error]]);
        }

        $anterior = (string) $encargo->getTbsolicitudencargofoto();
        $nueva = $this->guardarfoto($foto, $id);

        try {
            $this->entransaccion(function () use ($id, $nueva, $usuario) {
                $this->repositorio->guardarfoto($id, $nueva);
                $this->generales->registrar('solicitudencargo', $id, [], ['foto' => $nueva], $usuario['id']);
            });
        } catch (Throwable $excepcion) {
            $this->eliminarfoto($nueva);
            throw $excepcion;
        }

        $this->eliminarfoto($anterior);

        return $this->respuesta(true, $anterior === '' ? 'Se agregó la foto del arreglo.' : 'Se cambió la foto del arreglo.',
            ['tbsolicitudencargofoto' => $nueva]);
    }

    private function validarfoto($foto)
    {
        if (!is_array($foto) || ($foto['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 'Elija la foto del arreglo.';
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
        $nombre = 'solicitud_' . $id . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $this->tiposfoto[$tipo];

        if (!move_uploaded_file($foto['tmp_name'], $this->carpetafotos . $nombre)) {
            throw new RuntimeException('No se pudo guardar la foto del arreglo.');
        }

        return 'imagenes/solicitudes/' . $nombre;
    }

    // Solo borra fotos de la carpeta de solicitudes
    private function eliminarfoto($ruta)
    {
        $archivo = __DIR__ . '/../../Publico/' . $ruta;
        if ($ruta !== '' && strpos($ruta, 'imagenes/solicitudes/') === 0 && is_file($archivo)) {
            unlink($archivo);
        }
    }

    // Cambia el estado y lo deja en el historial con quién lo hizo
    private function cambiarestado($id, $estado, $usuario)
    {
        $this->entransaccion(function () use ($id, $estado, $usuario) {
            $this->repositorio->cambiarestado($id, $estado);
            $this->historico->registrar('estado', $id, $estado, $usuario['id']);
        });
    }

    // Datos de la solicitud que llevan histórico general (tbsolicitudencargo<campo>historico)
    private function datoshistorico(Encargo $encargo)
    {
        return [
            'fechaentrega' => $encargo->getTbsolicitudencargofechaentrega(),
            'total'        => number_format((float) $encargo->getTbsolicitudencargototal(), 2, '.', ''),
        ];
    }

    // Historial de la solicitud en palabras, para mostrarlo en "Ver"
    private function historial($idencargo)
    {
        $lista = [];
        foreach ($this->historico->listar($idencargo) as $fila) {
            if ($fila['campo'] === 'estado') {
                $texto = $fila['valor'] === 'Pendiente' && !$lista
                    ? 'Se registró la solicitud'
                    : 'Pasó a "' . $fila['valor'] . '"';
            } else {
                $texto = 'Se asignó a ' . ($fila['nombreasignado'] ?? 'un empleado eliminado');
            }

            $lista[] = [
                'fecha'    => $fila['fecha'],
                'texto'    => $texto,
                'hechopor' => $fila['hechopor'] ?? '',
            ];
        }

        // Cambios de fecha de entrega y de total. El primer valor es el del registro, así que no se repite.
        $formatear = function ($campo, $valor) {
            return $campo === 'total' ? historicocontrolador::monto($valor) : date('d/m/Y', strtotime($valor));
        };
        $etiquetas = ['fechaentrega' => 'La fecha de entrega', 'total' => 'El total'];

        foreach ($this->generales->listar('solicitudencargo', $idencargo, $etiquetas, $formatear) as $cambio) {
            if ($cambio['anterior'] === null) {
                continue;
            }

            $lista[] = [
                'fecha'    => $cambio['fecha'],
                'texto'    => $cambio['etiqueta'] . ' cambió de ' . $cambio['anterior'] . ' a ' . $cambio['nuevo'],
                'hechopor' => $cambio['hechopor'],
            ];
        }

        // Foto del arreglo personalizado
        $sinformato = function ($campo, $valor) {
            return $valor;
        };
        foreach ($this->generales->listar('solicitudencargo', $idencargo, ['foto' => 'La foto'], $sinformato) as $cambio) {
            $lista[] = [
                'fecha'    => $cambio['fecha'],
                'texto'    => $cambio['anterior'] === null ? 'Se agregó la foto del arreglo' : 'Se cambió la foto del arreglo',
                'hechopor' => $cambio['hechopor'],
            ];
        }

        usort($lista, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        return $lista;
    }

    // Productos que hay que sacar del inventario para prepararla, y si alcanza el stock.
    // Predefinida: los del arreglo × cantidad, más los adicionales. Personalizada: los que eligió el cliente.
    private function materiales(Encargo $encargo)
    {
        $necesarias = [];

        if (!$encargo->esPersonalizado() && $encargo->getTbarreglofloralid()) {
            foreach ($this->arreglos->listardetalles($encargo->getTbarreglofloralid()) as $detalle) {
                $idproducto = (int) $detalle->getTbinventarioid();
                $necesarias[$idproducto] = ($necesarias[$idproducto] ?? 0)
                    + (int) $detalle->getTbarreglofloralinsumocantidad() * (int) $encargo->getTbsolicitudencargocantidad();
            }
        }

        foreach ($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()) as $insumo) {
            $idproducto = (int) $insumo->getTbinventarioid();
            $necesarias[$idproducto] = ($necesarias[$idproducto] ?? 0) + (int) $insumo->getTbsolicitudencargoinsumocantidad();
        }

        $materiales = [];
        foreach ($necesarias as $idproducto => $cantidad) {
            $producto = $this->inventario->obtenerporid($idproducto);
            $stock = $producto ? (int) $producto->getTbinventariostock() : 0;

            $materiales[] = [
                'tbinventarioid'     => $idproducto,
                'tbinventarionombre' => $producto ? $producto->getTbinventarionombre() : 'Producto eliminado',
                'necesarias'         => $cantidad,
                'stock'              => $stock,
                'alcanza'            => $stock >= $cantidad,
            ];
        }

        return $materiales;
    }

    private function convertirinsumos(Encargo $encargo)
    {
        $lista = [];
        foreach ($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()) as $insumo) {
            $producto = $this->inventario->obtenerporid($insumo->getTbinventarioid());
            $cantidad = (int) $insumo->getTbsolicitudencargoinsumocantidad();
            $precio = (float) $insumo->getTbsolicitudencargoinsumopreciounitario();

            $lista[] = [
                'tbinventarioid'        => (int) $insumo->getTbinventarioid(),
                'tbinventarionombre'    => $producto ? $producto->getTbinventarionombre() : 'Producto eliminado',
                'tbinventariocategoria' => $producto ? $producto->getTbinventariocategoria() : '',
                'cantidad'              => $cantidad,
                'preciounitario'        => $precio,
                'subtotal'              => round($cantidad * $precio, 2),
            ];
        }

        return $lista;
    }

    private function validarempleado($idempleado)
    {
        if ($idempleado === '') {
            return 'Seleccione a quién se le asigna.';
        }

        $empleado = ctype_digit($idempleado) ? $this->empleados->obtenerporid((int) $idempleado) : null;

        if (!$empleado) {
            return 'El empleado seleccionado no existe.';
        }

        if (!$empleado->estaActivo()) {
            return 'El empleado seleccionado está inactivo.';
        }

        return '';
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
        $texto = function ($campo) use ($datos) {
            return trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        };

        $limpios = [
            'tbclienteid'                        => $texto('tbclienteid'),
            'tbempleadoid'                       => $texto('tbempleadoid'),
            'tbsolicitudencargotipo'             => $texto('tbsolicitudencargotipo'),
            'tbarreglofloralid'                  => $texto('tbarreglofloralid'),
            'tbsolicitudencargocantidad'         => $texto('tbsolicitudencargocantidad'),
            'tbsolicitudencargomodalidad'        => $texto('tbsolicitudencargomodalidad'),
            'tbsolicitudencargopresupuesto'      => str_replace(',', '.', $texto('tbsolicitudencargopresupuesto')),
            'tbsolicitudencargoexpress'          => ($datos['tbsolicitudencargoexpress'] ?? '') === '1' ? 1 : 0,
            'tbzonaentregaid'                    => $texto('tbzonaentregaid'),
            'tbsolicitudencargodireccionentrega' => $texto('tbsolicitudencargodireccionentrega'),
            'tbsolicitudencargofechaentrega'     => $texto('tbsolicitudencargofechaentrega'),
            'tbsolicitudencargonota'             => $texto('tbsolicitudencargonota'),
            'tbsolicitudencargosprayid'          => $texto('tbsolicitudencargosprayid'),
            'tbsolicitudencargoflorespintadas'   => $texto('tbsolicitudencargoflorespintadas'),
            'tbsolicitudencargoporcentaje'       => $texto('tbsolicitudencargoporcentaje'),
            'insumos'                            => [],
        ];

        // Productos: insumos[0][tbinventarioid], insumos[0][cantidad]... Las filas vacías se ignoran.
        foreach ((array) ($datos['insumos'] ?? []) as $fila) {
            $producto = trim((string) ($fila['tbinventarioid'] ?? ''));
            $cantidad = trim((string) ($fila['cantidad'] ?? ''));

            if ($producto !== '' || $cantidad !== '') {
                $limpios['insumos'][] = ['tbinventarioid' => $producto, 'cantidad' => $cantidad];
            }
        }

        return $limpios;
    }

    // Valida y, si todo está bien, completa los precios y totales en $datos.
    // $actual es la solicitud que se está editando (null al crear): lo que ya tenía
    // se acepta aunque después se haya inactivado y conserva el precio con que se cotizó.
    private function validar(&$datos, $actual)
    {
        $errores = [];
        $esnuevo = $actual === null;

        // Cliente
        $cliente = ctype_digit($datos['tbclienteid']) ? $this->clientes->obtenerporid((int) $datos['tbclienteid']) : null;
        $mismocliente = !$esnuevo && (int) $actual->getTbclienteid() === (int) $datos['tbclienteid'];
        if ($datos['tbclienteid'] === '') {
            $errores['tbclienteid'] = 'Seleccione el cliente.';
        } elseif (!$cliente) {
            $errores['tbclienteid'] = 'El cliente seleccionado no existe.';
        } elseif (!$cliente->estaActivo() && !$mismocliente) {
            $errores['tbclienteid'] = 'El cliente seleccionado está inactivo.';
        }

        // Tipo
        $tipo = $datos['tbsolicitudencargotipo'];
        if ($tipo === '') {
            $errores['tbsolicitudencargotipo'] = 'Elija si es un arreglo predefinido o personalizado.';
        } elseif (!in_array($tipo, ['Predefinido', 'Personalizado'], true)) {
            $errores['tbsolicitudencargotipo'] = 'El tipo de solicitud no es válido.';
        }

        // % de ganancia de los personalizados: de 0 a 500 (100 = el precio es el doble del costo)
        $porcentaje = $datos['tbsolicitudencargoporcentaje'];
        $porcentajevalido = ctype_digit($porcentaje) && (int) $porcentaje <= 500;
        if ($tipo === 'Personalizado') {
            if ($porcentaje === '') {
                $errores['tbsolicitudencargoporcentaje'] = 'Escriba el % de ganancia.';
            } elseif (!$porcentajevalido) {
                $errores['tbsolicitudencargoporcentaje'] = 'El % de ganancia debe ser un número entero entre 0 y 500.';
            }
        }
        $porcentaje = $porcentajevalido ? (int) $porcentaje : 100;
        // Al editar, si no cambió el %, cada producto conserva el precio con que se cotizó
        $mismoporcentaje = !$esnuevo && $actual->esPersonalizado()
            && (float) $actual->getTbsolicitudencargoporcentaje() === (float) $porcentaje;
        $costos = $this->temporadas->productos();
        $costo = 0;

        // Productos (adicionales o los del arreglo personalizado), con el precio de cada uno
        $preciosanteriores = [];
        if (!$esnuevo) {
            foreach ($this->repositorio->listarinsumos($actual->getTbsolicitudencargoid()) as $insumo) {
                $preciosanteriores[(int) $insumo->getTbinventarioid()] = (float) $insumo->getTbsolicitudencargoinsumopreciounitario();
            }
        }

        $lineas = [];
        $cantidades = [];
        $erroresproductos = [];
        $flores = 0;
        foreach ($datos['insumos'] as $numero => $fila) {
            $etiqueta = 'Producto ' . ($numero + 1) . ': ';
            $producto = ctype_digit($fila['tbinventarioid']) ? $this->inventario->obtenerporid((int) $fila['tbinventarioid']) : null;

            if ($fila['tbinventarioid'] === '') {
                $erroresproductos[] = $etiqueta . 'elija el producto.';
                continue;
            }
            if (!$producto) {
                $erroresproductos[] = $etiqueta . 'el producto no existe.';
                continue;
            }
            if (!$producto->estaActivo() && !isset($preciosanteriores[(int) $producto->getTbinventarioid()])) {
                $erroresproductos[] = $etiqueta . $producto->getTbinventarionombre() . ' está inactivo.';
                continue;
            }
            if (!ctype_digit($fila['cantidad']) || (int) $fila['cantidad'] < 1 || (int) $fila['cantidad'] > 999) {
                $erroresproductos[] = $etiqueta . 'la cantidad de ' . $producto->getTbinventarionombre() . ' debe ser un número entero entre 1 y 999.';
                continue;
            }

            // Si el mismo producto se eligió dos veces, se suman las cantidades
            if ($producto->getTbinventariocategoria() === 'Spray') {
                $erroresproductos[] = $etiqueta . 'el spray no se agrega como producto; use "Flores pintadas".';
                continue;
            }

            $idproducto = (int) $producto->getTbinventarioid();
            $cantidades[$idproducto] = ($cantidades[$idproducto] ?? 0) + (int) $fila['cantidad'];
            if ($producto->getTbinventariocategoria() === 'Flor') {
                $flores += (int) $fila['cantidad'];
            }
            $costounidad = $costos[$idproducto]['costo'] ?? (float) $producto->getTbinventariopreciounidad();
            $costo += (int) $fila['cantidad'] * $costounidad;

            if ($tipo === 'Personalizado') {
                // Personalizado: costo + % de ganancia
                $lineas[$idproducto] = $mismoporcentaje && isset($preciosanteriores[$idproducto])
                    ? $preciosanteriores[$idproducto] : self::preciopersonalizado($costounidad, $porcentaje);
            } else {
                // Adicional de un predefinido: su precio de venta
                $lineas[$idproducto] = $preciosanteriores[$idproducto]
                    ?? $this->precioactual('inventario', $idproducto, $producto->getTbinventarioprecioventa());
            }
        }

        $subtotalproductos = 0;
        $insumos = [];
        foreach ($lineas as $idproducto => $precio) {
            $insumos[] = new Solicitudencargoinsumo([
                'tbinventarioid'                         => $idproducto,
                'tbsolicitudencargoinsumocantidad'       => $cantidades[$idproducto],
                'tbsolicitudencargoinsumopreciounitario' => $precio,
            ]);
            $subtotalproductos += $cantidades[$idproducto] * $precio;
        }

        if ($erroresproductos) {
            $errores['insumos'] = implode(' ', $erroresproductos);
        }

        $subtotal = $subtotalproductos;

        if ($tipo === 'Predefinido') {
            $arreglo = ctype_digit($datos['tbarreglofloralid']) ? $this->arreglos->obtenerporid((int) $datos['tbarreglofloralid']) : null;
            $mismoarreglo = !$esnuevo && !$actual->esPersonalizado() && (int) $actual->getTbarreglofloralid() === (int) $datos['tbarreglofloralid'];

            if ($datos['tbarreglofloralid'] === '') {
                $errores['tbarreglofloralid'] = 'Seleccione el arreglo del catálogo.';
            } elseif (!$arreglo) {
                $errores['tbarreglofloralid'] = 'El arreglo seleccionado no existe.';
            } elseif (!$arreglo->estaActivo() && !$mismoarreglo) {
                $errores['tbarreglofloralid'] = 'El arreglo seleccionado está inactivo.';
            }

            if ($datos['tbsolicitudencargocantidad'] === '') {
                $errores['tbsolicitudencargocantidad'] = 'La cantidad es obligatoria.';
            } elseif (!ctype_digit($datos['tbsolicitudencargocantidad']) || (int) $datos['tbsolicitudencargocantidad'] < 1
                || (int) $datos['tbsolicitudencargocantidad'] > 99) {
                $errores['tbsolicitudencargocantidad'] = 'La cantidad debe ser un número entero entre 1 y 99.';
            }

            if ($arreglo) {
                $flores += $this->floresdearreglo($arreglo->getTbarreglofloralid()) * max(1, (int) $datos['tbsolicitudencargocantidad']);
                $costo += $this->temporadas->arreglo($arreglo)['costo'] * max(1, (int) $datos['tbsolicitudencargocantidad']);
            }

            $precioarreglo = $arreglo ? ($mismoarreglo ? (float) $actual->getTbsolicitudencargoprecioarreglo()
                : $this->precioactual('arreglofloral', $arreglo->getTbarreglofloralid(), $arreglo->getTbarreglofloralprecio())) : 0;
            $subtotal += $precioarreglo * (int) $datos['tbsolicitudencargocantidad'];

            $datos['tbsolicitudencargoprecioarreglo'] = $precioarreglo;
            $datos['tbsolicitudencargomodalidad'] = null;
            $datos['tbsolicitudencargopresupuesto'] = null;
            $datos['tbsolicitudencargoporcentaje'] = null;
        } elseif ($tipo === 'Personalizado') {
            $modalidad = $datos['tbsolicitudencargomodalidad'];

            if ($modalidad === '') {
                $errores['tbsolicitudencargomodalidad'] = 'Elija si el cliente dio un presupuesto o si es libre.';
            } elseif (!in_array($modalidad, ['Presupuesto', 'Libre'], true)) {
                $errores['tbsolicitudencargomodalidad'] = 'La modalidad no es válida.';
            }

            if (!$insumos && !isset($errores['insumos'])) {
                $errores['insumos'] = 'Agregue al menos un producto al arreglo personalizado.';
            }

            if ($modalidad === 'Presupuesto') {
                $presupuesto = $datos['tbsolicitudencargopresupuesto'];

                if ($presupuesto === '') {
                    $errores['tbsolicitudencargopresupuesto'] = 'Escriba el presupuesto que dio el cliente.';
                } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $presupuesto) || (float) $presupuesto <= 0) {
                    $errores['tbsolicitudencargopresupuesto'] = 'El presupuesto debe ser un monto mayor que 0, con máximo 2 decimales.';
                } elseif (!isset($errores['insumos']) && $subtotalproductos > (float) $presupuesto) {
                    $errores['insumos'] = 'Los productos suman ' . historicocontrolador::monto($subtotalproductos)
                        . ' y se pasan del presupuesto (' . historicocontrolador::monto($presupuesto) . ') por '
                        . historicocontrolador::monto($subtotalproductos - (float) $presupuesto) . '. Quite algún producto o baje la cantidad.';
                }
            } else {
                $datos['tbsolicitudencargopresupuesto'] = null;
            }

            $datos['tbarreglofloralid'] = null;
            $datos['tbsolicitudencargocantidad'] = 1;
            $datos['tbsolicitudencargoprecioarreglo'] = null;
            $datos['tbsolicitudencargoporcentaje'] = $porcentaje;
        }

        // Servicio express
        $costoexpress = 0;
        if ($datos['tbsolicitudencargoexpress'] === 1) {
            $zona = ctype_digit($datos['tbzonaentregaid']) ? $this->zonas->obtenerporid((int) $datos['tbzonaentregaid']) : null;
            $mismazona = !$esnuevo && $actual->tieneExpress() && (int) $actual->getTbzonaentregaid() === (int) $datos['tbzonaentregaid'];

            if ($datos['tbzonaentregaid'] === '') {
                $errores['tbzonaentregaid'] = 'Elija la zona de entrega.';
            } elseif (!$zona) {
                $errores['tbzonaentregaid'] = 'La zona seleccionada no existe.';
            } elseif (!$zona->estaActivo() && !$mismazona) {
                $errores['tbzonaentregaid'] = 'La zona seleccionada está inactiva.';
            } else {
                $costoexpress = $mismazona ? (float) $actual->getTbsolicitudencargocostoexpress() : (float) $zona->getTbzonaentregaprecio();
            }

            if ($datos['tbsolicitudencargodireccionentrega'] === '') {
                $errores['tbsolicitudencargodireccionentrega'] = 'Escriba la dirección de entrega.';
            } elseif (mb_strlen($datos['tbsolicitudencargodireccionentrega']) > 200) {
                $errores['tbsolicitudencargodireccionentrega'] = 'La dirección no puede superar 200 caracteres.';
            }
        } else {
            $datos['tbzonaentregaid'] = null;
            $datos['tbsolicitudencargodireccionentrega'] = '';
        }

        // Flores pintadas con spray: se cobra un monto por cada flor pintada
        $pintadas = $datos['tbsolicitudencargoflorespintadas'];
        $preciopintura = 0;
        if ($datos['tbsolicitudencargosprayid'] === '' && in_array($pintadas, ['', '0'], true)) {
            $datos['tbsolicitudencargosprayid'] = null;
            $pintadas = 0;
        } else {
            $idspray = $datos['tbsolicitudencargosprayid'];
            $spray = ctype_digit($idspray) ? $this->inventario->obtenerporid((int) $idspray) : null;
            $pintura = $spray && $spray->getTbinventariocategoria() === 'Spray'
                ? (new inventariocategoriarepositorio())->obtener('Spray', (int) $idspray) : null;
            $mismospray = !$esnuevo && (int) $actual->getTbsolicitudencargosprayid() === (int) $idspray;

            if ($idspray === '') {
                $errores['tbsolicitudencargosprayid'] = 'Elija la pintura con que se pintan las flores.';
            } elseif (!$pintura || $pintura->getTbinventariocategoriaspraytipo() !== 'Pintura floral') {
                $errores['tbsolicitudencargosprayid'] = 'Elija una pintura floral.';
            } elseif (!$spray->estaActivo() && !$mismospray) {
                $errores['tbsolicitudencargosprayid'] = 'La pintura seleccionada está inactiva.';
            } else {
                $preciopintura = $mismospray ? (float) $actual->getTbsolicitudencargopreciopintura()
                    : (float) $pintura->getTbinventariocategoriaspraycobroflor();
            }

            if (!ctype_digit((string) $pintadas) || (int) $pintadas < 1 || (int) $pintadas > 999) {
                $errores['tbsolicitudencargoflorespintadas'] = 'Escriba cuántas flores se pintan (de 1 a 999).';
            } elseif ($tipo !== '' && !isset($errores['insumos']) && !isset($errores['tbarreglofloralid']) && (int) $pintadas > $flores) {
                $errores['tbsolicitudencargoflorespintadas'] = $flores === 0
                    ? 'El arreglo no lleva flores para pintar.'
                    : 'El arreglo lleva ' . $flores . ($flores === 1 ? ' flor' : ' flores') . '; no se pueden pintar más.';
            }

            $datos['tbsolicitudencargosprayid'] = ctype_digit($idspray) ? (int) $idspray : null;
            $pintadas = (int) $pintadas;
        }
        $costopintura = round($pintadas * $preciopintura, 2);
        $datos['tbsolicitudencargoflorespintadas'] = $pintadas;
        $datos['tbsolicitudencargopreciopintura'] = $preciopintura;
        $datos['tbsolicitudencargocostopintura'] = $costopintura;

        // Con presupuesto, las flores pintadas también entran en el monto que dio el cliente
        if ($tipo === 'Personalizado' && $datos['tbsolicitudencargomodalidad'] === 'Presupuesto' && $costopintura > 0
            && !isset($errores['tbsolicitudencargopresupuesto']) && !isset($errores['insumos'])
            && !isset($errores['tbsolicitudencargoflorespintadas'])
            && $subtotalproductos + $costopintura > (float) $datos['tbsolicitudencargopresupuesto']) {
            $errores['tbsolicitudencargoflorespintadas'] = 'Los productos (' . historicocontrolador::monto($subtotalproductos)
                . ') y las flores pintadas (' . historicocontrolador::monto($costopintura) . ') se pasan del presupuesto ('
                . historicocontrolador::monto($datos['tbsolicitudencargopresupuesto']) . ') por '
                . historicocontrolador::monto($subtotalproductos + $costopintura - (float) $datos['tbsolicitudencargopresupuesto'])
                . '. Pinte menos flores o quite algún producto.';
        }

        // Fecha de entrega: al editar se acepta la que ya tenía aunque ya haya pasado
        $fecha = DateTime::createFromFormat('Y-m-d', $datos['tbsolicitudencargofechaentrega']);
        $mismafecha = !$esnuevo && $actual->getTbsolicitudencargofechaentrega() === $datos['tbsolicitudencargofechaentrega'];
        if ($datos['tbsolicitudencargofechaentrega'] === '') {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega es obligatoria.';
        } elseif (!$fecha || $fecha->format('Y-m-d') !== $datos['tbsolicitudencargofechaentrega']) {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega no es válida.';
        } elseif ($datos['tbsolicitudencargofechaentrega'] < date('Y-m-d') && !$mismafecha) {
            $errores['tbsolicitudencargofechaentrega'] = 'La fecha de entrega no puede ser anterior a hoy.';
        }

        if (mb_strlen($datos['tbsolicitudencargonota']) > 255) {
            $errores['tbsolicitudencargonota'] = 'La nota no puede superar 255 caracteres.';
        }

        // Al crear se puede dejar sin asignar
        if ($esnuevo && $datos['tbempleadoid'] !== '') {
            $error = $this->validarempleado($datos['tbempleadoid']);
            if ($error !== '') {
                $errores['tbempleadoid'] = $error;
            }
        }

        $datos['tbempleadoid'] = $datos['tbempleadoid'] === '' ? null : (int) $datos['tbempleadoid'];
        $datos['tbsolicitudencargocostoexpress'] = $costoexpress;
        $datos['tbsolicitudencargosubtotal'] = round($subtotal, 2);
        $datos['tbsolicitudencargototal'] = round($subtotal + $costopintura + $costoexpress, 2);
        $datos['tbsolicitudencargocosto'] = round($costo, 2);
        $datos['lineas'] = $insumos;

        return $errores;
    }

    // Nombres de clientes, zonas, arreglos y empleados, para no consultarlos uno por uno
    private function nombres()
    {
        $nombres = ['clientes' => [], 'zonas' => [], 'arreglos' => [], 'empleados' => [], 'temporadas' => []];

        foreach ((new temporadarepositorio())->listar() as $temporada) {
            $nombres['temporadas'][$temporada->getTbtemporadaid()] = $temporada->getTbtemporadanombre();
        }

        foreach ($this->clientes->listar() as $cliente) {
            $nombres['clientes'][$cliente->getTbclienteid()] = $cliente;
        }
        foreach ($this->zonas->listar() as $zona) {
            $nombres['zonas'][$zona->getTbzonaentregaid()] = $zona;
        }
        foreach ($this->arreglos->listar() as $arreglo) {
            $nombres['arreglos'][$arreglo->getTbarreglofloralid()] = $arreglo;
        }
        foreach ($this->empleados->listar() as $empleado) {
            $nombres['empleados'][$empleado->getTbempleadoid()] = $empleado;
        }

        return $nombres;
    }

    private function convertirarreglo(Encargo $encargo, $nombres)
    {
        $cliente = $nombres['clientes'][$encargo->getTbclienteid()] ?? null;
        $zona = $nombres['zonas'][$encargo->getTbzonaentregaid()] ?? null;
        $arreglo = $nombres['arreglos'][$encargo->getTbarreglofloralid()] ?? null;
        $empleado = $nombres['empleados'][$encargo->getTbempleadoid()] ?? null;

        $alcanza = true;
        if ($encargo->estaAbierto()) {
            foreach ($this->materiales($encargo) as $material) {
                $alcanza = $alcanza && $material['alcanza'];
            }
        }

        // Texto corto de lo que pidió el cliente
        if ($encargo->esPersonalizado()) {
            $descripcion = $encargo->getTbsolicitudencargomodalidad() === 'Presupuesto'
                ? 'Personalizado con presupuesto de ' . historicocontrolador::monto($encargo->getTbsolicitudencargopresupuesto())
                : 'Personalizado (libre)';
        } else {
            $descripcion = (int) $encargo->getTbsolicitudencargocantidad() . ' × ' . ($arreglo ? $arreglo->getTbarreglofloralnombre() : 'Arreglo eliminado');
            $adicionales = count($this->repositorio->listarinsumos($encargo->getTbsolicitudencargoid()));
            if ($adicionales > 0) {
                $descripcion .= ' + ' . $adicionales . ($adicionales === 1 ? ' adicional' : ' adicionales');
            }
        }

        return [
            'tbsolicitudencargoid'               => (int) $encargo->getTbsolicitudencargoid(),
            'tbclienteid'                        => (int) $encargo->getTbclienteid(),
            'tbclientenombre'                    => $cliente ? $cliente->getTbclientenombre() : 'Cliente eliminado',
            'tbclientetelefono'                  => $cliente ? $cliente->getTbclientetelefono() : '',
            'tbsolicitudencargotipo'             => $encargo->getTbsolicitudencargotipo(),
            'tbarreglofloralid'                  => $encargo->getTbarreglofloralid() !== null ? (int) $encargo->getTbarreglofloralid() : null,
            'tbarreglofloralnombre'              => $arreglo ? $arreglo->getTbarreglofloralnombre() : '',
            'tbarreglofloralfoto'                => $arreglo ? $arreglo->getTbarreglofloralfoto() : '',
            'tbsolicitudencargocantidad'         => (int) $encargo->getTbsolicitudencargocantidad(),
            'tbsolicitudencargoprecioarreglo'    => (float) $encargo->getTbsolicitudencargoprecioarreglo(),
            'tbsolicitudencargomodalidad'        => $encargo->getTbsolicitudencargomodalidad(),
            'tbsolicitudencargopresupuesto'      => $encargo->getTbsolicitudencargopresupuesto() !== null ? (float) $encargo->getTbsolicitudencargopresupuesto() : null,
            'tbsolicitudencargoexpress'          => (int) $encargo->getTbsolicitudencargoexpress(),
            'tbzonaentregaid'                    => $encargo->getTbzonaentregaid() !== null ? (int) $encargo->getTbzonaentregaid() : null,
            'tbzonaentreganombre'                => $zona ? $zona->getTbzonaentreganombre() : '',
            'tbsolicitudencargodireccionentrega' => (string) $encargo->getTbsolicitudencargodireccionentrega(),
            'tbsolicitudencargocostoexpress'     => (float) $encargo->getTbsolicitudencargocostoexpress(),
            'tbsolicitudencargosubtotal'         => (float) $encargo->getTbsolicitudencargosubtotal(),
            'tbsolicitudencargototal'            => (float) $encargo->getTbsolicitudencargototal(),
            'tbempleadoid'                       => $empleado ? (int) $empleado->getTbempleadoid() : null,
            'tbempleadonombre'                   => $empleado ? $empleado->getTbempleadonombre() : '',
            'tbempleadorol'                      => $empleado ? $empleado->getTbempleadorol() : '',
            'tbsolicitudencargofechaentrega'     => $encargo->getTbsolicitudencargofechaentrega(),
            'tbsolicitudencargonota'             => (string) $encargo->getTbsolicitudencargonota(),
            'tbsolicitudencargoestado'           => $encargo->getTbsolicitudencargoestado(),
            'tbsolicitudencargofecharegistro'    => $encargo->getTbsolicitudencargofecharegistro(),
            'tbtemporadaid'                      => $encargo->getTbtemporadaid() !== null ? (int) $encargo->getTbtemporadaid() : null,
            'tbtemporadanombre'                  => $nombres['temporadas'][$encargo->getTbtemporadaid()] ?? '',
            'tbsolicitudencargosprayid'          => $encargo->getTbsolicitudencargosprayid() !== null ? (int) $encargo->getTbsolicitudencargosprayid() : null,
            'tbsolicitudencargospraynombre'      => $encargo->getTbsolicitudencargosprayid() !== null
                ? (($spray = $this->inventario->obtenerporid($encargo->getTbsolicitudencargosprayid())) ? $spray->getTbinventarionombre() : 'Spray eliminado') : '',
            'tbsolicitudencargoflorespintadas'   => (int) $encargo->getTbsolicitudencargoflorespintadas(),
            'tbsolicitudencargopreciopintura'    => (float) $encargo->getTbsolicitudencargopreciopintura(),
            'tbsolicitudencargocostopintura'     => (float) $encargo->getTbsolicitudencargocostopintura(),
            'tbsolicitudencargoporcentaje'       => $encargo->getTbsolicitudencargoporcentaje() !== null ? (float) $encargo->getTbsolicitudencargoporcentaje() : null,
            'tbsolicitudencargocosto'            => (float) $encargo->getTbsolicitudencargocosto(),
            'tbsolicitudencargofoto'             => (string) $encargo->getTbsolicitudencargofoto(),
            'descripcion'                        => $descripcion,
            'alcanzastock'                       => $alcanza,
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
