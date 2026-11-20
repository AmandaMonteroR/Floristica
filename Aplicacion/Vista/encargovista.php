<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Encargos | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="<?= recurso('css/estilos.css') ?>">
    <link rel="stylesheet" href="<?= recurso('css/encargo.css') ?>">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <header class="encabezado">
        <div>
            <h1>Encargos</h1>
        </div>
        <button type="button" class="boton boton-claro" id="botonnuevo">Nuevo encargo</button>
    </header>

    <nav class="pestanas" aria-label="Catálogo y encargos">
        <a href="index.php?modulo=catalogo">Catálogo</a>
        <a href="index.php?modulo=encargo" class="activa" aria-current="page">Encargos</a>
    </nav>

    <section aria-labelledby="tituloresumenencargos">
        <div class="encargos-seccion-encabezado">
            <h2 id="tituloresumenencargos">Resumen de hoy</h2>
            <span class="texto-suave">Sin encargos cancelados</span>
        </div>
        <div class="resumen-inicio resumen-encargos" aria-live="polite">
            <div class="tarjeta-resumen">
                <span>Encargos de hoy</span>
                <strong class="numero" id="totalencargoshoy">0</strong>
            </div>
            <div class="tarjeta-resumen">
                <span>Por preparar</span>
                <strong class="numero" id="porprepararhoy">0</strong>
            </div>
            <div class="tarjeta-resumen">
                <span>Listos por entregar</span>
                <strong class="numero" id="listosporentregarhoy">0</strong>
            </div>
            <div class="tarjeta-resumen">
                <span>Entregados</span>
                <strong class="numero" id="entregadoshoy">0</strong>
            </div>
        </div>
    </section>

    <div class="encargos-seccion-encabezado">
        <h2 id="titulolistado">Encargos de hoy</h2>
    </div>

    <section class="filtros" aria-label="Filtros de encargos">
        <label class="campo">
            <span>Fecha de entrega</span>
            <select id="filtroperiodo">
                <option value="hoy">Hoy</option>
                <option value="todas">Todas las fechas</option>
                <option value="personalizada">Elegir fecha</option>
            </select>
        </label>
        <label class="campo" id="campofecha" hidden>
            <span>Día</span>
            <input type="date" id="filtrofecha">
        </label>
        <label class="campo">
            <span>Estado</span>
            <select id="filtroestado">
                <option value="">Todos</option>
                <option value="abiertos">Abiertas (pendientes y en proceso)</option>
                <option value="atrasados">Atrasadas</option>
                <option value="Pendiente">Pendientes</option>
                <option value="En proceso">En proceso</option>
                <option value="Terminado">Terminados</option>
                <option value="Entregado">Entregados</option>
                <option value="Cancelado">Cancelados</option>
            </select>
        </label>
        <label class="campo">
            <span>Tipo</span>
            <select id="filtrotipo">
                <option value="">Todos</option>
                <option value="Predefinido">Predefinidos</option>
                <option value="Personalizado">Personalizados</option>
            </select>
        </label>
        <label class="campo campo-busqueda">
            <span>Asignado a</span>
            <select id="filtroempleado">
                <option value="">Todos</option>
                <option value="sinasignar">Sin asignar</option>
            </select>
        </label>
    </section>

    <div id="aviso" class="aviso" role="status" hidden></div>

    <div class="tabla-contenedor">
        <table class="tabla tabla-encargos">
            <thead>
                <tr>
                    <th>N.º</th>
                    <th>Cliente</th>
                    <th>Arreglo</th>
                    <th>Entrega</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th class="columna-acciones">Acciones</th>
                </tr>
            </thead>
            <tbody id="cuerpotabla">
                <tr><td colspan="7" class="vacio">Cargando encargos…</td></tr>
            </tbody>
        </table>
    </div>
</main>

<dialog id="dialogoelegirencargo" class="dialogo" aria-labelledby="tituloelegirencargo">
    <div class="cabecera-modal-encargo">
        <h2 id="tituloelegirencargo">Nuevo encargo</h2>
        <button type="button" class="boton cerrar-modal-encargo" id="botoncerrarelegir" aria-label="Cerrar" title="Cerrar">&times;</button>
    </div>
    <p class="nota">¿Cómo desea preparar el encargo?</p>
    <div class="resumen-inicio">
        <button type="button" class="tarjeta-resumen boton" data-tipoencargo="Predefinido">
            <strong>Del catálogo</strong>
            <span>Seleccione un arreglo del catálogo y agregue los productos adicionales.</span>
        </button>
        <button type="button" class="tarjeta-resumen boton" data-tipoencargo="Personalizado">
            <strong>Personalizado</strong>
            <span>Prepare un arreglo con presupuesto o libre y utilice el asistente.</span>
        </button>
    </div>
    <div class="acciones-dialogo">
        <button type="button" class="boton" id="botoncancelareleccion">Cancelar</button>
    </div>
