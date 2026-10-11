<?php
require_once __DIR__ . '/../Repositorio/inventariorepositorio.php';
require_once __DIR__ . '/../Repositorio/inventariocategoriarepositorio.php';
require_once __DIR__ . '/../Repositorio/proveedorrepositorio.php';

class inventariocontrolador
{
    private $repositorio;
    private $atributos;
    private $proveedores;
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

        return $this->respuesta(true, '', $datos);
    }

    public function crear($datos)
    {
        $datos = $this->limpiar($datos);
        $errores = $this->validar($datos);

        if ($errores) {
            return $this->respuesta(false, 'Revise los datos ingresados.', ['errores' => $errores]);
        }

        $datos['tbinventariopreciounidad'] = $this->calcularpreciounidad($datos);
        $inventario = new Inventario($datos);

        $id = $this->entransaccion(function () use ($inventario, $datos) {
            $id = $this->repositorio->insertar($inventario);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);

            return $id;
        });

        return $this->respuesta(true, 'Producto registrado correctamente.', ['tbinventarioid' => $id]);
    }

    public function actualizar($datos)
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

        $inventario->setTbproveedorid($datos['tbproveedorid']);
        $inventario->setTbinventarionombre($datos['tbinventarionombre']);
        $inventario->setTbinventariocategoria($datos['tbinventariocategoria']);
        $inventario->setTbinventariopreciopaquete($datos['tbinventariopreciopaquete']);
        $inventario->setTbinventariounidadespaquete($datos['tbinventariounidadespaquete']);
        $inventario->setTbinventariopreciounidad($this->calcularpreciounidad($datos));
        $inventario->setTbinventariostock($datos['tbinventariostock']);
        $inventario->setTbinventariostockminimo($datos['tbinventariostockminimo']);

        $this->entransaccion(function () use ($inventario, $datos, $id) {
            $this->repositorio->actualizar($inventario);
            $this->atributos->eliminar($id);
            $this->atributos->insertar($datos['tbinventariocategoria'], $id, $datos);
        });

        return $this->respuesta(true, 'Producto actualizado correctamente.');
    }

    public function inactivar($id)
    {
        $id = (int) $id;
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        if (!$inventario->estaActivo()) {
            return $this->respuesta(false, 'El producto ya está inactivo.');
        }

        $this->repositorio->inactivar($id);

        return $this->respuesta(true, 'Producto inactivado correctamente.');
    }

    public function reactivar($id)
    {
        $id = (int) $id;
        $inventario = $this->repositorio->obtenerporid($id);

        if (!$inventario) {
            return $this->respuesta(false, 'El producto no existe.');
        }

        if ($inventario->estaActivo()) {
            return $this->respuesta(false, 'El producto ya está activo.');
        }

        $this->repositorio->reactivar($id);

        return $this->respuesta(true, 'Producto reactivado correctamente.');
    }

    private function nombresproveedores()
    {
        $nombres = [];
        foreach ($this->proveedores->listar() as $proveedor) {
            $nombres[$proveedor->getTbproveedorid()] = $proveedor->getTbproveedornombreempresa();
        }

        return $nombres;
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
            $limpios[$campo] = trim((string) ($datos[$campo] ?? ''));
        }

        $limpios['tbinventariopreciopaquete'] = str_replace(',', '.', $limpios['tbinventariopreciopaquete']);

        if (in_array($limpios['tbinventariocategoria'], $this->categorias, true)) {
            foreach ($this->atributos->campos($limpios['tbinventariocategoria']) as $campo) {
                $limpios[$campo] = trim((string) ($datos[$campo] ?? ''));
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

        if ($datos['tbinventariounidadespaquete'] === '') {
            $errores['tbinventariounidadespaquete'] = 'Las unidades por paquete son obligatorias.';
        } elseif (!ctype_digit($datos['tbinventariounidadespaquete']) || (int) $datos['tbinventariounidadespaquete'] < 1) {
            $errores['tbinventariounidadespaquete'] = 'Las unidades por paquete deben ser un número entero mayor que 0.';
        }

        if ($datos['tbinventariostock'] === '') {
            $errores['tbinventariostock'] = 'El stock es obligatorio.';
        } elseif (!ctype_digit($datos['tbinventariostock'])) {
            $errores['tbinventariostock'] = 'El stock debe ser un número entero de 0 o más.';
        }

        if ($datos['tbinventariostockminimo'] === '') {
            $errores['tbinventariostockminimo'] = 'El stock mínimo es obligatorio.';
        } elseif (!ctype_digit($datos['tbinventariostockminimo'])) {
            $errores['tbinventariostockminimo'] = 'El stock mínimo debe ser un número entero de 0 o más.';
        }

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

    private function validarflor($datos)
    {
        $errores = [];

        if ($datos['tbinventariocategoriaflortipo'] === '') {
            $errores['tbinventariocategoriaflortipo'] = 'El tipo de flor es obligatorio.';
        } elseif (mb_strlen($datos['tbinventariocategoriaflortipo']) > 50) {
            $errores['tbinventariocategoriaflortipo'] = 'El tipo de flor no puede superar 50 caracteres.';
        } elseif (!preg_match('/^[\p{L}\s]+$/u', $datos['tbinventariocategoriaflortipo'])) {
            $errores['tbinventariocategoriaflortipo'] = 'El tipo de flor solo puede contener letras.';
        }

        if ($datos['tbinventariocategoriaflorcolor'] === '') {
            $errores['tbinventariocategoriaflorcolor'] = 'El color es obligatorio.';
        } elseif (mb_strlen($datos['tbinventariocategoriaflorcolor']) > 30) {
            $errores['tbinventariocategoriaflorcolor'] = 'El color no puede superar 30 caracteres.';
        } elseif (!preg_match('/^[\p{L}\s]+$/u', $datos['tbinventariocategoriaflorcolor'])) {
            $errores['tbinventariocategoriaflorcolor'] = 'El color solo puede contener letras.';
        }

        if ($datos['tbinventariocategoriaflorlargotallo'] === '') {
            $errores['tbinventariocategoriaflorlargotallo'] = 'El largo del tallo es obligatorio.';
        } elseif (!ctype_digit($datos['tbinventariocategoriaflorlargotallo'])
            || (int) $datos['tbinventariocategoriaflorlargotallo'] < 1
            || (int) $datos['tbinventariocategoriaflorlargotallo'] > 300) {
            $errores['tbinventariocategoriaflorlargotallo'] = 'El largo del tallo debe ser un número entero entre 1 y 300 cm.';
        }

        if ($datos['tbinventariocategoriaflordiasduracion'] === '') {
            $errores['tbinventariocategoriaflordiasduracion'] = 'Los días de duración son obligatorios.';
        } elseif (!ctype_digit($datos['tbinventariocategoriaflordiasduracion'])
            || (int) $datos['tbinventariocategoriaflordiasduracion'] < 1
            || (int) $datos['tbinventariocategoriaflordiasduracion'] > 60) {
            $errores['tbinventariocategoriaflordiasduracion'] = 'Los días de duración deben ser un número entero entre 1 y 60.';
        }

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

        if ($datos['tbinventariocategoriabasecolor'] === '') {
            $errores['tbinventariocategoriabasecolor'] = 'El color es obligatorio.';
        } elseif (mb_strlen($datos['tbinventariocategoriabasecolor']) > 30) {
            $errores['tbinventariocategoriabasecolor'] = 'El color no puede superar 30 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriabaseforma']) > 30) {
            $errores['tbinventariocategoriabaseforma'] = 'La forma no puede superar 30 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriabasemedida']) > 30) {
            $errores['tbinventariocategoriabasemedida'] = 'La medida no puede superar 30 caracteres.';
        }

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

        if (mb_strlen($datos['tbinventariocategoriaadornomaterial']) > 50) {
            $errores['tbinventariocategoriaadornomaterial'] = 'El material no puede superar 50 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriaadornocolor']) > 30) {
            $errores['tbinventariocategoriaadornocolor'] = 'El color no puede superar 30 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriaadornomedida']) > 30) {
            $errores['tbinventariocategoriaadornomedida'] = 'La medida no puede superar 30 caracteres.';
        }

        if ($datos['tbinventariocategoriaadornotipo'] === 'Tarjeta' && $datos['tbinventariocategoriaadornodiseno'] === '') {
            $errores['tbinventariocategoriaadornodiseno'] = 'En las tarjetas, el diseño u ocasión es obligatorio.';
        } elseif (mb_strlen($datos['tbinventariocategoriaadornodiseno']) > 50) {
            $errores['tbinventariocategoriaadornodiseno'] = 'El diseño no puede superar 50 caracteres.';
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

        if (mb_strlen($datos['tbinventariocategoriaextramarca']) > 50) {
            $errores['tbinventariocategoriaextramarca'] = 'La marca no puede superar 50 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriaextratamano']) > 30) {
            $errores['tbinventariocategoriaextratamano'] = 'El tamaño no puede superar 30 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriaextracolor']) > 30) {
            $errores['tbinventariocategoriaextracolor'] = 'El color no puede superar 30 caracteres.';
        }

        if (mb_strlen($datos['tbinventariocategoriaextracontenido']) > 30) {
            $errores['tbinventariocategoriaextracontenido'] = 'El contenido no puede superar 30 caracteres.';
        }

        if (in_array($datos['tbinventariocategoriaextratipo'], $this->extrasconvencimiento, true)) {
            $fecha = DateTime::createFromFormat('Y-m-d', (string) $datos['tbinventariocategoriaextrafechavencimiento']);

            if ($datos['tbinventariocategoriaextrafechavencimiento'] === null) {
                $errores['tbinventariocategoriaextrafechavencimiento'] = 'La fecha de vencimiento es obligatoria en chocolates y maquillaje.';
            } elseif (!$fecha || $fecha->format('Y-m-d') !== $datos['tbinventariocategoriaextrafechavencimiento']) {
                $errores['tbinventariocategoriaextrafechavencimiento'] = 'La fecha de vencimiento no es válida.';
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