<?php
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/encargohistoricorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariomovimientorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

class encargocontrolador
{
    private $repositorio;
    private $empleados;
    private $arreglos;
    private $inventario;
    private $historico;
    private $movimientos;
    private $fechas;

    public function __construct()
    {
        $this->repositorio = new encargorepositorio();
        $this->empleados = new empleadorepositorio();
        $this->arreglos = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
        $this->historico = new encargohistoricorepositorio();
        $this->movimientos = new inventariomovimientorepositorio();
        $this->fechas = new historicocontrolador();
    }

    public function arreglos()
    {
        $lista = [];
        foreach ($this->arreglos->listar('', 1) as $arreglo) {
            $lista[] = [
                'tbarreglofloralid'     => $arreglo->getTbarreglofloralid(),
                'tbarreglofloralnombre' => $arreglo->getTbarreglofloralnombre(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function empleados()
    {
        $lista = [];
        foreach ($this->empleados->listar('', 1) as $empleado) {
            $lista[] = [
                'tbempleadoid'     => $empleado->getTbempleadoid(),
                'tbempleadonombre' => $empleado->getTbempleadonombre(),
                'tbempleadorol'    => $empleado->getTbempleadorol(),
            ];
        }

        return $this->respuesta(true, '', $lista);
    }

    public function listar($estado = '', $idempleado = '')
    {
        $lista = [];
        foreach ($this->repositorio->listar((string) $estado, (string) $idempleado) as $encargo) {
            $lista[] = $this->convertirarreglo($encargo);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        $datos = $this->convertirarreglo($encargo);
        $datos['materiales'] = $this->materiales($encargo);
        $datos['historial'] = $this->historial($encargo->getTbencargoid());

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);

        // Un empleado solo puede dejarlo sin asignar o asignárselo a sí mismo
        if ($usuario['rol'] !== 'Dueña' && !in_array($datos['tbempleadoid'], ['', (string) $usuario['id']], true)) {
            return $this->respuesta(false, 'Revise los datos ingresados.',
                ['errores' => ['tbempleadoid' => 'Solo puede asignarse el encargo a usted misma.']]);
        }

        $errores = $this->validar($datos, true);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $id = $this->entransaccion(function () use ($datos, $usuario) {
            $id = $this->repositorio->insertar(new Encargo($datos));
            $this->historico->registrar('estado', $id, 'Pendiente', $usuario['id']);
            $this->fechas->registrar('encargo', $id, [], ['fechaentrega' => $datos['tbencargofechaentrega']], $usuario['id']);

            // Al validar, "sin asignar" queda como null
            if ($datos['tbempleadoid'] !== null && $datos['tbempleadoid'] !== '') {
                $this->historico->registrar('empleado', $id, (int) $datos['tbempleadoid'], $usuario['id']);
            }

            return $id;
        });

        return $this->respuesta(true, 'Encargo registrado correctamente.', ['tbencargoid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbencargoid'] ?? 0);
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if ($encargo->getTbencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden editar encargos pendientes.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, false);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $fechaanterior = $encargo->getTbencargofechaentrega();

        $encargo->setTbarreglofloralid($datos['tbarreglofloralid']);
        $encargo->setTbencargocantidad($datos['tbencargocantidad']);
        $encargo->setTbencargofechaentrega($datos['tbencargofechaentrega']);
        $encargo->setTbencargonota($datos['tbencargonota']);

        $this->entransaccion(function () use ($encargo, $id, $fechaanterior, $datos, $usuario) {
            $this->repositorio->actualizar($encargo);
            $this->fechas->registrar('encargo', $id, ['fechaentrega' => $fechaanterior],
                ['fechaentrega' => $datos['tbencargofechaentrega']], $usuario['id']);
        });

        return $this->respuesta(true, 'Encargo actualizado correctamente.');
    }

    public function asignar($datos, $usuario)
    {
        $id = (int) ($datos['tbencargoid'] ?? 0);
        $idempleado = trim((string) ($datos['tbempleadoid'] ?? ''));
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden asignar encargos pendientes o en proceso.');
        }

        $error = $this->validarempleado($idempleado);
        if ($error !== '') {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => ['tbempleadoid' => $error]]);
        }

        $empleado = $this->empleados->obtenerporid((int) $idempleado);

        // Si se elige a la misma persona no hay nada que cambiar ni que guardar en el historial
        if ((int) $encargo->getTbempleadoid() === (int) $idempleado) {
            return $this->respuesta(true, 'El encargo ya estaba asignado a ' . $empleado->getTbempleadonombre() . '.');
        }

        $this->entransaccion(function () use ($id, $idempleado, $usuario) {
            $this->repositorio->asignar($id, (int) $idempleado);
            $this->historico->registrar('empleado', $id, (int) $idempleado, $usuario['id']);
        });

        return $this->respuesta(true, 'Encargo asignado a ' . $empleado->getTbempleadonombre() . '.');
    }

    // Quien tiene la sesión abierta (dueña o empleado) se asigna el encargo
    public function asignarme($id, $usuario)
    {
        $encargo = $this->repositorio->obtenerporid((int) $id);

        if ($encargo && $usuario['rol'] !== 'Dueña' && $encargo->getTbempleadoid()
            && (int) $encargo->getTbempleadoid() !== (int) $usuario['id']) {
            return $this->respuesta(false, 'Este encargo ya está asignado a otra persona.');
        }

        return $this->asignar(['tbencargoid' => $id, 'tbempleadoid' => (string) $usuario['id']], $usuario);
    }

    // Un empleado solo puede mover sus propios encargos
    private function esdeotro(Encargo $encargo, $usuario)
    {
        return $usuario['rol'] !== 'Dueña' && (int) $encargo->getTbempleadoid() !== (int) $usuario['id'];
    }

    public function iniciar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if ($encargo->getTbencargoestado() !== 'Pendiente') {
            return $this->respuesta(false, 'Solo se pueden iniciar encargos pendientes.');
        }

        if (!$encargo->getTbempleadoid()) {
            return $this->respuesta(false, 'Asigne el encargo a alguien antes de iniciarlo.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede iniciar los encargos asignados a usted.');
        }

        $this->cambiarestado($id, 'En proceso', $usuario);

        return $this->respuesta(true, 'El encargo pasó a "En proceso".');
    }