</dialog>

<dialog id="dialogoformulario" class="dialogo dialogo-ancho">
    <div class="cabecera-modal-encargo">
        <h2 id="tituloformulario">Nuevo encargo</h2>
        <button type="button" class="boton cerrar-modal-encargo" id="botoncerrarformulario" aria-label="Cerrar" title="Cerrar">&times;</button>
    </div>
    <form id="formulario" novalidate>

        <div id="avisoborrador" class="aviso aviso-info" hidden>
            <span>Se recuperaron datos que no se habían guardado.</span>
            <button type="button" class="enlace" id="botondescartarborrador">Descartar</button>
        </div>

        <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>
        <p id="avisotemporadaform" class="aviso aviso-temporada" hidden></p>
        <input type="hidden" name="tbsolicitudencargoid">

        <div class="rejilla">
            <label class="campo campo-completo">
                <span>Cliente *</span>
                <select name="tbclienteid">
                    <option value="">Seleccione…</option>
                </select>
                <small class="nota-campo texto-suave">¿No aparece? <a id="enlaceregistrarcliente" href="index.php?modulo=cliente&amp;nuevo=1&amp;volver=encargo">Registrar cliente</a> y continuar el encargo.</small>
                <small class="error" data-error="tbclienteid"></small>
            </label>
            <input type="hidden" name="tbsolicitudencargotipo">
            <small class="error" data-error="tbsolicitudencargotipo"></small>
        </div>

        <fieldset class="seccion-categoria" id="seccionpredefinido" hidden>
            <legend>Arreglo del catálogo</legend>
            <div class="rejilla">
                <div class="campo">
                    <span>Arreglo *</span>
                    <select name="tbarreglofloralid" hidden aria-label="Arreglo elegido">
                        <option value="">Seleccione…</option>
                    </select>
                    <button type="button" class="boton boton-elegir-arreglo" id="botonelegirarreglo">Buscar arreglo con foto</button>
                    <div id="arregloelegido" class="arreglo-elegido" hidden></div>
                    <small class="error" data-error="tbarreglofloralid"></small>
                </div>
                <div class="campo">
                    <span>Cantidad de arreglos *</span>
                    <div class="contador">
                        <button type="button" class="boton-contador" data-paso="-1" aria-label="Quitar un arreglo">−</button>
                        <input type="text" name="tbsolicitudencargocantidad" inputmode="numeric" maxlength="2" autocomplete="off" aria-label="Cantidad de arreglos">
                        <button type="button" class="boton-contador" data-paso="1" aria-label="Agregar un arreglo">+</button>
                    </div>
                    <small class="error" data-error="tbsolicitudencargocantidad"></small>
                </div>
            </div>
            <div class="lleva-arreglo" id="llevaarreglo" hidden>
                <img id="fotollevaarreglo" alt="">
                <div>
                    <strong id="titulollevaarreglo">Lleva</strong>
                    <ul id="listallevaarreglo"></ul>
                </div>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria" id="seccionpersonalizado" hidden>
            <legend>Arreglo personalizado</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Modalidad *</span>
                    <select name="tbsolicitudencargomodalidad">
                        <option value="">Seleccione…</option>
                        <option value="Presupuesto">Con presupuesto</option>
                        <option value="Libre">Libre (cotizar el total)</option>
                    </select>
                    <small class="error" data-error="tbsolicitudencargomodalidad"></small>
                </label>
                <label class="campo" id="campopresupuesto" hidden>
                    <span>Presupuesto del cliente (₡) *</span>
                    <input type="text" name="tbsolicitudencargopresupuesto" inputmode="decimal" maxlength="11" placeholder="15000" autocomplete="off">
                    <small class="error" data-error="tbsolicitudencargopresupuesto"></small>
                </label>
                <div class="campo campo-completo">
                    <span id="etiquetaporcentaje">Ganancia sobre el costo (%) *</span>
                    <div class="fila-porcentaje">
                        <div class="contador">
                            <button type="button" class="boton-contador" data-pasoporcentaje="-10" aria-label="Bajar 10%">−</button>
                            <input type="text" name="tbsolicitudencargoporcentaje" inputmode="numeric" maxlength="3" autocomplete="off" aria-labelledby="etiquetaporcentaje">
                            <button type="button" class="boton-contador" data-pasoporcentaje="10" aria-label="Subir 10%">+</button>
                        </div>
                        <div class="chips-porcentaje" role="group" aria-label="Porcentajes rápidos">
                            <button type="button" class="chip" data-porcentaje="50">50%</button>
                            <button type="button" class="chip" data-porcentaje="80">80%</button>
                            <button type="button" class="chip" data-porcentaje="100">100%</button>
                            <button type="button" class="chip" data-porcentaje="150">150%</button>
                        </div>
                    </div>
                    <small class="nota-campo texto-suave">El porcentaje se aplica a flores, follajes, papel, cintas y bases. Chocolates, peluches, tarjetas y accesorios conservan su precio de venta.</small>
                    <small class="error" data-error="tbsolicitudencargoporcentaje"></small>
                </div>
            </div>
            <div class="asistente" id="asistente" hidden>
                <div class="asistente-encabezado">
                    <div>
                        <strong><span aria-hidden="true">&#10047;</span> <span id="tituloasistente">Asistente de presupuesto</span></strong>
                        <p class="nota" id="explicacionasistente">Propone arreglos dentro del presupuesto con este % de ganancia. Respeta los productos ya elegidos.</p>
                    </div>
                    <button type="button" class="boton" id="botonsugerir">Sugerir arreglos</button>
                </div>
                <p class="texto-suave" id="notaasistente" aria-live="polite"></p>
                <div class="propuestas" id="propuestas"></div>
            </div>
            <div class="caja-referencia" id="cajareferencia" hidden>
                <input type="hidden" name="tbsolicitudencargoreferencia">
                <img id="fotoreferencia" alt="Foto de referencia" data-lupa>
                <div>
                    <strong>Foto de referencia</strong>
                    <p class="nota">Así lo quiere la clienta; sirve de guía para armarlo. La foto de cómo quedó se agrega después, en "Ver".</p>
                    <button type="button" class="enlace enlace-peligro" id="botonquitarreferencia">Quitar foto de referencia</button>
                    <small class="error" data-error="tbsolicitudencargoreferencia"></small>
                </div>
            </div>
        </fieldset>

        <section id="seccionlineas" hidden>
            <h3 class="subtitulo-seccion" id="titulolineas">Adicionales</h3>
            <p class="nota" id="notalineas"></p>
            <div class="tabla-contenedor">
                <table class="tabla tabla-lineas">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th id="columnaprecio">Precio</th>
                            <th>Subtotal</th>
                            <th><span class="solo-lector">Quitar</span></th>
                        </tr>
                    </thead>
                    <tbody id="cuerpolineas"></tbody>
                </table>
            </div>
            <small class="error" data-error="insumos"></small>
            <div class="pie-lineas">
                <button type="button" class="boton" id="botonbuscarproductoencargo">+ Agregar producto</button>
                <span id="restopresupuesto" class="resto-presupuesto" hidden></span>
            </div>
        </section>

        <fieldset class="seccion-categoria" id="seccionpintura" hidden>
            <legend>Flores pintadas (opcional)</legend>
            <p class="nota">Si el cliente quiere flores pintadas, elija la pintura y cuántas flores. Se cobra cada flor pintada.</p>
            <div class="rejilla">
                <label class="campo">
                    <span>Pintura</span>
                    <select name="tbsolicitudencargosprayid">
                        <option value="">Sin flores pintadas</option>
                    </select>
                    <small class="error" data-error="tbsolicitudencargosprayid"></small>
                </label>
                <div class="campo">
                    <span id="etiquetaflorespintadas">Flores pintadas</span>
                    <div class="contador">
                        <button type="button" class="boton-contador" data-pasoflores="-1" aria-label="Una flor pintada menos">−</button>
                        <input type="text" name="tbsolicitudencargoflorespintadas" inputmode="numeric" maxlength="3" autocomplete="off" placeholder="0" aria-labelledby="etiquetaflorespintadas">
                        <button type="button" class="boton-contador" data-pasoflores="1" aria-label="Una flor pintada más">+</button>
                    </div>
                    <small class="nota-campo texto-suave" id="notapintura"></small>
                    <small class="error" data-error="tbsolicitudencargoflorespintadas"></small>
                </div>
            </div>
        </fieldset>

        <fieldset class="seccion-categoria">
            <legend>Entrega</legend>
            <div class="rejilla">
                <label class="campo">
                    <span>Fecha de entrega *</span>
                    <input type="date" name="tbsolicitudencargofechaentrega">
                    <small class="error" data-error="tbsolicitudencargofechaentrega"></small>
                </label>
                <div class="campo">
                    <span>Servicio express</span>
                    <label class="casilla">
                        <input type="checkbox" name="tbsolicitudencargoexpress" value="1">
                        <span>Llevarlo a domicilio</span>
                    </label>
                    <small class="nota-campo texto-suave" id="notaexpress">Si no, el cliente lo recoge en el local.</small>
                </div>
                <label class="campo" id="campozona" hidden>
                    <span>Zona de entrega *</span>
                    <select name="tbzonaentregaid">
                        <option value="">Seleccione…</option>
                    </select>
                    <small class="error" data-error="tbzonaentregaid"></small>
                </label>
                <label class="campo campo-completo" id="campodireccion" hidden>
                    <span>Dirección de entrega *</span>
                    <textarea name="tbsolicitudencargodireccionentrega" maxlength="200" placeholder="Cariari, barrio Palermo, casa 12…"></textarea>
                    <small class="error" data-error="tbsolicitudencargodireccionentrega"></small>
                </label>
            </div>
        </fieldset>

        <div class="rejilla">
            <label class="campo" id="campoasignar">
                <span>Asignar a</span>
                <select name="tbempleadoid">
                    <option value="">Sin asignar por ahora</option>
                </select>
                <small class="error" data-error="tbempleadoid"></small>
            </label>
            <label class="campo">
                <span>Nota</span>
                <textarea name="tbsolicitudencargonota" maxlength="255" placeholder="Texto de la tarjeta, colores, hora de entrega…"></textarea>
                <small class="error" data-error="tbsolicitudencargonota"></small>
            </label>
        </div>

        <div class="resumen-solicitud" aria-live="polite">
            <dl id="resumentotal"></dl>
            <button type="button" class="boton" id="botonproforma">Ver proforma para el cliente</button>
        </div>

        <div class="acciones-dialogo">
            <button type="button" class="boton" id="botoncancelar">Cancelar</button>
            <button type="submit" class="boton boton-principal" id="botonguardar">Guardar encargo</button>
        </div>
    </form>
