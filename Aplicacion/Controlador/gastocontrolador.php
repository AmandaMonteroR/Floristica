<?php
require_once __DIR__ . '/../Repositorio/gastorepositorio.php';
require_once __DIR__ . '/../Repositorio/empleadorepositorio.php';
require_once __DIR__ . '/historicocontrolador.php';

// Gastos del negocio (solo el propietario): lo que se paga y no es mercadería, como el alquiler,
// la luz, el agua o los salarios. Se usan en el reporte de ganancia neta.
// Un gasto mal registrado se anula (no se borra) y queda en el historial.
class gastocontrolador
{
    const CATEGORIAS = ['Alquiler', 'Electricidad', 'Agua', 'Internet y teléfono', 'Salarios', 'Transporte',
        'Mantenimiento', 'Publicidad', 'Otros'];
    // Hasta cuántos años atrás se puede registrar un gasto
    const ANOSMAXIMOS = 2;

    private $repositorio;
    private $empleados;
    private $historico;

    public function __construct()
    {
        $this->repositorio = new gastorepositorio();
        $this->empleados = new empleadorepositorio();
        $this->historico = new historicocontrolador();
    }

    private function fechavalida($fecha)
    {
        $objeto = DateTime::createFromFormat('Y-m-d', (string) $fecha);
        return $objeto && $objeto->format('Y-m-d') === $fecha;
    }

    // Gastos de un período ($desde y $hasta AAAA-MM-DD; sin fechas, el mes actual; "todo", desde el primero)
    public function listar($desde = '', $hasta = '', $categoria = '', $estado = '')
    {
        $desde = trim((string) $desde);
        $hasta = trim((string) $hasta);
        $categoria = trim((string) $categoria);

        if ($desde === 'todo') {
            $desde = $this->repositorio->primerafecha() ?? date('Y-m-d');
            $hasta = date('Y-m-d');
        }
        if ($desde === '') {
            $desde = date('Y-m-01');
        }
        if ($hasta === '') {
            $hasta = date('Y-m-d');
        }
        if (!$this->fechavalida($desde) || !$this->fechavalida($hasta)) {
            return $this->respuesta(false, 'Las fechas no son válidas.');
        }
        if ($desde > $hasta) {
            return $this->respuesta(false, 'La fecha "desde" no puede ser posterior a la fecha "hasta".');
        }
        if ($categoria !== '' && !in_array($categoria, self::CATEGORIAS, true)) {
            $categoria = '';
        }
        $estado = $estado === '' || $estado === null ? null : (int) $estado;

        $nombres = $this->nombresempleados();
        $lista = [];
        $total = 0;
        $anulados = 0;
        foreach ($this->repositorio->listar($desde, $hasta, $categoria, $estado) as $gasto) {
            $fila = $this->convertirarreglo($gasto);
            $fila['registradopor'] = $nombres[(int) $gasto->getTbempleadoid()] ?? '';
            $lista[] = $fila;
            if ($gasto->estaActivo()) {
                $total += (float) $gasto->getTbgastomonto();
            } else {
                $anulados++;
            }
        }

        return $this->respuesta(true, '', [
            'desde'         => $desde,
            'hasta'         => $hasta,
            'gastos'        => $lista,
            'total'         => round($total, 2),
            'anulados'      => $anulados,
            'porcategoria'  => $this->repositorio->totalesporcategoria($desde, $hasta),
            'categorias'    => self::CATEGORIAS,
        ]);
    }

    public function obtener($id)
    {
        $gasto = $this->repositorio->obtenerporid((int) $id);

        if (!$gasto) {
            return $this->respuesta(false, 'El gasto no existe.');
        }

        return $this->respuesta(true, '', $this->convertirarreglo($gasto));
    }

    public function crear($datos, $usuario)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $gasto = new Gasto($datos + ['tbempleadoid' => (int) $usuario['id']]);

        $id = $this->entransaccion(function () use ($gasto, $usuario) {
            $id = $this->repositorio->insertar($gasto);
            $this->historico->registrar('gasto', $id, [], $this->datoshistorico($gasto), $usuario['id']);

            return $id;
        });

