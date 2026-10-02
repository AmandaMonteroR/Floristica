<?php
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorhistoricorepositorio.php';
require_once __DIR__ . '/../Repositorio/ubicacionrepositorio.php';

class proveedorcontrolador
{
    private $repositorio;
    private $historico;
    private $ubicaciones;

    public function __construct()
    {
        $this->repositorio = new proveedorrepositorio();
        $this->historico = new proveedorhistoricorepositorio();
        $this->ubicaciones = new ubicacionrepositorio();
    }

    public function ubicaciones()
    {
        return $this->respuesta(true, '', $this->ubicaciones->listar());
    }

    public function listar($texto = '', $estado = null)
    {
        $lista = [];
        foreach ($this->repositorio->listar($texto, $estado) as $proveedor) {
            $lista[] = $this->convertirarreglo($proveedor);
        }

        return $this->respuesta(true, '', $lista);
    }

    public function obtener($id)
    {
        $proveedor = $this->repositorio->obtenerporid((int) $id);

        if (!$proveedor) {
            return $this->respuesta(false, 'El proveedor no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($proveedor));
    }

    public function crear($datos)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $proveedor = new Proveedor($datos);

        $id = $this->entransaccion(function () use ($proveedor) {
            $id = $this->repositorio->insertar($proveedor);

            $this->historico->registrar('nombreempresa', $id, $proveedor->getTbproveedornombreempresa());
            $this->historico->registrar('nombrecontacto', $id, $proveedor->getTbproveedornombrecontacto());
            $this->historico->registrar('telefono', $id, $proveedor->getTbproveedortelefono());

            if ($proveedor->getTbproveedorcorreo() !== '') {
                $this->historico->registrar('correo', $id, $proveedor->getTbproveedorcorreo());
            }

            $this->historico->registrar('estado', $id, 1);

            return $id;
        });

        return $this->respuesta(true, 'Proveedor registrado correctamente.', ['tbproveedorid' => $id]);
    }

    public function actualizar($datos)
    {
        $id = (int) ($datos['tbproveedorid'] ?? 0);
        $proveedor = $this->repositorio->obtenerporid($id);

        if (!$proveedor) {
            return $this->respuesta(false, 'El proveedor no existe.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $valoresactuales = [
            'nombreempresa'  => $proveedor->getTbproveedornombreempresa(),
            'nombrecontacto' => $proveedor->getTbproveedornombrecontacto(),
            'telefono'       => $proveedor->getTbproveedortelefono(),
            'correo'         => $proveedor->getTbproveedorcorreo(),
        ];

        $cambios = [];
        foreach ($valoresactuales as $campo => $valoractual) {
            $valornuevo = $datos['tbproveedor' . $campo];
            if ((string) $valoractual !== $valornuevo) {
                $cambios[$campo] = $valornuevo;
            }
        }

        $proveedor->setTbproveedornombreempresa($datos['tbproveedornombreempresa']);
        $proveedor->setTbproveedornombrecontacto($datos['tbproveedornombrecontacto']);
        $proveedor->setTbproveedortelefono($datos['tbproveedortelefono']);
        $proveedor->setTbproveedorcorreo($datos['tbproveedorcorreo']);
        $proveedor->setTbproveedorprovincia($datos['tbproveedorprovincia']);
        $proveedor->setTbproveedorcanton($datos['tbproveedorcanton']);
        $proveedor->setTbproveedordistrito($datos['tbproveedordistrito']);
        $proveedor->setTbproveedordireccion($datos['tbproveedordireccion']);
        $proveedor->setTbproveedordescripcion($datos['tbproveedordescripcion']);

        $this->entransaccion(function () use ($proveedor, $cambios, $id) {
            $this->repositorio->actualizar($proveedor);

            foreach ($cambios as $campo => $valor) {
                $this->historico->registrar($campo, $id, $valor);
            }
        });

        return $this->respuesta(true, 'Proveedor actualizado correctamente.');
    }

    public function inactivar($id)
    {
        $id = (int) $id;
        $proveedor = $this->repositorio->obtenerporid($id);

        if (!$proveedor) {
            return $this->respuesta(false, 'El proveedor no existe.');
        }

        if (!$proveedor->estaActivo()) {
            return $this->respuesta(false, 'El proveedor ya está inactivo.');
        }

        $this->entransaccion(function () use ($id) {
            $this->repositorio->inactivar($id);
            $this->historico->registrar('estado', $id, 0);
        });

        return $this->respuesta(true, 'Proveedor inactivado correctamente.');
    }

    public function reactivar($id)
    {
        $id = (int) $id;
        $proveedor = $this->repositorio->obtenerporid($id);

        if (!$proveedor) {
            return $this->respuesta(false, 'El proveedor no existe.');
        }

        if ($proveedor->estaActivo()) {
            return $this->respuesta(false, 'El proveedor ya está activo.');
        }

        $this->entransaccion(function () use ($id) {
            $this->repositorio->reactivar($id);
            $this->historico->registrar('estado', $id, 1);
        });

        return $this->respuesta(true, 'Proveedor reactivado correctamente.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El proveedor no existe.');
        }

        $etiquetas = [
            'nombreempresa'  => 'Nombre de la empresa',
            'nombrecontacto' => 'Nombre del contacto',
            'telefono'       => 'Teléfono',
            'correo'         => 'Correo',
            'estado'         => 'Estado',
        ];

        $movimientos = [];
        foreach ($etiquetas as $campo => $etiqueta) {
            $anterior = null;

            foreach ($this->historico->listar($campo, $id) as $fila) {
                $valor = $campo === 'estado'
                    ? ((int) $fila['valor'] === 1 ? 'Activo' : 'Inactivo')
                    : $fila['valor'];

                $movimientos[] = [
                    'campo'    => $campo,
                    'etiqueta' => $etiqueta,
                    'anterior' => $anterior,
                    'nuevo'    => $valor,
                    'fecha'    => $fila['fecha'],
                ];

                $anterior = $valor;
            }
        }

        usort($movimientos, function ($a, $b) {
            return strcmp($b['fecha'], $a['fecha']);
        });

        return $this->respuesta(true, '', $movimientos);
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
            'tbproveedornombreempresa',
            'tbproveedornombrecontacto',
            'tbproveedortelefono',
            'tbproveedorcorreo',
            'tbproveedorprovincia',
            'tbproveedorcanton',
            'tbproveedordistrito',
            'tbproveedordireccion',
            'tbproveedordescripcion',
        ];

        $limpios = [];
        foreach ($campos as $campo) {
            $limpios[$campo] = trim((string) ($datos[$campo] ?? ''));
        }

        $digitos = preg_replace('/\D/', '', $limpios['tbproveedortelefono']);
        if (strlen($digitos) === 8) {
            $limpios['tbproveedortelefono'] = substr($digitos, 0, 4) . '-' . substr($digitos, 4);
        }

        return $limpios;
    }