</dialog>

<dialog id="dialogocatalogoencargo" class="dialogo dialogo-ancho" aria-labelledby="titulocatalogoencargo">
    <div class="cabecera-modal-encargo">
        <h2 id="titulocatalogoencargo">Elegir arreglo del catálogo</h2>
        <button type="button" class="boton cerrar-modal-encargo" id="botoncerrarcatalogoencargo" aria-label="Cerrar" title="Cerrar">&times;</button>
    </div>
    <label class="campo"><span>Buscar por nombre</span>
        <input type="search" id="buscararregloencargo" placeholder="Rosas, lirios, girasoles…" autocomplete="off">
    </label>
    <div id="galeriaarreglosencargo" class="galeria-seleccion-encargo"></div>
</dialog>

<dialog id="dialogoproductosencargo" class="dialogo dialogo-ancho" aria-labelledby="tituloproductosencargo">
    <div class="cabecera-modal-encargo">
        <h2 id="tituloproductosencargo">Elegir producto</h2>
        <button type="button" class="boton cerrar-modal-encargo" id="botoncerrarproductosencargo" aria-label="Cerrar" title="Cerrar">&times;</button>
    </div>
    <div class="rejilla">
        <label class="campo"><span>Buscar</span><input type="search" id="buscarproductoencargo" placeholder="Chocolate, peluche, rosa…" autocomplete="off"></label>
        <label class="campo"><span>Categoría</span><select id="categoriaproductoencargo"><option value="">Todas las categorías</option></select></label>
    </div>
    <div id="galeriaproductosencargo" class="galeria-seleccion-encargo"></div>
