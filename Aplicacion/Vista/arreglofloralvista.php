<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Catálogo | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
    <style>
        #dialogoexportarcatalogo { width: min(930px, calc(100% - 2rem)); }
        #dialogoexportarcatalogo .barra-proforma { align-items: center; margin: 0.75rem 0 1rem; }
        #dialogoexportarcatalogo .cabecera-modal-inventario { margin-bottom: 0; }
        #hojacatalogo { background: #fff; color: #392733; }
        #hojacatalogo .catalogo-portada { display: flex; align-items: center; justify-content: space-between;
            gap: 1rem; border-bottom: 3px solid #bf295f; padding-bottom: 1rem; }
        #hojacatalogo .catalogo-marca { display: flex; align-items: center; gap: 0.65rem; color: #a62052;
            font-weight: 700; font-size: 1.35rem; }
        #hojacatalogo .catalogo-marca img { height: 52px; width: 52px; object-fit: contain; }
        #hojacatalogo .catalogo-portada h3 { margin: 0; font-size: 1.5rem; color: #3a2230; }
        #hojacatalogo .catalogo-fecha { font-size: 0.76rem; color: #856675; margin-top: 0.3rem; }
        #hojacatalogo .catalogo-seccion { margin-top: 1.55rem; }
        #hojacatalogo .catalogo-seccion h4 { margin: 0 0 0.8rem; padding-bottom: 0.45rem;
            border-bottom: 1px solid #edc9d8; color: #ad2455; font-size: 1.1rem; }
        #hojacatalogo .catalogo-productos { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; }
        #hojacatalogo .catalogo-ficha { display: flex; flex-direction: column; border: 1px solid #ecd2dc;
            border-radius: 0.65rem; overflow: hidden; background: white; break-inside: avoid; }
        #hojacatalogo .catalogo-imagen { position: relative; height: 330px; background: #fff0f5; }
        #hojacatalogo .catalogo-imagen img { display: block; position: absolute; inset: 0;
            width: 100%; height: 100%; object-fit: contain; }
        #hojacatalogo .catalogo-sinfoto { position: absolute; inset: 0; display: grid; place-content: center;
            gap: 0.25rem; text-align: center; color: #a54a70; font-size: 1.5rem; }
        #hojacatalogo .catalogo-sinfoto small { font-size: 0.7rem; }
        #hojacatalogo .catalogo-info { display: flex; flex: 1; flex-direction: column; gap: 0.4rem; padding: 0.75rem; }
        #hojacatalogo .catalogo-info strong { font-size: 0.86rem; line-height: 1.3; overflow-wrap: anywhere; }
        #hojacatalogo .catalogo-info p { color: #856675; font-size: 0.71rem; line-height: 1.35; margin: 0; }
        #hojacatalogo .catalogo-precio { margin-top: auto; color: #b02154; font-size: 0.95rem; font-weight: 700; }
        #hojacatalogo .catalogo-pie { margin-top: 1.6rem; padding-top: 0.85rem; border-top: 1px solid #eccbd8;
            text-align: center; color: #826576; font-size: 0.76rem; }
        @media (max-width: 650px) {
            #hojacatalogo { padding: 1rem; }
            #hojacatalogo .catalogo-productos { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            #hojacatalogo .catalogo-imagen { height: 240px; }
            #hojacatalogo .catalogo-portada h3 { font-size: 1.1rem; }
        }
        @media print {
            body:has(#dialogoexportarcatalogo[open]) > :not(#dialogoexportarcatalogo) { display: none !important; }
            #dialogoexportarcatalogo[open] { display: block; position: static; width: auto; max-width: none;
                max-height: none; margin: 0; padding: 0; border: none; overflow: visible; background: white; }
            #dialogoexportarcatalogo::backdrop, #dialogoexportarcatalogo .cabecera-modal-inventario,
            #dialogoexportarcatalogo .barra-proforma, #avisoexportarcatalogo { display: none !important; }
            #hojacatalogo { padding: 0; box-shadow: none; }
            #hojacatalogo .catalogo-imagen { height: 260px; }
            #hojacatalogo .catalogo-ficha { page-break-inside: avoid; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Catálogo</h1>
        </div>
        <div class="botones-encabezado">
            <button type="button" class="boton boton-borde-claro" id="botonexportarcatalogo">Exportar catálogo</button>
            <button type="button" class="boton boton-borde-claro" id="botonbuscarfoto">Buscar por foto</button>
            <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo arreglo</button>
        </div>
    </header>

    <nav class="pestanas" aria-label="Secciones del catálogo">
        <a href="index.php?modulo=catalogo" class="activa" aria-current="page">Catálogo</a>
        <a href="index.php?modulo=encargo">Encargos</a>
    </nav>

    <section class="filtros">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="busqueda" placeholder="Nombre del arreglo">
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div id="galeria" class="galeria">
        <p class="vacio-galeria">Cargando catálogo…</p>
    </div>

    <section class="catalogo-regalos">
        <h2 class="subtitulo-seccion">Regalos y complementos</h2>
        <p class="nota">Chocolates, peluches, tarjetas y accesorios disponibles en Inventario, con su fotografía y precio de venta.</p>
        <div id="galeriaregalos" class="galeria-seleccion-encargo">
            <p class="nota">Cargando productos…</p>
        </div>
    </section>
</main>

<dialog id="dialogoexportarcatalogo" class="dialogo dialogo-proforma" aria-labelledby="tituloexportarcatalogo">
    <header class="cabecera-modal-inventario">
        <h2 id="tituloexportarcatalogo">Catálogo para el cliente</h2>
        <button type="button" class="cerrar-modal-inventario" id="botoncerrarcatalogo" aria-label="Cerrar catálogo">&times;</button>
    </header>
    <div class="barra-proforma">
        <p class="nota">Fotografías y precios de los arreglos y regalos disponibles.</p>
        <div class="acciones-grupo">
            <button type="button" class="boton" id="botonimprimircatalogo">Imprimir</button>
            <button type="button" class="boton" id="botoncompartircatalogo">Compartir PDF</button>
            <button type="button" class="boton boton-principal" id="botonpdfcatalogo">Descargar PDF</button>
        </div>
    </div>
    <p id="avisoexportarcatalogo" class="aviso aviso-error" role="alert" hidden></p>
    <article class="hoja-proforma" id="hojacatalogo"></article>
</dialog>

<dialog id="dialogoformulario" class="dialogo dialogo-ancho">
    <form id="formulario" novalidate>
        <h2 id="tituloformulario">Nuevo arreglo</h2>
        <p class="nota">Los campos con * son obligatorios. Las cantidades de productos son en unidades.</p>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado (la foto debe elegirse de nuevo).</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbarreglofloralid">

        <div class="formulario-arreglo">
            <div class="campo-foto">
                <span class="etiqueta-foto" id="etiquetafoto">Foto *</span>
                <label class="vista-foto" for="campofoto">
                    <img id="vistafoto" alt="" hidden>
                    <span id="textofoto">Toque para elegir una foto<br><small>JPG, PNG o WEBP, máximo 2 MB</small></span>
                </label>
                <input type="file" id="campofoto" name="tbarreglofloralfoto" accept="image/jpeg,image/png,image/webp" class="solo-lector">
                <small class="error" data-error="tbarreglofloralfoto"></small>
            </div>

            <div class="rejilla">
                <label class="campo campo-completo">
                    <span>Nombre del arreglo *</span>
                    <input type="text" name="tbarreglofloralnombre" maxlength="100">
                    <small class="error" data-error="tbarreglofloralnombre"></small>
                </label>
                <label class="campo campo-completo">
                    <span>Descripción</span>
                    <textarea name="tbarreglofloraldescripcion" maxlength="255" placeholder="Ideal para cumpleaños, incluye tarjeta…"></textarea>
                    <small class="error" data-error="tbarreglofloraldescripcion"></small>
                </label>
                <div class="campo campo-completo">
                    <span id="etiquetagananciaarreglo">Ganancia sobre el costo (%)</span>
                    <div class="fila-porcentaje">
                        <div class="contador">
                            <button type="button" class="boton-contador" data-pasoporcentaje="-10" aria-label="Bajar 10%">−</button>
                            <input type="text" id="porcentajearreglo" inputmode="numeric" maxlength="3" autocomplete="off" value="100" aria-labelledby="etiquetagananciaarreglo">
                            <button type="button" class="boton-contador" data-pasoporcentaje="10" aria-label="Subir 10%">+</button>
                        </div>
                        <div class="chips-porcentaje" role="group" aria-label="Porcentajes rápidos">
                            <button type="button" class="chip" data-porcentaje="50">50%</button>
                            <button type="button" class="chip" data-porcentaje="80">80%</button>
                            <button type="button" class="chip" data-porcentaje="100">100%</button>
                            <button type="button" class="chip" data-porcentaje="150">150%</button>
                        </div>
                    </div>
                    <small class="nota-campo texto-suave">El precio sugerido es el costo de los productos más este %, redondeado hacia arriba a ₡100.</small>
                </div>
                <label class="campo">
                    <span>Precio de venta (₡) *</span>
                    <input type="text" name="tbarreglofloralprecio" inputmode="decimal" maxlength="11" autocomplete="off" placeholder="25000">
                    <small class="nota-campo texto-suave" id="notaprecio"></small>
                    <small class="error" data-error="tbarreglofloralprecio"></small>
                </label>
                <div class="resumen-costo">
                    <span>Costo de productos: <strong id="costoarreglo">₡0,00</strong></span>
                    <span>Precio sugerido: <strong id="sugeridoarreglo">₡0,00</strong></span>
                    <span>Ganancia: <strong id="gananciaarreglo">₡0,00</strong></span>
                </div>
            </div>
        </div>

        <h3 class="subtitulo-seccion">Productos del arreglo</h3>
        <div class="tabla-contenedor">
            <table class="tabla tabla-lineas">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Unidades</th>
                        <th>En inventario</th>
                        <th>Costo unidad</th>
                        <th>Subtotal</th>
                        <th><span class="solo-lector">Quitar</span></th>
                    </tr>
                </thead>
                <tbody id="cuerpolineas"></tbody>
            </table>
        </div>
        <small class="error" data-error="detalles"></small>

        <div class="pie-lineas">
            <button type="button" class="boton" id="botonagregarlinea">+ Agregar productos</button>
        </div>

        <p class="nota nota-temporada">✿ En temporada (San Valentín, Día de la Madre…) el precio del arreglo se calcula solo:
            sube igual que el costo de sus flores, con el mismo porcentaje de ganancia. Lo puede ver en "Ver".</p>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar arreglo</button>
        </div>
    </form>
</dialog>

<dialog id="dialogoproductos" class="dialogo dialogo-ancho" aria-labelledby="tituloproductos">
    <h2 id="tituloproductos">Agregar productos al arreglo</h2>
    <p class="nota">Busque el producto o filtre por categoría y toque "Agregar". Se muestra cuántas unidades hay en inventario.</p>
    <div class="buscador-productos">
        <label class="campo campo-busqueda">
            <span>Buscar</span>
            <input type="search" id="buscarproducto" placeholder="Rosa, papel, base…" autocomplete="off">
        </label>
        <div class="chips-categoria" id="chipscategoria" role="group" aria-label="Categoría"></div>
    </div>
    <ul class="lista-productos" id="listaproductos" aria-live="polite"></ul>
    <div class="acciones-dialogo">
        <span class="texto-suave" id="resumenproductos"></span>
        <button type="button" class="boton boton-principal" id="botonlistoproductos">Listo</button>
    </div>
</dialog>

<dialog id="dialogodetalle" class="dialogo dialogo-ancho">
    <div class="detalle-arreglo">
        <img id="fotodetalle" class="foto-detalle" alt="">
        <div class="detalle-info">
            <h2 id="titulodetalle"></h2>
            <p id="descripciondetalle" class="descripcion-detalle"></p>
            <div id="preciosdetalle" class="precios-detalle"></div>
            <h3 class="subtitulo-seccion">Incluye</h3>
            <ul id="incluyedetalle" class="lista-incluye"></ul>
        </div>
    </div>
    <?php if (Seguridad::espropietario()): ?>
        <details class="costos-detalle">
            <summary>Costo y ganancia</summary>
            <div id="resumencostos" class="resumen-costos"></div>
            <div class="tabla-contenedor">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Unidades</th>
                            <th>Costo normal</th>
                            <th>Costo en temporada alta</th>
                        </tr>
                    </thead>
                    <tbody id="cuerpodetalle"></tbody>
                </table>
            </div>
        </details>
    <?php endif; ?>
    <div class="acciones-dialogo">
        <?php if (Seguridad::espropietario()): ?>
            <button type="button" class="boton" id="botonverhistorial">Ver historial</button>
        <?php endif; ?>
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

<dialog id="dialogobuscarfoto" class="dialogo dialogo-ancho" aria-labelledby="titulobuscarfoto">
    <h2 id="titulobuscarfoto">Buscar arreglos por foto</h2>
    <p class="nota">Suba la foto que mandó la clienta. El sistema la compara con las fotos del catálogo por sus colores y su forma, y muestra los arreglos más parecidos. Si ninguno se parece, puede valorar el ramo de la foto.</p>
    <div class="busqueda-foto">
        <div class="columna-buscada">
            <label class="zona-foto" id="zonabuscarfoto">
                <input type="file" id="campobuscarfoto" accept="image/jpeg,image/png,image/webp" class="visualmente-oculto">
                <span class="icono-zona" aria-hidden="true">&#10047;</span>
                <strong>Elegir foto</strong>
                <small class="texto-suave">o arrástrela aquí · JPG, PNG o WEBP</small>
            </label>
            <img id="fotobuscada" class="foto-buscada" alt="Foto que mandó la clienta" hidden data-lupa>
            <button type="button" class="boton" id="botonotrafoto" hidden>Elegir otra foto</button>
            <div id="coloresbuscada" class="colores-foto"></div>
            <small class="error" id="errorbuscarfoto" role="alert"></small>
        </div>
        <div id="resultadosfoto" class="resultados-foto" aria-live="polite"></div>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton boton-principal" id="botoncerrarbuscarfoto">Cerrar</button>
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

<script src="<?= recurso('js/menu.js') ?>"></script>
<script src="<?= recurso('js/historial.js') ?>"></script>
<script src="<?= recurso('js/librerias/html2canvas.min.js') ?>"></script>
<script src="<?= recurso('js/librerias/jspdf.umd.min.js') ?>"></script>
<script src="<?= recurso('js/arreglofloral.js') ?>"></script>
<script src="<?= recurso('js/buscarfoto.js') ?>"></script>
<script src="<?= recurso('js/valorarramo.js') ?>"></script>
<script src="<?= recurso('js/lupa.js') ?>"></script>
</body>
</html>