    private function validar($datos, $idexcluir = 0)
    {
        $errores = [];

        if ($datos['tbproveedornombreempresa'] === '') {
            $errores['tbproveedornombreempresa'] = 'El nombre de la empresa es obligatorio.';
        } elseif (mb_strlen($datos['tbproveedornombreempresa']) > 100) {
            $errores['tbproveedornombreempresa'] = 'El nombre de la empresa no puede superar 100 caracteres.';
        } elseif ($this->repositorio->existenombreempresa($datos['tbproveedornombreempresa'], $idexcluir)) {
            $errores['tbproveedornombreempresa'] = 'Ya existe un proveedor con ese nombre de empresa.';
        }

        if ($datos['tbproveedornombrecontacto'] === '') {
            $errores['tbproveedornombrecontacto'] = 'El nombre del contacto es obligatorio.';
        } elseif (mb_strlen($datos['tbproveedornombrecontacto']) > 100) {
            $errores['tbproveedornombrecontacto'] = 'El nombre del contacto no puede superar 100 caracteres.';
        }

        if ($datos['tbproveedortelefono'] === '') {
            $errores['tbproveedortelefono'] = 'El teléfono es obligatorio.';
        } elseif (!preg_match('/^\d{4}-\d{4}$/', $datos['tbproveedortelefono'])) {
            $errores['tbproveedortelefono'] = 'El teléfono debe tener exactamente 8 números.';
        }

        if ($datos['tbproveedorcorreo'] !== '') {
            if (!filter_var($datos['tbproveedorcorreo'], FILTER_VALIDATE_EMAIL)) {
                $errores['tbproveedorcorreo'] = 'El correo no tiene un formato válido.';
            } elseif (mb_strlen($datos['tbproveedorcorreo']) > 100) {
                $errores['tbproveedorcorreo'] = 'El correo no puede superar 100 caracteres.';
            }
        }

        $tieneubicacion = $datos['tbproveedorprovincia'] !== ''
            || $datos['tbproveedorcanton'] !== ''
            || $datos['tbproveedordistrito'] !== '';

        if ($tieneubicacion) {
            if ($datos['tbproveedorprovincia'] === '') {
                $errores['tbproveedorprovincia'] = 'Seleccione la provincia.';
            } elseif (!$this->ubicaciones->existeprovincia($datos['tbproveedorprovincia'])) {
                $errores['tbproveedorprovincia'] = 'La provincia seleccionada no es válida.';
            }

            if ($datos['tbproveedorcanton'] === '') {
                $errores['tbproveedorcanton'] = 'Seleccione el cantón.';
            } elseif (!$this->ubicaciones->existecanton($datos['tbproveedorprovincia'], $datos['tbproveedorcanton'])) {
                $errores['tbproveedorcanton'] = 'El cantón no pertenece a la provincia seleccionada.';
            }

            if ($datos['tbproveedordistrito'] === '') {
                $errores['tbproveedordistrito'] = 'Seleccione el distrito.';
            } elseif (!$this->ubicaciones->existedistrito($datos['tbproveedorprovincia'], $datos['tbproveedorcanton'], $datos['tbproveedordistrito'])) {
                $errores['tbproveedordistrito'] = 'El distrito no pertenece al cantón seleccionado.';
            }
        }

        if (mb_strlen($datos['tbproveedordireccion']) > 200) {
            $errores['tbproveedordireccion'] = 'La dirección exacta no puede superar 200 caracteres.';
        }

        if (mb_strlen($datos['tbproveedordescripcion']) > 255) {
            $errores['tbproveedordescripcion'] = 'La descripción no puede superar 255 caracteres.';
        }

        return $errores;
    }

    private function convertirarreglo(Proveedor $proveedor)
    {
        return [
            'tbproveedorid'             => $proveedor->getTbproveedorid(),
            'tbproveedornombreempresa'  => $proveedor->getTbproveedornombreempresa(),
            'tbproveedornombrecontacto' => $proveedor->getTbproveedornombrecontacto(),
            'tbproveedortelefono'       => $proveedor->getTbproveedortelefono(),
            'tbproveedorcorreo'         => $proveedor->getTbproveedorcorreo(),
            'tbproveedorprovincia'      => $proveedor->getTbproveedorprovincia() ?? '',
            'tbproveedorcanton'         => $proveedor->getTbproveedorcanton() ?? '',
            'tbproveedordistrito'       => $proveedor->getTbproveedordistrito() ?? '',
            'tbproveedordireccion'      => $proveedor->getTbproveedordireccion(),
            'tbproveedordescripcion'    => $proveedor->getTbproveedordescripcion(),
            'tbproveedorestado'         => (int) $proveedor->getTbproveedorestado(),
            'tbproveedorfecharegistro'  => $proveedor->getTbproveedorfecharegistro(),
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