</dialog>

<dialog id="dialogoproforma" class="dialogo dialogo-proforma" aria-labelledby="tituloproforma">
    <div class="barra-proforma">
        <div>
            <h2 id="tituloproforma">Proforma para el cliente</h2>
            <p class="nota">Descárguela en PDF y envíesela por WhatsApp. Lleva la foto del arreglo y de cada adicional.</p>
        </div>
        <div class="acciones-grupo">
            <a class="boton" id="enlacewhatsapp" target="_blank" rel="noopener" hidden>Abrir chat de WhatsApp</a>
            <button type="button" class="boton" id="botonimprimirproforma">Imprimir</button>
            <button type="button" class="boton boton-principal" id="botonpdfproforma">Descargar PDF</button>
            <button type="button" class="boton" id="botoncerrarproforma">Cerrar</button>
        </div>
    </div>
    <p id="avisoproforma" class="aviso aviso-error" role="alert" hidden></p>
    <article class="hoja-proforma" id="hojaproforma"></article>
</dialog>

<dialog id="dialogoasignar" class="dialogo dialogo-angosto">
    <form id="formularioasignar" novalidate>
        <h2>Asignar encargo</h2>
        <p class="nota" id="resumenasignar"></p>
        <p id="avisoasignar" class="aviso aviso-error" role="alert" hidden></p>
        <input type="hidden" name="tbsolicitudencargoid">

        <label class="campo">
            <span>¿Quién la prepara? *</span>
            <select name="tbempleadoid">
                <option value="">Seleccione…</option>
            </select>
            <small class="error" data-error="tbempleadoid"></small>
        </label>

        <div class="acciones-dialogo acciones-separadas">
            <button type="button" class="boton" id="botonasignarme">Asignármela a mí</button>
            <div class="acciones-grupo">
                <button type="button" class="boton" id="botoncancelarasignar">Cancelar</button>
                <button type="submit" class="boton boton-principal" id="botonguardarasignar">Asignar</button>
            </div>
        </div>
    </form>