    public function terminar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if ($this->esdeotro($encargo, $usuario)) {
            return $this->respuesta(false, 'Solo puede terminar los encargos asignados a usted.');
        }

        if ($encargo->getTbencargoestado() !== 'En proceso') {
            return $this->respuesta(false, 'Solo se pueden terminar encargos en proceso.');
        }

        $materiales = $this->materiales($encargo);
        $faltantes = [];
        foreach ($materiales as $material) {
            if (!$material['alcanza']) {
                $faltantes[] = $material['tbinventarionombre'] . ' (faltan ' . ($material['necesarias'] - $material['stock']) . ')';
            }
        }

        if ($faltantes) {
            return $this->respuesta(false, 'No alcanza el inventario para terminarlo: ' . implode(', ', $faltantes) . '.');
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

        return $this->respuesta(true, 'Encargo terminado. Se descontaron los productos del inventario.');
    }

    public function cancelar($id, $usuario)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden cancelar encargos pendientes o en proceso.');
        }

        $this->cambiarestado($id, 'Cancelado', $usuario);

        return $this->respuesta(true, 'Encargo cancelado.');
    }

    // Cambia el estado y lo deja en el historial con quién lo hizo
    private function cambiarestado($id, $estado, $usuario)
    {
        $this->entransaccion(function () use ($id, $estado, $usuario) {
            $this->repositorio->cambiarestado($id, $estado);
            $this->historico->registrar('estado', $id, $estado, $usuario['id']);
        });
    }

    // Historial del encargo en palabras, para mostrarlo en "Ver"
    private function historial($idencargo)
    {
        $lista = [];
        foreach ($this->historico->listar($idencargo) as $fila) {
            if ($fila['campo'] === 'estado') {
                $texto = $fila['valor'] === 'Pendiente' && !$lista
                    ? 'Se registró el encargo'
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

        // Cambios de fecha de entrega. La primera es la fecha con que se registró, así que no se repite.
        $formatear = function ($campo, $valor) {
            return date('d/m/Y', strtotime($valor));
        };

        foreach ($this->fechas->listar('encargo', $idencargo, ['fechaentrega' => 'Fecha de entrega'], $formatear) as $cambio) {
            if ($cambio['anterior'] === null) {
                continue;
            }

            $lista[] = [
                'fecha'    => $cambio['fecha'],
                'texto'    => 'La fecha de entrega cambió del ' . $cambio['anterior'] . ' al ' . $cambio['nuevo'],
                'hechopor' => $cambio['hechopor'],
            ];
        }

        usort($lista, function ($a, $b) {
            return strcmp($a['fecha'], $b['fecha']);
        });

        return $lista;
    }

    // Productos que necesita el encargo (unidades del arreglo x cantidad de arreglos) y si alcanza el stock
    private function materiales(Encargo $encargo)
    {
        $materiales = [];
        foreach ($this->arreglos->listardetalles($encargo->getTbarreglofloralid()) as $detalle) {
            $producto = $this->inventario->obtenerporid($detalle->getTbinventarioid());
            $necesarias = (int) $detalle->getTbarreglofloraldetallecantidad() * (int) $encargo->getTbencargocantidad();
            $stock = $producto ? (int) $producto->getTbinventariostock() : 0;

            $materiales[] = [
                'tbinventarioid'     => (int) $detalle->getTbinventarioid(),
                'tbinventarionombre' => $producto ? $producto->getTbinventarionombre() : 'Producto eliminado',
                'necesarias'         => $necesarias,
                'stock'              => $stock,
                'alcanza'            => $stock >= $necesarias,
            ];
        }

        return $materiales;
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
        return [
            'tbarreglofloralid'     => trim((string) ($datos['tbarreglofloralid'] ?? '')),
            'tbempleadoid'          => trim((string) ($datos['tbempleadoid'] ?? '')),
            'tbencargocantidad'     => trim((string) ($datos['tbencargocantidad'] ?? '')),
            'tbencargofechaentrega' => trim((string) ($datos['tbencargofechaentrega'] ?? '')),
            'tbencargonota'         => trim(preg_replace('/\s+/u', ' ', (string) ($datos['tbencargonota'] ?? ''))),
        ];
    }

    private function validar(&$datos, $esnuevo)
    {
        $errores = [];

        $arreglo = ctype_digit($datos['tbarreglofloralid']) ? $this->arreglos->obtenerporid((int) $datos['tbarreglofloralid']) : null;
        if ($datos['tbarreglofloralid'] === '') {
            $errores['tbarreglofloralid'] = 'Seleccione el arreglo.';
        } elseif (!$arreglo) {
            $errores['tbarreglofloralid'] = 'El arreglo seleccionado no existe.';
        } elseif (!$arreglo->estaActivo()) {
            $errores['tbarreglofloralid'] = 'El arreglo seleccionado está inactivo.';
        }

        if ($datos['tbencargocantidad'] === '') {
            $errores['tbencargocantidad'] = 'La cantidad es obligatoria.';
        } elseif (!ctype_digit($datos['tbencargocantidad']) || (int) $datos['tbencargocantidad'] < 1 || (int) $datos['tbencargocantidad'] > 99) {
            $errores['tbencargocantidad'] = 'La cantidad debe ser un número entero entre 1 y 99.';
        }

        $fecha = DateTime::createFromFormat('Y-m-d', $datos['tbencargofechaentrega']);
        if ($datos['tbencargofechaentrega'] === '') {
            $errores['tbencargofechaentrega'] = 'La fecha de entrega es obligatoria.';
        } elseif (!$fecha || $fecha->format('Y-m-d') !== $datos['tbencargofechaentrega']) {
            $errores['tbencargofechaentrega'] = 'La fecha de entrega no es válida.';
        } elseif ($datos['tbencargofechaentrega'] < date('Y-m-d')) {
            $errores['tbencargofechaentrega'] = 'La fecha de entrega no puede ser anterior a hoy.';
        }

        if (mb_strlen($datos['tbencargonota']) > 255) {
            $errores['tbencargonota'] = 'La nota no puede superar 255 caracteres.';
        }

        // Al crear se puede dejar sin asignar
        if ($esnuevo && $datos['tbempleadoid'] !== '') {
            $error = $this->validarempleado($datos['tbempleadoid']);
            if ($error !== '') {
                $errores['tbempleadoid'] = $error;
            }
        }

        $datos['tbempleadoid'] = $datos['tbempleadoid'] === '' ? null : (int) $datos['tbempleadoid'];

        return $errores;
    }

    private function convertirarreglo(Encargo $encargo)
    {
        $arreglo = $this->arreglos->obtenerporid($encargo->getTbarreglofloralid());
        $empleado = $encargo->getTbempleadoid() ? $this->empleados->obtenerporid($encargo->getTbempleadoid()) : null;
        $alcanza = true;

        if ($encargo->estaAbierto()) {
            foreach ($this->materiales($encargo) as $material) {
                $alcanza = $alcanza && $material['alcanza'];
            }
        }

        return [
            'tbencargoid'            => $encargo->getTbencargoid(),
            'tbarreglofloralid'      => (int) $encargo->getTbarreglofloralid(),
            'tbarreglofloralnombre'  => $arreglo ? $arreglo->getTbarreglofloralnombre() : '',
            'tbarreglofloralfoto'    => $arreglo ? $arreglo->getTbarreglofloralfoto() : '',
            'tbempleadoid'           => $empleado ? (int) $empleado->getTbempleadoid() : null,
            'tbempleadonombre'       => $empleado ? $empleado->getTbempleadonombre() : '',
            'tbempleadorol'          => $empleado ? $empleado->getTbempleadorol() : '',
            'tbencargocantidad'      => (int) $encargo->getTbencargocantidad(),
            'tbencargofechaentrega'  => $encargo->getTbencargofechaentrega(),
            'tbencargonota'          => $encargo->getTbencargonota(),
            'tbencargoestado'        => $encargo->getTbencargoestado(),
            'tbencargofecharegistro' => $encargo->getTbencargofecharegistro(),
            'alcanzastock'           => $alcanza,
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