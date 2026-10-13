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
            <p class="subtitulo">Flores, bases, adornos y extras disponibles en la floristería.</p>
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
                <option value="Flor">Flor</option>
                <option value="Base">Base</option>
                <option value="Adorno">Adorno</option>
                <option value="Extra">Extra</option>
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
                    <th>Precio unidad</th>
                    <th>Stock</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando inventario…</td></tr>
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
                    <option value="Flor">Flor</option>
                    <option value="Base">Base</option>
                    <option value="Adorno">Adorno</option>
                    <option value="Extra">Extra</option>
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
            <p class="campo-completo precio-unidad">Precio por unidad: <strong id="preciounidad">—</strong></p>
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
            <legend>Datos de la flor</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo de flor *</span>
                    <input type="text" name="tbinventariocategoriaflortipo" maxlength="50" data-filtro="letras" placeholder="Rosa, girasol…">
                    <small class="error" data-error="tbinventariocategoriaflortipo"></small>
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

        <fieldset class="seccion-categoria" data-categoria="Base" hidden>
            <legend>Datos de la base</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriabasetipo">
                        <option value="">Seleccione…</option>
                        <option value="Caja">Caja</option>
                        <option value="Base">Base</option>
                    </select>
                    <small class="error" data-error="tbinventariocategoriabasetipo"></small>
                </label>
                <label class="campo">
                    <span>Material *</span>
                    <select name="tbinventariocategoriabasematerial">
                        <option value="">Seleccione…</option>
                        <option value="Plástico">Plástico</option>
                        <option value="Madera">Madera</option>
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
                    <input type="text" name="tbinventariocategoriabaseforma" maxlength="30" data-filtro="letras" placeholder="Redonda, cuadrada…">
                    <small class="error" data-error="tbinventariocategoriabaseforma"></small>
                </label>
                <label class="campo">
                    <span>Medida (cm)</span>
                    <input type="text" name="tbinventariocategoriabasemedida" maxlength="30" placeholder="20 x 20" autocomplete="off" data-filtro="medida">
                    <small class="error" data-error="tbinventariocategoriabasemedida"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Adorno" hidden>
            <legend>Datos del adorno</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriaadornotipo">
                        <option value="">Seleccione…</option>
                        <option value="Cinta">Cinta</option>
                        <option value="Papel">Papel</option>
                        <option value="Tarjeta">Tarjeta</option>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaadornotipo"></small>
                </label>
                <label class="campo">
                    <span>Material</span>
                    <input type="text" name="tbinventariocategoriaadornomaterial" maxlength="50" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriaadornomaterial"></small>
                </label>
                <label class="campo">
                    <span>Color</span>
                    <input type="text" name="tbinventariocategoriaadornocolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriaadornocolor"></small>
                </label>
                <label class="campo">
                    <span>Medida (cm)</span>
                    <input type="text" name="tbinventariocategoriaadornomedida" maxlength="30" placeholder="2 x 300" autocomplete="off" data-filtro="medida">
                    <small class="error" data-error="tbinventariocategoriaadornomedida"></small>
                </label>
                <label class="campo campo-completo">
                    <span id="etiquetadiseno">Diseño u ocasión</span>
                    <input type="text" name="tbinventariocategoriaadornodiseno" maxlength="50" data-filtro="texto" placeholder="Cumpleaños, aniversario…">
                    <small class="error" data-error="tbinventariocategoriaadornodiseno"></small>
                </label>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" data-categoria="Extra" hidden>
            <legend>Datos del extra</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Tipo *</span>
                    <select name="tbinventariocategoriaextratipo">
                        <option value="">Seleccione…</option>
                        <option value="Peluche">Peluche</option>
                        <option value="Chocolate">Chocolate</option>
                        <option value="Maquillaje">Maquillaje</option>
                        <option value="Juguete">Juguete</option>
                    </select>
                    <small class="error" data-error="tbinventariocategoriaextratipo"></small>
                </label>
                <label class="campo">
                    <span>Marca</span>
                    <input type="text" name="tbinventariocategoriaextramarca" maxlength="50" data-filtro="texto">
                    <small class="error" data-error="tbinventariocategoriaextramarca"></small>
                </label>
                <label class="campo">
                    <span>Tamaño</span>
                    <input type="text" name="tbinventariocategoriaextratamano" maxlength="30" placeholder="Pequeño, mediano…" data-filtro="texto">
                    <small class="error" data-error="tbinventariocategoriaextratamano"></small>
                </label>
                <label class="campo">
                    <span>Color</span>
                    <input type="text" name="tbinventariocategoriaextracolor" maxlength="30" data-filtro="letras">
                    <small class="error" data-error="tbinventariocategoriaextracolor"></small>
                </label>
                <label class="campo">
                    <span>Contenido</span>
                    <input type="text" name="tbinventariocategoriaextracontenido" maxlength="30" data-filtro="texto" placeholder="12 unidades, 250 g…">
                    <small class="error" data-error="tbinventariocategoriaextracontenido"></small>
                </label>
                <label class="campo" id="campovencimiento" hidden>
                    <span>Fecha de vencimiento *</span>
                    <input type="date" name="tbinventariocategoriaextrafechavencimiento">
                    <small class="error" data-error="tbinventariocategoriaextrafechavencimiento"></small>
                </label>
            </div>
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
<script src="js/inventario.js"></script>
</body>
</html>