</dialog>

<dialog id="dialogodetalle" class="dialogo dialogo-ancho" aria-labelledby="titulodetalle">
    <header>
        <div>
            <h2 id="titulodetalle"></h2>
            <p class="texto-suave" id="descripciondetalle"></p>
        </div>
        <button type="button" class="boton" id="botoncerrardetalle"
                aria-label="Cerrar detalle del encargo" title="Cerrar">&times;</button>
    </header>
    <div class="detalle-arreglo">
        <div class="columna-foto" id="columnafoto">
            <img id="fotodetalle" class="foto-detalle" alt="" data-lupa>
            <div class="foto-vacia" id="fotovacia" hidden><span aria-hidden="true">&#10047;</span>Todavía no tiene foto</div>
            <input type="file" id="campofoto" accept="image/jpeg,image/png,image/webp" hidden>
            <button type="button" class="boton boton-foto" id="botonfoto" hidden>Agregar foto</button>
            <small class="nota-foto texto-suave" id="notafoto" hidden>JPG, PNG o WEBP de hasta 2 MB.</small>
            <small class="error" id="errorfoto" role="alert"></small>
            <figure class="figura-referencia" id="figurareferencia" hidden>
                <img id="fotoreferenciadetalle" alt="Foto de referencia" data-lupa>
                <figcaption>Foto de referencia</figcaption>
            </figure>
        </div>
        <div>
            <dl id="listadetalle" class="detalle"></dl>
        </div>
    </div>
    <div class="acciones-detalle" id="accionesdetalle"></div>
    <h3 class="subtitulo-seccion">Cobro</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Concepto</th>
                    <th>Cantidad</th>
                    <th>Precio</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody id="cuerpocobro"></tbody>
        </table>
    </div>
    <h3 class="subtitulo-seccion">Productos que se sacan del inventario</h3>
    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Necesarias</th>
                    <th>En inventario</th>
                    <th>¿Alcanza?</th>
                </tr>
            </thead>
            <tbody id="cuerpodetalle"></tbody>
        </table>
    </div>
    <h3 class="subtitulo-seccion">Historial</h3>
    <div class="tabla-contenedor">
        <table class="tabla tabla-historial">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Qué pasó</th>
                    <th>Hecho por</th>
                </tr>
            </thead>
            <tbody id="cuerpohistorial"></tbody>
        </table>
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
<script src="<?= recurso('js/librerias/html2canvas.min.js') ?>"></script>
<script src="<?= recurso('js/librerias/jspdf.umd.min.js') ?>"></script>
<script src="<?= recurso('js/encargo.js') ?>"></script>
<script src="<?= recurso('js/asistente.js') ?>"></script>
<script src="<?= recurso('js/lupa.js') ?>"></script>
</body>
</html>