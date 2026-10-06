<?php

require_once __DIR__ . '/../../Configuracion/Configuracion.php';
require_once __DIR__ . '/../../Aplicacion/Controlador/proveedorcontrolador.php';

$proveedores = [
    ['Flores del Valle', 'María Fernanda Rojas', '8888-1001', 'ventas@floresdelvalle.cr', 'Heredia', 'Barva', 'San Pedro', '300 m sur de la iglesia', 'Rosas, claveles y girasoles'],
    ['Vivero Orosi', 'Carlos Quesada Mora', '8888-1002', 'contacto@viveroorosi.cr', 'Cartago', 'Paraíso', 'Orosi', 'Frente al mirador', 'Plantas ornamentales y follaje'],
    ['Empaques Tacares', 'Laura Jiménez Solís', '8888-1003', 'pedidos@empaquestacares.cr', 'Alajuela', 'Grecia', 'Tacares', 'Bodega 4, zona industrial', 'Papel de envolver, cintas y cajas'],
    ['Tropicales Jacó', 'José Pablo Vargas', '8888-1004', '', 'Puntarenas', 'Garabito', 'Jacó', '', 'Heliconias, aves del paraíso y orquídeas'],
    ['Floricultura Liberia', 'Ana Lucía Chaves', '8888-1005', 'info@floriculturaliberia.cr', 'Guanacaste', 'Liberia', 'Liberia', 'Barrio Los Ángeles, casa 12', 'Flores de temporada'],
    ['Distribuidora Escazú 2000', 'Andrés Núñez Castro', '8888-1006', 'andres@escazu2000.cr', 'San José', 'Escazú', 'San Rafael', 'Centro comercial Plaza Real, local 8', 'Jarrones, canastas y accesorios'],
    ['Orquídeas del Caribe', 'Sofía Brenes Arias', '8888-1007', 'sofia@orquideascaribe.cr', 'Limón', 'Pococí', 'Guápiles', '200 m norte del parque', 'Orquídeas y bromelias'],
    ['Follajes Santo Domingo', 'Diego Ramírez Soto', '8888-1008', 'diego@follajessd.cr', 'Heredia', 'Santo Domingo', 'Santo Domingo', '', 'Helechos, eucalipto y follaje verde'],
];

$campos = [
    'tbproveedornombreempresa', 'tbproveedornombrecontacto', 'tbproveedortelefono', 'tbproveedorcorreo',
    'tbproveedorprovincia', 'tbproveedorcanton', 'tbproveedordistrito', 'tbproveedordireccion', 'tbproveedordescripcion',
];

$controlador = new proveedorcontrolador();
$ids = [];

foreach ($proveedores as $valores) {
    $datos = array_combine($campos, $valores);
    $respuesta = $controlador->crear($datos);

    if ($respuesta['exito']) {
        $ids[$datos['tbproveedornombreempresa']] = $respuesta['datos']['tbproveedorid'];
        echo "Creado: {$datos['tbproveedornombreempresa']}\n";
    } else {
        $detalle = implode(' ', $respuesta['datos']['errores'] ?? [$respuesta['mensaje']]);
        echo "Omitido: {$datos['tbproveedornombreempresa']} ({$detalle})\n";
    }
}

if (isset($ids['Vivero Orosi'])) {
    $id = $ids['Vivero Orosi'];
    $actual = $controlador->obtener($id)['datos'];
    $actual['tbproveedornombrecontacto'] = 'Daniela Quesada Mora';
    $actual['tbproveedortelefono'] = '8888-2002';
    $actual['tbproveedorcanton'] = 'Cartago';
    $actual['tbproveedordistrito'] = 'Oriental';
    $actual['tbproveedordireccion'] = '100 m este de la Basílica';
    $respuesta = $controlador->actualizar($actual);
    echo ($respuesta['exito'] ? 'Editado' : 'No se pudo editar') . ": Vivero Orosi\n";
}

if (isset($ids['Tropicales Jacó'])) {
    $respuesta = $controlador->inactivar($ids['Tropicales Jacó']);
    echo ($respuesta['exito'] ? 'Inactivado' : 'No se pudo inactivar') . ": Tropicales Jacó\n";
}
