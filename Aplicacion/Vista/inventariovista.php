<?php
// Opciones de las listas de cada categoría: son las mismas que valida el controlador
$opciones = inventariocontrolador::opciones();

function opcionesinventario($lista)
{
    foreach ($lista as $valor) {
        echo '<option value="' . e($valor) . '">' . e($valor) . '</option>';
    }
}

// Para "Personalizable": el valor que se guarda (1 o 0) y el texto que se ve (Sí o No)
function opcionespersonalizable($lista)
{
    foreach ($lista as $valor => $texto) {
        echo '<option value="' . e($valor) . '">' . e($texto) . '</option>';
    }
}

$categoriasinventario = [
    'Flor'      => 'Flor o follaje',
    'Envoltura' => 'Envoltura (papel o cinta)',
    'Base'      => 'Base',
    'Chocolate' => 'Chocolate',
    'Peluche'   => 'Peluche',
    'Tarjeta'   => 'Tarjeta',
    'Spray'     => 'Spray (pintura, abrillantador o aromatizante)',
    'Accesorio' => 'Accesorio (corona, perfume, splash…)',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Inventario</h1>
            <p class="subtitulo">Flores, papel, cintas, bases, chocolates, peluches, tarjetas, sprays y accesorios de la floristería.</p>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo producto</button>
    </header>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre del producto">
        </label>
        <label class="campo">
            <span>Categoría</span>
            <select id="filtrocategoria">
                <option value="">Todas</option>
                <?php foreach ($categoriasinventario as $valor => $texto): ?>
                    <option value="<?= e($valor) ?>"><?= e($texto) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </label>
        <label class="campo">
            <span>Nivel de stock</span>
            <select id="filtrostock">
                <option value="">Todos</option>
                <option value="bajo">Bajo el mínimo</option>
                <option value="medio">Cerca del mínimo</option>
                <option value="bien">Suficiente</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla tabla-inventario">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Proveedor</th>
                    <th>Costo unidad</th>
                    <th>Precio venta</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="8" class="vacio">Cargando inventario…</td></tr>
            </tbody>
        </table>
    </div>

    <p class="leyenda-stock">
        <span><span class="punto stock-bajo"></span>Bajo el mínimo</span>
        <span><span class="punto stock-medio"></span>Cerca del mínimo</span>
        <span><span class="punto stock-bien"></span>Suficiente</span>
    </p>
</main>

<dialog id="dialogoformulario" class="dialogo">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo producto</h2>
        <p class="nota">Los campos con * son obligatorios.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbinventarioid">

        <div class="rejilla">
            <label class="campo campo-completo">
                <span>Nombre del producto *</span>
                <input type="text" name="tbinventarionombre" maxlength="100" data-filtro="nombre">
                <small class="error" data-error="tbinventarionombre"></small>
            </label>
            <label class="campo">
                <span>Proveedor *</span>
                <select name="tbproveedorid">
                    <option value="">Seleccione…</option>
                </select>
                <small class="error" data-error="tbproveedorid"></small>
            </label>
            <label class="campo">
                <span>Categoría *</span>
                <select name="tbinventariocategoria">
                    <option value="">Seleccione…</option>
                    <?php foreach ($categoriasinventario as $valor => $texto): ?>
                        <option value="<?= e($valor) ?>"><?= e($texto) ?></option>
                    <?php endforeach; ?>
                </select>
                <small class="error" data-error="tbinventariocategoria"></small>
            </label>
            <label class="campo">
                <span>Precio del paquete (₡) *</span>
                <input type="text" name="tbinventariopreciopaquete" inputmode="decimal" maxlength="11" placeholder="12000" autocomplete="off" data-filtro="decimal">
                <small class="error" data-error="tbinventariopreciopaquete"></small>
            </label>
            <div class="campo">
                <span>Unidades por paquete *</span>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" data-campo="tbinventariounidadespaquete" aria-label="Quitar una unidad por paquete">−</button>
                    <input type="text" name="tbinventariounidadespaquete" inputmode="numeric" maxlength="5" autocomplete="off" data-filtro="entero" aria-label="Unidades por paquete">
                    <button type="button" class="boton-contador" data-paso="1" data-campo="tbinventariounidadespaquete" aria-label="Agregar una unidad por paquete">+</button>
                </div>
                <small class="error" data-error="tbinventariounidadespaquete"></small>
            </div>
            <p class="campo-completo precio-unidad">Costo por unidad: <strong id="preciounidad">—</strong></p>
            <label class="campo">
                <span>Precio de venta por unidad (₡) *</span>
                <input type="text" name="tbinventarioprecioventa" inputmode="decimal" maxlength="11" placeholder="1000" autocomplete="off" data-filtro="decimal">
                <small class="error" data-error="tbinventarioprecioventa"></small>
            </label>
            <p class="campo precio-unidad precio-ganancia">Ganancia por unidad: <strong id="gananciaunidad">—</strong><br>
                <small class="texto-suave">Es lo que se le cobra a la clienta por unidad en un arreglo personalizado o como adicional.</small></p>
            <div class="campo">
                <span>Stock actual (unidades) *</span>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" data-campo="tbinventariostock" aria-label="Quitar una unidad del stock">−</button>
                    <input type="text" name="tbinventariostock" inputmode="numeric" maxlength="6" autocomplete="off" data-filtro="entero" aria-label="Stock actual (unidades)">
                    <button type="button" class="boton-contador" data-paso="1" data-campo="tbinventariostock" aria-label="Agregar una unidad al stock">+</button>
                </div>
                <small class="error" data-error="tbinventariostock"></small>
            </div>
            <div class="campo">
                <span>Stock mínimo (unidades) *</span>
                <div class="contador">
                    <button type="button" class="boton-contador" data-paso="-1" data-campo="tbinventariostockminimo" aria-label="Quitar una unidad del stock mínimo">−</button>
                    <input type="text" name="tbinventariostockminimo" inputmode="numeric" maxlength="6" autocomplete="off" data-filtro="entero" aria-label="Stock mínimo (unidades)">
                    <button type="button" class="boton-contador" data-paso="1" data-campo="tbinventariostockminimo" aria-label="Agregar una unidad al stock mínimo">+</button>
                </div>
                <small class="error" data-error="tbinventariostockminimo"></small>
            </div>
        </div>

        <fieldset class="seccion-categoria" data-categoria="Flor" hidden>
            <legend>Datos de la flor o follaje</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriaflortipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tiposflor']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaflortipo"></small>
                </label>
                <label class="campo">
                    <span>Variedad *</span>
                    <input type="text" name="tbinventariocategoriaflorvariedad" maxlength="50" data-filtro="letras" list="listavariedades" placeholder="Rosa, girasol, eucalipto…">
                    <datalist id="listavariedades"></datalist>
                    <small class="error" data-error="tbinventariocategoriaflorvariedad"></small>
                </label>
                <label class="campo">
                    <span>Color *</span>
                    <input type="text" name="tbinventariocategoriaflorcolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriaflorcolor"></small>
                </label>
                <label class="campo">
                    <span>Largo del tallo (cm) *</span>
                    <input type="text" name="tbinventariocategoriaflorlargotallo" inputmode="numeric" maxlength="3" autocomplete="off" placeholder="60" data-filtro="entero">
                    <small class="error" data-error="tbinventariocategoriaflorlargotallo"></small>
                </label>
                <label class="campo">
                    <span>Días de duración *</span>
                    <input type="text" name="tbinventariocategoriaflordiasduracion" inputmode="numeric" maxlength="2" autocomplete="off" placeholder="7" data-filtro="entero">
                    <small class="error" data-error="tbinventariocategoriaflordiasduracion"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Envoltura" hidden>
            <legend>Datos del papel o la cinta</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>¿Papel o cinta? *</span>
                    <select name="tbinventariocategoriaenvolturaclase">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['clasesenvoltura']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaenvolturaclase"></small>
                </label>
                <label class="campo">
                    <span id="etiquetatipoenvoltura">Tipo *</span>
                    <select name="tbinventariocategoriaenvolturatipo"
                            data-opciones="<?= e(json_encode($opciones['tiposenvoltura'], JSON_UNESCAPED_UNICODE)) ?>">
                        <option value="">Primero elija papel o cinta</option>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaenvolturatipo"></small>
                </label>
                <label class="campo">
                    <span>Color *</span>
                    <input type="text" name="tbinventariocategoriaenvolturacolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriaenvolturacolor"></small>
                </label>
                <label class="campo">
                    <span>Diseño</span>
                    <input type="text" name="tbinventariocategoriaenvolturadiseno" maxlength="50" data-filtro="letras" placeholder="Liso, estampado, con brillo…">
                    <small class="error" data-error="tbinventariocategoriaenvolturadiseno"></small>
                </label>
                <label class="campo">
                    <span id="etiquetamedidaenvoltura">Medida (cm)</span>
                    <input type="text" name="tbinventariocategoriaenvolturamedida" maxlength="30" placeholder="60 x 60" autocomplete="off" data-filtro="medida">
                    <small class="error" data-error="tbinventariocategoriaenvolturamedida"></small>
                </label>
                <label class="campo">
                    <span>Material</span>
                    <input type="text" name="tbinventariocategoriaenvolturamaterial" maxlength="30" data-filtro="letras" placeholder="Papel, plástico, tela…">
                    <small class="error" data-error="tbinventariocategoriaenvolturamaterial"></small>
                </label>
                <label class="campo" id="campopersonalizable" hidden>
                    <span>¿Personalizable? *</span>
                    <select name="tbinventariocategoriaenvolturapersonalizable">
                        <option value="">Seleccione…</option>
                        <?php opcionespersonalizable($opciones['personalizable']); ?>
                    </select>
                    <small class="nota-campo texto-suave">Si se le puede imprimir un nombre o mensaje.</small>
                    <small class="error" data-error="tbinventariocategoriaenvolturapersonalizable"></small>
                </label>
                <label class="campo" id="campoacabado" hidden>
                    <span>Acabado</span>
                    <select name="tbinventariocategoriaenvolturaacabado">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['acabadospapel']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaenvolturaacabado"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Base" hidden>
            <legend>Datos de la base</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriabasetipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tiposbase']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriabasetipo"></small>
                </label>
                <label class="campo">
                    <span>Material *</span>
                    <select name="tbinventariocategoriabasematerial">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['materialesbase']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriabasematerial"></small>
                </label>
                <label class="campo">
                    <span>Color *</span>
                    <input type="text" name="tbinventariocategoriabasecolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriabasecolor"></small>
                </label>
                <label class="campo">
                    <span>Forma</span>
                    <input type="text" name="tbinventariocategoriabaseforma" maxlength="30" data-filtro="letras" placeholder="Redonda, cuadrada, corazón…">
                    <small class="error" data-error="tbinventariocategoriabaseforma"></small>
                </label>
                <label class="campo">
                    <span>Medidas (cm)</span>
                    <input type="text" name="tbinventariocategoriabasemedida" maxlength="30" placeholder="20 x 20 x 15" autocomplete="off" data-filtro="medida">
                    <small class="error" data-error="tbinventariocategoriabasemedida"></small>
                </label>
                <label class="campo">
                    <span>Diseño</span>
                    <input type="text" name="tbinventariocategoriabasediseno" maxlength="50" data-filtro="letras" placeholder="Liso, decorado, estampado…">
                    <small class="error" data-error="tbinventariocategoriabasediseno"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Chocolate" hidden>
            <legend>Datos del chocolate</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriachocolatetipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tiposchocolate']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriachocolatetipo"></small>
                </label>
                <label class="campo">
                    <span>Presentación *</span>
                    <select name="tbinventariocategoriachocolatepresentacion">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['presentacioneschocolate']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriachocolatepresentacion"></small>
                </label>
                <label class="campo">
                    <span>Marca</span>
                    <input type="text" name="tbinventariocategoriachocolatemarca" maxlength="50" data-filtro="texto">
                    <small class="error" data-error="tbinventariocategoriachocolatemarca"></small>
                </label>
                <label class="campo">
                    <span>Peso (g) *</span>
                    <input type="text" name="tbinventariocategoriachocolatepeso" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="100" data-filtro="entero">
                    <small class="error" data-error="tbinventariocategoriachocolatepeso"></small>
                </label>
                <label class="campo">
                    <span>Sabor</span>
                    <input type="text" name="tbinventariocategoriachocolatesabor" maxlength="30" data-filtro="letras" placeholder="Fresa, caramelo, menta…">
                    <small class="error" data-error="tbinventariocategoriachocolatesabor"></small>
                </label>
                <label class="campo">
                    <span>Fecha de vencimiento *</span>
                    <input type="date" name="tbinventariocategoriachocolatefechavencimiento">
                    <small class="error" data-error="tbinventariocategoriachocolatefechavencimiento"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Peluche" hidden>
            <legend>Datos del peluche</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <input type="text" name="tbinventariocategoriapeluchetipo" maxlength="30" data-filtro="letras" list="listatipospeluche" placeholder="Oso, conejo, corazón…">
                    <datalist id="listatipospeluche">
                        <?php opcionesinventario($opciones['tipospeluche']); ?>
                    </datalist>
                    <small class="error" data-error="tbinventariocategoriapeluchetipo"></small>
                </label>
                <label class="campo">
                    <span>Tamaño (cm de alto) *</span>
                    <input type="text" name="tbinventariocategoriapeluchetamano" inputmode="numeric" maxlength="3" autocomplete="off" placeholder="25" data-filtro="entero">
                    <small class="error" data-error="tbinventariocategoriapeluchetamano"></small>
                </label>
                <label class="campo">
                    <span>Color *</span>
                    <input type="text" name="tbinventariocategoriapeluchecolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriapeluchecolor"></small>
                </label>
                <label class="campo">
                    <span>Material</span>
                    <select name="tbinventariocategoriapeluchematerial">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['materialespeluche']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriapeluchematerial"></small>
                </label>
                <label class="campo campo-completo">
                    <span>Diseño</span>
                    <input type="text" name="tbinventariocategoriapeluchediseno" maxlength="50" data-filtro="texto" placeholder="Personaje, animal o diseño decorativo">
                    <small class="error" data-error="tbinventariocategoriapeluchediseno"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Tarjeta" hidden>
            <legend>Datos de la tarjeta</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo de tarjeta *</span>
                    <select name="tbinventariocategoriatarjetatipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tipostarjeta']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriatarjetatipo"></small>
                </label>
                <label class="campo">
                    <span>Temática *</span>
                    <select name="tbinventariocategoriatarjetatematica">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tematicastarjeta']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriatarjetatematica"></small>
                </label>
                <label class="campo">
                    <span>Color *</span>
                    <input type="text" name="tbinventariocategoriatarjetacolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriatarjetacolor"></small>
                </label>
                <label class="campo">
                    <span>Material *</span>
                    <select name="tbinventariocategoriatarjetamaterial">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['materialestarjeta']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriatarjetamaterial"></small>
                </label>
                <label class="campo">
                    <span>Medidas (cm)</span>
                    <input type="text" name="tbinventariocategoriatarjetamedida" maxlength="30" placeholder="10 x 7" autocomplete="off" data-filtro="medida">
                    <small class="error" data-error="tbinventariocategoriatarjetamedida"></small>
                </label>
                <label class="campo">
                    <span>¿Personalizable? *</span>
                    <select name="tbinventariocategoriatarjetapersonalizable">
                        <option value="">Seleccione…</option>
                        <?php opcionespersonalizable($opciones['personalizable']); ?>
                    </select>
                    <small class="nota-campo texto-suave">Si se le puede escribir o imprimir un mensaje.</small>
                    <small class="error" data-error="tbinventariocategoriatarjetapersonalizable"></small>
                </label>
                <label class="campo">
                    <span>Elaboración *</span>
                    <select name="tbinventariocategoriatarjetaelaboracion">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['elaboracionestarjeta']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriatarjetaelaboracion"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Spray" hidden>
            <legend>Datos del spray</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo de spray *</span>
                    <select name="tbinventariocategoriaspraytipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tiposspray']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaspraytipo"></small>
                </label>
                <label class="campo">
                    <span>Marca *</span>
                    <input type="text" name="tbinventariocategoriaspraymarca" maxlength="50" data-filtro="texto" placeholder="Design Master, Chrysal…">
                    <small class="error" data-error="tbinventariocategoriaspraymarca"></small>
                </label>
                <label class="campo" id="campospraycolor" hidden>
                    <span>Color de la pintura *</span>
                    <input type="text" name="tbinventariocategoriaspraycolor" maxlength="30" data-filtro="letras" placeholder="Azul, dorado, plateado…">
                    <small class="error" data-error="tbinventariocategoriaspraycolor"></small>
                </label>
                <label class="campo" id="camposprayaroma" hidden>
                    <span>Aroma *</span>
                    <input type="text" name="tbinventariocategoriasprayaroma" maxlength="30" data-filtro="letras" placeholder="Rosas, lavanda…">
                    <small class="error" data-error="tbinventariocategoriasprayaroma"></small>
                </label>
                <label class="campo">
                    <span>Contenido (ml) *</span>
                    <input type="text" name="tbinventariocategoriaspraycontenido" inputmode="numeric" maxlength="4" autocomplete="off" placeholder="340" data-filtro="entero">
                    <small class="error" data-error="tbinventariocategoriaspraycontenido"></small>
                </label>
                <label class="campo">
                    <span>Fecha de vencimiento</span>
                    <input type="date" name="tbinventariocategoriasprayfechavencimiento">
                    <small class="error" data-error="tbinventariocategoriasprayfechavencimiento"></small>
                </label>
                <label class="campo" id="campospraycobro" hidden>
                    <span>Cobro por flor pintada (₡) *</span>
                    <input type="text" name="tbinventariocategoriaspraycobroflor" inputmode="decimal" maxlength="11" autocomplete="off" placeholder="300" data-filtro="decimal">
                    <small class="nota-campo texto-suave">Lo que se le suma al cliente por cada flor que se pinta con esta pintura.</small>
                    <small class="error" data-error="tbinventariocategoriaspraycobroflor"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Accesorio" hidden>
            <legend>Datos del accesorio</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo de accesorio *</span>
                    <select name="tbinventariocategoriaaccesoriotipo">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tiposaccesorio']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaaccesoriotipo"></small>
                </label>
                <label class="campo">
                    <span>Tamaño *</span>
                    <select name="tbinventariocategoriaaccesoriotamano">
                        <option value="">Seleccione…</option>
                        <?php opcionesinventario($opciones['tamanosaccesorio']); ?>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaaccesoriotamano"></small>
                </label>
                <label class="campo">
                    <span>Color</span>
                    <input type="text" name="tbinventariocategoriaaccesoriocolor" maxlength="30" data-filtro="letras" placeholder="Dorado, plateado…">
                    <small class="error" data-error="tbinventariocategoriaaccesoriocolor"></small>
                </label>
                <label class="campo">
                    <span>Material</span>
                    <input type="text" name="tbinventariocategoriaaccesoriomaterial" maxlength="30" data-filtro="texto" placeholder="Metal, plástico, vidrio…">
                    <small class="error" data-error="tbinventariocategoriaaccesoriomaterial"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria">
            <legend>Precio del proveedor en temporada alta</legend>
            <p class="nota">Escriba cuánto le cobra el proveedor por el paquete en temporada alta. El precio de venta sube solo
                lo mismo que subió el costo (se mantiene la ganancia en colones), y también el de los arreglos que lo llevan.
                Si lo deja vacío, se usa el precio normal.</p>
            <div class="rejilla-temporadas" id="preciostemporada"></div>
            <small class="error" data-error="temporadas"></small>
        </fieldset>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar producto</button>
        </div>
    </form>
</dialog>

<dialog id="dialogodetalle" class="dialogo dialogo-ancho">
    <h2 id="titulodetalle"></h2>
    <dl id="listadetalle" class="detalle"></dl>
    <h3 class="subtitulo-seccion">Movimientos del stock</h3>
    <p class="nota">Los 20 más recientes, del más viejo al más nuevo. Las entradas suman y las salidas restan.</p>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Motivo</th>
                    <th>Cantidad</th>
                    <th>Stock</th>
                    <th>Hecho por</th>
                </tr>
            </thead>
            <tbody id="cuerpomovimientos"></tbody>
        </table>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton" id="botonverhistorial">Ver historial</button>
        <button type="button" class="boton boton-principal" id="botoncerrardetalle">Cerrar</button>
    </div>
</dialog>

<dialog id="dialogohistorial" class="dialogo dialogo-ancho">
    <h2 id="titulohistorial"></h2>
    <p class="nota">Cambios en los datos principales, del más antiguo al más reciente.</p>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Dato</th>
                    <th>Valor anterior</th>
                    <th>Valor nuevo</th>
                    <th>Hecho por</th>
                </tr>
            </thead>
            <tbody id="cuerpohistorial"></tbody>
        </table>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton boton-principal" id="botoncerrarhistorial">Cerrar</button>
    </div>
</dialog>

<dialog id="dialogoconfirmacion" class="dialogo dialogo-confirmacion">
    <div class="icono-confirmacion" id="iconoconfirmacion" aria-hidden="true"></div>
    <h2 id="tituloconfirmacion"></h2>
    <p id="mensajeconfirmacion" class="mensaje-confirmacion"></p>
    <div class="acciones-dialogo acciones-centradas">
        <button type="button" class="boton" id="botoncancelarconfirmacion">Cancelar</button>
        <button type="button" class="boton boton-principal" id="botonaceptarconfirmacion"></button>
    </div>
</dialog>

<script src="js/menu.js"></script>
<script src="js/historial.js"></script>
<script>
    // Sugerencias de variedad según el tipo (flor o follaje)
    const variedadesflor = <?= json_encode($opciones['variedades'], JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="js/preciostemporada.js"></script>
<script src="js/inventario.js"></script>
</body>
</html>
