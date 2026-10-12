<?php
require_once __DIR__ . '/../Repositorio/encargorepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/../Repositorio/arreglofloralrepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';

class encargocontrolador
{
    private $repositorio;
    private $empleados;
    private $arreglos;
    private $inventario;

    public function __construct()
    {
        $this->repositorio = new encargorepositorio();
        $this->empleados = new empleadorepositorio();
        $this->arreglos = new arreglofloralrepositorio();
        $this->inventario = new inventariorepositorio();
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

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, true);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $id = $this->repositorio->insertar(new Encargo($datos));

        return $this->respuesta(true, 'Encargo registrado correctamente.', ['tbencargoid' => $id]);
    }

    public function actualizar($datos)
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

        $encargo->setTbarreglofloralid($datos['tbarreglofloralid']);
        $encargo->setTbencargocantidad($datos['tbencargocantidad']);
        $encargo->setTbencargofechaentrega($datos['tbencargofechaentrega']);
        $encargo->setTbencargonota($datos['tbencargonota']);
        $this->repositorio->actualizar($encargo);

        return $this->respuesta(true, 'Encargo actualizado correctamente.');
    }

    public function asignar($datos)
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
        $this->repositorio->asignar($id, (int) $idempleado);

        return $this->respuesta(true, 'Encargo asignado a ' . $empleado->getTbempleadonombre() . '.');
    }

    public function asignarme($id)
    {
        $duena = $this->empleados->obtenerduena();

        if (!$duena) {
            return $this->respuesta(false, 'No hay una dueña registrada. Regístrela en Empleados con el rol "Dueña".');
        }

        return $this->asignar(['tbencargoid' => $id, 'tbempleadoid' => $duena->getTbempleadoid()]);
    }

    public function iniciar($id)
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

        $this->repositorio->cambiarestado($id, 'En proceso');

        return $this->respuesta(true, 'El encargo pasó a "En proceso".');
    }

    public function terminar($id)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
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

        $this->entransaccion(function () use ($materiales, $id) {
            foreach ($materiales as $material) {
                if (!$this->inventario->restarstock($material['tbinventarioid'], $material['necesarias'])) {
                    throw new RuntimeException('El stock de ' . $material['tbinventarionombre'] . ' cambió y ya no alcanza. Intente de nuevo.');
                }
            }

            $this->repositorio->cambiarestado($id, 'Terminado');
        });

        return $this->respuesta(true, 'Encargo terminado. Se descontaron los productos del inventario.');
    }

    public function cancelar($id)
    {
        $id = (int) $id;
        $encargo = $this->repositorio->obtenerporid($id);

        if (!$encargo) {
            return $this->respuesta(false, 'El encargo no existe.');
        }

        if (!$encargo->estaAbierto()) {
            return $this->respuesta(false, 'Solo se pueden cancelar encargos pendientes o en proceso.');
        }

        $this->repositorio->cambiarestado($id, 'Cancelado');

        return $this->respuesta(true, 'Encargo cancelado.');
    }

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