        return $this->respuesta(true, 'Gasto registrado correctamente.', ['tbgastoid' => $id]);
    }

    public function actualizar($datos, $usuario)
    {
        $id = (int) ($datos['tbgastoid'] ?? 0);
        $gasto = $this->repositorio->obtenerporid($id);

        if (!$gasto) {
            return $this->respuesta(false, 'El gasto no existe.');
        }
        if (!$gasto->estaActivo()) {
            return $this->respuesta(false, 'El gasto está anulado. Vuelva a contarlo antes de editarlo.');
        }

        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos, $id, $gasto->getTbgastofecha());

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $anteriores = $this->datoshistorico($gasto);

        $gasto->setTbgastocategoria($datos['tbgastocategoria']);
        $gasto->setTbgastodescripcion($datos['tbgastodescripcion']);
        $gasto->setTbgastomonto($datos['tbgastomonto']);
        $gasto->setTbgastofecha($datos['tbgastofecha']);

        $this->entransaccion(function () use ($gasto, $id, $anteriores, $usuario) {
            $this->repositorio->actualizar($gasto);
            $this->historico->registrar('gasto', $id, $anteriores, $this->datoshistorico($gasto), $usuario['id']);
        });

        return $this->respuesta(true, 'Gasto actualizado correctamente.');
    }

    // Anular: deja de contar en los totales y en la ganancia neta, pero no se borra
    public function inactivar($id, $usuario)
    {
        return $this->cambiarestado((int) $id, 0, $usuario);
    }

    public function reactivar($id, $usuario)
    {
        return $this->cambiarestado((int) $id, 1, $usuario);
    }

    private function cambiarestado($id, $estado, $usuario)
    {
        $gasto = $this->repositorio->obtenerporid($id);

        if (!$gasto) {
            return $this->respuesta(false, 'El gasto no existe.');
        }
        if ((int) $gasto->getTbgastoestado() === $estado) {
            return $this->respuesta(false, $estado === 1 ? 'El gasto ya cuenta.' : 'El gasto ya está anulado.');
        }
        if ($estado === 1 && $this->repositorio->existeigual($gasto->getTbgastocategoria(), $gasto->getTbgastodescripcion(),
                $gasto->getTbgastomonto(), $gasto->getTbgastofecha(), $id)) {
            return $this->respuesta(false, 'No se puede volver a contar: ya hay otro gasto igual que sí cuenta.');
        }

        $this->entransaccion(function () use ($id, $estado, $usuario) {
            $this->repositorio->cambiarestado($id, $estado);
            $this->historico->registrar('gasto', $id, ['estado' => 1 - $estado], ['estado' => $estado], $usuario['id']);
        });

        return $this->respuesta(true, $estado === 1 ? 'El gasto vuelve a contar.' : 'Gasto anulado. Ya no cuenta en los totales.');
    }

    public function historial($id)
    {
        $id = (int) $id;

        if (!$this->repositorio->obtenerporid($id)) {
            return $this->respuesta(false, 'El gasto no existe.');
        }

        $etiquetas = [
            'categoria'   => 'Categoría',
            'descripcion' => 'Descripción',
            'monto'       => 'Monto',
            'fecha'       => 'Fecha',
            'estado'      => 'Estado',
        ];

        $formatear = function ($campo, $valor) {
            switch ($campo) {
                case 'monto':
                    return historicocontrolador::monto($valor);
                case 'fecha':
                    return date('d/m/Y', strtotime($valor));
                case 'estado':
                    return (int) $valor === 1 ? 'Cuenta' : 'Anulado';
                default:
                    return (string) $valor;
            }
        };

        return $this->respuesta(true, '', $this->historico->listar('gasto', $id, $etiquetas, $formatear));
    }

    private function datoshistorico(Gasto $gasto)
    {
        return [
            'categoria'   => $gasto->getTbgastocategoria(),
            'descripcion' => $gasto->getTbgastodescripcion(),
            'monto'       => number_format((float) $gasto->getTbgastomonto(), 2, '.', ''),
            'fecha'       => $gasto->getTbgastofecha(),
            'estado'      => (int) $gasto->getTbgastoestado(),
        ];
    }

    private function nombresempleados()
    {
        $nombres = [];
        foreach ($this->empleados->listar() as $empleado) {
            $nombres[(int) $empleado->getTbempleadoid()] = $empleado->getTbempleadonombre();
        }

        return $nombres;
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
        $limpios = [];
        foreach (['tbgastocategoria', 'tbgastodescripcion', 'tbgastomonto', 'tbgastofecha'] as $campo) {
            $limpios[$campo] = trim(preg_replace('/\s+/u', ' ', (string) ($datos[$campo] ?? '')));
        }

        $limpios['tbgastomonto'] = str_replace(',', '.', $limpios['tbgastomonto']);

        return $limpios;
    }

    // $fechaanterior: al editar, la fecha que ya tenía puede quedarse aunque sea muy vieja
    private function validar($datos, $idexcluir = 0, $fechaanterior = '')
    {
        $errores = [];

        if (!in_array($datos['tbgastocategoria'], self::CATEGORIAS, true)) {
            $errores['tbgastocategoria'] = 'Elija la categoría del gasto.';
        }

        if ($datos['tbgastodescripcion'] === '') {
            $errores['tbgastodescripcion'] = 'Escriba qué se pagó.';
        } elseif (mb_strlen($datos['tbgastodescripcion']) < 3) {
            $errores['tbgastodescripcion'] = 'La descripción debe tener al menos 3 caracteres.';
        } elseif (mb_strlen($datos['tbgastodescripcion']) > 100) {
            $errores['tbgastodescripcion'] = 'La descripción no puede superar 100 caracteres.';
        } elseif (!preg_match('/\p{L}/u', $datos['tbgastodescripcion'])) {
            $errores['tbgastodescripcion'] = 'La descripción debe contener al menos una letra.';
        } elseif (!preg_match("/^[\p{L}\d\s.,'()#%\/-]+$/u", $datos['tbgastodescripcion'])) {
            $errores['tbgastodescripcion'] = 'La descripción solo puede contener letras, números y los signos . , - \' ( ) # % /.';
        }

        if ($datos['tbgastomonto'] === '') {
            $errores['tbgastomonto'] = 'El monto es obligatorio.';
        } elseif (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $datos['tbgastomonto']) || (float) $datos['tbgastomonto'] <= 0) {
            $errores['tbgastomonto'] = 'El monto debe ser mayor que 0, con máximo 2 decimales.';
        }

        $minima = date('Y-m-d', strtotime('-' . self::ANOSMAXIMOS . ' years'));
        if ($datos['tbgastofecha'] === '') {
            $errores['tbgastofecha'] = 'La fecha es obligatoria.';
        } elseif (!$this->fechavalida($datos['tbgastofecha'])) {
            $errores['tbgastofecha'] = 'La fecha no es válida.';
        } elseif ($datos['tbgastofecha'] > date('Y-m-d')) {
            $errores['tbgastofecha'] = 'La fecha no puede ser posterior a hoy.';
        } elseif ($datos['tbgastofecha'] < $minima && $datos['tbgastofecha'] !== $fechaanterior) {
            $errores['tbgastofecha'] = 'La fecha no puede ser de hace más de ' . self::ANOSMAXIMOS . ' años.';
        }

        if (!$errores && $this->repositorio->existeigual($datos['tbgastocategoria'], $datos['tbgastodescripcion'],
                number_format((float) $datos['tbgastomonto'], 2, '.', ''), $datos['tbgastofecha'], $idexcluir)) {
            $errores['tbgastodescripcion'] = 'Ya hay un gasto igual (misma categoría, descripción, monto y fecha). ¿Se registró dos veces?';
        }

        return $errores;
    }

    private function convertirarreglo(Gasto $gasto)
    {
        return [
            'tbgastoid'          => (int) $gasto->getTbgastoid(),
            'tbgastocategoria'   => $gasto->getTbgastocategoria(),
            'tbgastodescripcion' => $gasto->getTbgastodescripcion(),
            'tbgastomonto'       => (float) $gasto->getTbgastomonto(),
            'tbgastofecha'       => $gasto->getTbgastofecha(),
            'tbgastoestado'      => (int) $gasto->getTbgastoestado(),
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
