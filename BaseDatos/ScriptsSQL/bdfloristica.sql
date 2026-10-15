DROP DATABASE IF EXISTS bdfloristica;
CREATE DATABASE IF NOT EXISTS bdfloristica;
USE bdfloristica;

CREATE TABLE tbproveedor (
    tbproveedorid INT,
    tbproveedornombreempresa VARCHAR(100),
    tbproveedornombrecontacto VARCHAR(100),
    tbproveedortelefono VARCHAR(20),
    tbproveedorcorreo VARCHAR(100),
    tbproveedorprovincia VARCHAR(50),
    tbproveedorcanton VARCHAR(50),
    tbproveedordistrito VARCHAR(60),
    tbproveedordireccion VARCHAR(200),
    tbproveedordescripcion VARCHAR(255),
    tbproveedorestado TINYINT(1),
    tbproveedorfecharegistro DATE,
    PRIMARY KEY (tbproveedorid)
);

-- En los históricos de proveedor, tbempleadoid es quien hizo el cambio
CREATE TABLE tbproveedornombreempresahistorico (
    tbproveedornombreempresahistoricoid INT,
    tbproveedorid INT,
    tbproveedornombreempresahistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbproveedornombreempresahistoricofecha DATETIME,
    PRIMARY KEY (tbproveedornombreempresahistoricoid)
);

CREATE TABLE tbproveedornombrecontactohistorico (
    tbproveedornombrecontactohistoricoid INT,
    tbproveedorid INT,
    tbproveedornombrecontactohistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbproveedornombrecontactohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedornombrecontactohistoricoid)
);

CREATE TABLE tbproveedortelefonohistorico (
    tbproveedortelefonohistoricoid INT,
    tbproveedorid INT,
    tbproveedortelefonohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbproveedortelefonohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedortelefonohistoricoid)
);

CREATE TABLE tbproveedorcorreohistorico (
    tbproveedorcorreohistoricoid INT,
    tbproveedorid INT,
    tbproveedorcorreohistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbproveedorcorreohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorcorreohistoricoid)
);

-- Guarda provincia, cantón, distrito y dirección juntos, separados por " / "
CREATE TABLE tbproveedorubicacionhistorico (
    tbproveedorubicacionhistoricoid INT,
    tbproveedorid INT,
    tbproveedorubicacionhistoricovalor VARCHAR(370),
    tbempleadoid INT,
    tbproveedorubicacionhistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorubicacionhistoricoid)
);

CREATE TABLE tbproveedorestadohistorico (
    tbproveedorestadohistoricoid INT,
    tbproveedorid INT,
    tbproveedorestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbproveedorestadohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorestadohistoricoid)
);

-- ---------- Inventario (insumos) ----------
-- Datos que tienen todos los insumos. Lo propio de cada tipo va en su tabla de categoría.
-- Categoría: Flor, Envoltura (papel o cinta), Base, Chocolate, Peluche o Tarjeta.
-- preciounidad es lo que le cuesta a la floristería; precioventa es lo que se le cobra a la clienta
-- por una unidad cuando la pide en un arreglo personalizado o como adicional.
CREATE TABLE tbinventario (
    tbinventarioid INT,
    tbproveedorid INT,
    tbinventarionombre VARCHAR(100),
    tbinventariocategoria VARCHAR(20),
    tbinventariopreciopaquete DECIMAL(10,2),
    tbinventariounidadespaquete INT,
    tbinventariopreciounidad DECIMAL(10,2),
    tbinventarioprecioventa DECIMAL(10,2),
    tbinventariostock INT,
    tbinventariostockminimo INT,
    tbinventarioestado TINYINT(1),
    PRIMARY KEY (tbinventarioid)
);

-- Flores y follaje. Tipo: Flor o Follaje.
-- Variedad: Rosa, Gerbera, Girasol, Clavel, Lirio, Lisianthus, Dragón, Popis, Fully, Áster, Gypson;
-- en follaje: Eucalipto, Helecho, Baby eucalipto (se pueden escribir otras).
CREATE TABLE tbinventariocategoriaflor (
    tbinventariocategoriaflorid INT,
    tbinventarioid INT,
    tbinventariocategoriaflortipo VARCHAR(20),
    tbinventariocategoriaflorvariedad VARCHAR(50),
    tbinventariocategoriaflorcolor VARCHAR(30),
    tbinventariocategoriaflorlargotallo INT,
    tbinventariocategoriaflordiasduracion INT,
    PRIMARY KEY (tbinventariocategoriaflorid)
);

-- Papel y cinta van en una sola tabla porque comparten casi todos sus atributos.
-- Clase: Papel o Cinta.
-- Tipo de papel: Coreano, Kraft, Seda, Celofán, Encerado; tipo de cinta: Satín, Organza, Yute, Raso, Papel.
-- Medida en cm: el papel lleva ancho x largo del pliego ("60 x 60"); la cinta, solo el ancho ("2.5").
-- Acabado (Mate, Brillante, Metalizado, Transparente) solo aplica al papel; en la cinta queda NULL.
CREATE TABLE tbinventariocategoriaenvoltura (
    tbinventariocategoriaenvolturaid INT,
    tbinventarioid INT,
    tbinventariocategoriaenvolturaclase VARCHAR(10),
    tbinventariocategoriaenvolturatipo VARCHAR(30),
    tbinventariocategoriaenvolturacolor VARCHAR(30),
    tbinventariocategoriaenvolturadiseno VARCHAR(50),
    tbinventariocategoriaenvolturamedida VARCHAR(30),
    tbinventariocategoriaenvolturamaterial VARCHAR(30),
    tbinventariocategoriaenvolturaacabado VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriaenvolturaid)
);

-- Tipo: Plástica, Canasta, Caja, Florero, Madera, Espuma floral...
-- Material: Plástico, Madera, Vidrio, Cerámica, Cartón, Mimbre...
-- Medida en cm: alto, ancho, largo o diámetro ("20 x 20 x 15")
CREATE TABLE tbinventariocategoriabase (
    tbinventariocategoriabaseid INT,
    tbinventarioid INT,
    tbinventariocategoriabasetipo VARCHAR(30),
    tbinventariocategoriabasematerial VARCHAR(30),
    tbinventariocategoriabasecolor VARCHAR(30),
    tbinventariocategoriabaseforma VARCHAR(30),
    tbinventariocategoriabasemedida VARCHAR(30),
    tbinventariocategoriabasediseno VARCHAR(50),
    PRIMARY KEY (tbinventariocategoriabaseid)
);

-- Tipo: Con leche, Oscuro, Blanco, Relleno... Presentación: Barra, Bombón, Trufa, Caja...
-- Peso en gramos
CREATE TABLE tbinventariocategoriachocolate (
    tbinventariocategoriachocolateid INT,
    tbinventarioid INT,
    tbinventariocategoriachocolatetipo VARCHAR(30),
    tbinventariocategoriachocolatemarca VARCHAR(50),
    tbinventariocategoriachocolatepresentacion VARCHAR(30),
    tbinventariocategoriachocolatepeso INT,
    tbinventariocategoriachocolatesabor VARCHAR(30),
    tbinventariocategoriachocolatefechavencimiento DATE,
    PRIMARY KEY (tbinventariocategoriachocolateid)
);

-- Tipo: Oso, Conejo, Perro, Corazón... Tamaño: alto en cm. Material: Felpa, Algodón, Poliéster...
CREATE TABLE tbinventariocategoriapeluche (
    tbinventariocategoriapelucheid INT,
    tbinventarioid INT,
    tbinventariocategoriapeluchetipo VARCHAR(30),
    tbinventariocategoriapeluchetamano INT,
    tbinventariocategoriapeluchecolor VARCHAR(30),
    tbinventariocategoriapeluchediseno VARCHAR(50),
    tbinventariocategoriapeluchematerial VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriapelucheid)
);

-- Ocasión: Cumpleaños, Amor, Aniversario, Condolencias, Agradecimiento...
-- Medida en cm ("10 x 7")
CREATE TABLE tbinventariocategoriatarjeta (
    tbinventariocategoriatarjetaid INT,
    tbinventarioid INT,
    tbinventariocategoriatarjetaocasion VARCHAR(30),
    tbinventariocategoriatarjetadiseno VARCHAR(50),
    tbinventariocategoriatarjetacolor VARCHAR(30),
    tbinventariocategoriatarjetamedida VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriatarjetaid)
);

CREATE TABLE tbpedido (
    tbpedidoid INT,
    tbproveedorid INT,
    tbpedidofecha DATE,
    tbpedidoestado VARCHAR(20),
    PRIMARY KEY (tbpedidoid)
);

CREATE TABLE tbpedidodetalle (
    tbpedidodetalleid INT,
    tbpedidoid INT,
    tbinventarioid INT,
    tbpedidodetallecantidad INT,
    tbpedidodetalleprecio DECIMAL(10,2),
    PRIMARY KEY (tbpedidodetalleid)
);

CREATE TABLE tbconfirmacioncompra (
    tbconfirmacioncompraid INT,
    tbpedidoid INT,
    tbconfirmacioncomprafecha DATE,
    tbconfirmacioncompratipopago VARCHAR(10),
    tbconfirmacioncompramonto DECIMAL(10,2),
    tbconfirmacioncompraplazodias INT,
    PRIMARY KEY (tbconfirmacioncompraid)
);

CREATE TABLE tbarreglofloral (
    tbarreglofloralid INT,
    tbarreglofloralnombre VARCHAR(100),
    tbarreglofloraldescripcion VARCHAR(255),
    tbarreglofloralfoto VARCHAR(200),
    tbarreglofloralprecio DECIMAL(10,2),
    tbarreglofloralestado TINYINT(1),
    PRIMARY KEY (tbarreglofloralid)
);

-- Insumos que lleva un arreglo del catálogo y cuántas unidades de cada uno
CREATE TABLE tbarreglofloralinsumo (
    tbarreglofloralinsumoid INT,
    tbarreglofloralid INT,
    tbinventarioid INT,
    tbarreglofloralinsumocantidad INT,
    PRIMARY KEY (tbarreglofloralinsumoid)
);
CREATE TABLE tbempleado (
    tbempleadoid INT,
    tbempleadonombre VARCHAR(100),
    tbempleadotelefono VARCHAR(20),
    tbempleadorol VARCHAR(20),
    tbempleadoestado TINYINT(1),
    tbempleadousuario VARCHAR(30),
    tbempleadoclave VARCHAR(255),
    PRIMARY KEY (tbempleadoid)
);

-- Clientas que hacen solicitudes de encargo
CREATE TABLE tbcliente (
    tbclienteid INT,
    tbclientenombre VARCHAR(100),
    tbclientetelefono VARCHAR(20),
    tbclientecorreo VARCHAR(100),
    tbclientedireccion VARCHAR(200),
    tbclienteestado TINYINT(1),
    tbclientefecharegistro DATE,
    PRIMARY KEY (tbclienteid)
);

-- Zonas del servicio express y lo que se cobra por llevar el arreglo.
-- El propietario las crea y les cambia el precio cuando quiera.
CREATE TABLE tbzonaentrega (
    tbzonaentregaid INT,
    tbzonaentreganombre VARCHAR(100),
    tbzonaentregadescripcion VARCHAR(200),
    tbzonaentregaprecio DECIMAL(10,2),
    tbzonaentregaestado TINYINT(1),
    PRIMARY KEY (tbzonaentregaid)
);

-- Solicitud de encargo: lo que pide una clienta y quién lo prepara.
-- Tipo: Predefinido (un arreglo del catálogo, con adicionales si quiere) o
--       Personalizado (la clienta escoge los insumos uno por uno).
-- Modalidad (solo personalizado):
--   Presupuesto: la clienta da un monto y se agregan insumos hasta llegar a ese límite.
--   Libre: se agregan insumos hasta que la clienta decide que ya está; el total sale de lo escogido.
-- Express: si se lleva a domicilio, con la zona, la dirección y el costo de la zona en ese momento.
-- Los precios se copian al registrar la solicitud, así un cambio de precio después no cambia lo cobrado.
-- subtotal = precioarreglo x cantidad + insumos; total = subtotal + costoexpress.
-- Estado: Pendiente, En proceso, Terminado o Cancelado.
CREATE TABLE tbsolicitudencargo (
    tbsolicitudencargoid INT,
    tbclienteid INT,
    tbempleadoid INT,
    tbsolicitudencargotipo VARCHAR(20),
    tbarreglofloralid INT,
    tbsolicitudencargocantidad INT,
    tbsolicitudencargoprecioarreglo DECIMAL(10,2),
    tbsolicitudencargomodalidad VARCHAR(20),
    tbsolicitudencargopresupuesto DECIMAL(10,2),
    tbsolicitudencargoexpress TINYINT(1),
    tbzonaentregaid INT,
    tbsolicitudencargodireccionentrega VARCHAR(200),
    tbsolicitudencargocostoexpress DECIMAL(10,2),
    tbsolicitudencargosubtotal DECIMAL(10,2),
    tbsolicitudencargototal DECIMAL(10,2),
    tbsolicitudencargofechaentrega DATE,
    tbsolicitudencargonota VARCHAR(255),
    tbsolicitudencargoestado VARCHAR(20),
    tbsolicitudencargofecharegistro DATE,
    PRIMARY KEY (tbsolicitudencargoid)
);

-- Insumos de una solicitud: en un predefinido son los adicionales (chocolates, peluches,
-- cintas...); en un personalizado, todo lo que lleva el arreglo.
-- preciounitario es el precio de venta del insumo cuando se agregó.
CREATE TABLE tbsolicitudencargoinsumo (
    tbsolicitudencargoinsumoid INT,
    tbsolicitudencargoid INT,
    tbinventarioid INT,
    tbsolicitudencargoinsumocantidad INT,
    tbsolicitudencargoinsumopreciounitario DECIMAL(10,2),
    PRIMARY KEY (tbsolicitudencargoinsumoid)
);

-- ---------- Históricos y movimientos ----------
-- En todos, tbempleadoid es quien hizo el cambio (el propietario o un empleado)

-- Cada cambio de estado de una solicitud: Pendiente, En proceso, Terminado o Cancelado
CREATE TABLE tbsolicitudencargoestadohistorico (
    tbsolicitudencargoestadohistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoestadohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbsolicitudencargoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoestadohistoricoid)
);

-- Cada asignación de una solicitud. El valor es el id del empleado al que se le asignó
CREATE TABLE tbsolicitudencargoempleadohistorico (
    tbsolicitudencargoempleadohistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoempleadohistoricovalor INT,
    tbempleadoid INT,
    tbsolicitudencargoempleadohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoempleadohistoricoid)
);

-- Cada vez que cambia el stock de un producto y por qué.
-- Tipo: Inicial, Entrada (pedido recibido), Salida (solicitud de encargo terminada) o Ajuste (edición manual).
-- La cantidad es positiva si entra y negativa si sale.
CREATE TABLE tbinventariomovimiento (
    tbinventariomovimientoid INT,
    tbinventarioid INT,
    tbinventariomovimientotipo VARCHAR(20),
    tbinventariomovimientocantidad INT,
    tbinventariomovimientostockfinal INT,
    tbpedidoid INT,
    tbsolicitudencargoid INT,
    tbempleadoid INT,
    tbinventariomovimientofecha DATETIME,
    PRIMARY KEY (tbinventariomovimientoid)
);

-- ---------- Históricos de inventario ----------
-- Precio: guarda el precio del paquete y las unidades juntos, por ejemplo "12000.00 / 24"
CREATE TABLE tbinventariopreciohistorico (
    tbinventariopreciohistoricoid INT,
    tbinventarioid INT,
    tbinventariopreciohistoricovalor VARCHAR(40),
    tbempleadoid INT,
    tbinventariopreciohistoricofecha DATETIME,
    PRIMARY KEY (tbinventariopreciohistoricoid)
);

-- Precio de venta por unidad (lo que se le cobra a la clienta)
CREATE TABLE tbinventarioprecioventahistorico (
    tbinventarioprecioventahistoricoid INT,
    tbinventarioid INT,
    tbinventarioprecioventahistoricovalor DECIMAL(10,2),
    tbempleadoid INT,
    tbinventarioprecioventahistoricofecha DATETIME,
    PRIMARY KEY (tbinventarioprecioventahistoricoid)
);

CREATE TABLE tbinventariostockminimohistorico (
    tbinventariostockminimohistoricoid INT,
    tbinventarioid INT,
    tbinventariostockminimohistoricovalor INT,
    tbempleadoid INT,
    tbinventariostockminimohistoricofecha DATETIME,
    PRIMARY KEY (tbinventariostockminimohistoricoid)
);

-- El valor es el id del proveedor
CREATE TABLE tbinventarioproveedorhistorico (
    tbinventarioproveedorhistoricoid INT,
    tbinventarioid INT,
    tbinventarioproveedorhistoricovalor INT,
    tbempleadoid INT,
    tbinventarioproveedorhistoricofecha DATETIME,
    PRIMARY KEY (tbinventarioproveedorhistoricoid)
);

CREATE TABLE tbinventarioestadohistorico (
    tbinventarioestadohistoricoid INT,
    tbinventarioid INT,
    tbinventarioestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbinventarioestadohistoricofecha DATETIME,
    PRIMARY KEY (tbinventarioestadohistoricoid)
);

CREATE TABLE tbinventarionombrehistorico (
    tbinventarionombrehistoricoid INT,
    tbinventarioid INT,
    tbinventarionombrehistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbinventarionombrehistoricofecha DATETIME,
    PRIMARY KEY (tbinventarionombrehistoricoid)
);

-- ---------- Históricos del catálogo de arreglos ----------
CREATE TABLE tbarreglofloralpreciohistorico (
    tbarreglofloralpreciohistoricoid INT,
    tbarreglofloralid INT,
    tbarreglofloralpreciohistoricovalor DECIMAL(10,2),
    tbempleadoid INT,
    tbarreglofloralpreciohistoricofecha DATETIME,
    PRIMARY KEY (tbarreglofloralpreciohistoricoid)
);

CREATE TABLE tbarreglofloralestadohistorico (
    tbarreglofloralestadohistoricoid INT,
    tbarreglofloralid INT,
    tbarreglofloralestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbarreglofloralestadohistoricofecha DATETIME,
    PRIMARY KEY (tbarreglofloralestadohistoricoid)
);

CREATE TABLE tbarreglofloralnombrehistorico (
    tbarreglofloralnombrehistoricoid INT,
    tbarreglofloralid INT,
    tbarreglofloralnombrehistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbarreglofloralnombrehistoricofecha DATETIME,
    PRIMARY KEY (tbarreglofloralnombrehistoricoid)
);

-- ---------- Históricos de empleados ----------
-- Aquí tbempleadoid es el empleado al que se le hizo el cambio
-- y tbempleadoidresponsable es quien lo hizo (normalmente el propietario)
CREATE TABLE tbempleadonombrehistorico (
    tbempleadonombrehistoricoid INT,
    tbempleadoid INT,
    tbempleadonombrehistoricovalor VARCHAR(100),
    tbempleadoidresponsable INT,
    tbempleadonombrehistoricofecha DATETIME,
    PRIMARY KEY (tbempleadonombrehistoricoid)
);

CREATE TABLE tbempleadotelefonohistorico (
    tbempleadotelefonohistoricoid INT,
    tbempleadoid INT,
    tbempleadotelefonohistoricovalor VARCHAR(20),
    tbempleadoidresponsable INT,
    tbempleadotelefonohistoricofecha DATETIME,
    PRIMARY KEY (tbempleadotelefonohistoricoid)
);

CREATE TABLE tbempleadorolhistorico (
    tbempleadorolhistoricoid INT,
    tbempleadoid INT,
    tbempleadorolhistoricovalor VARCHAR(20),
    tbempleadoidresponsable INT,
    tbempleadorolhistoricofecha DATETIME,
    PRIMARY KEY (tbempleadorolhistoricoid)
);

CREATE TABLE tbempleadousuariohistorico (
    tbempleadousuariohistoricoid INT,
    tbempleadoid INT,
    tbempleadousuariohistoricovalor VARCHAR(30),
    tbempleadoidresponsable INT,
    tbempleadousuariohistoricofecha DATETIME,
    PRIMARY KEY (tbempleadousuariohistoricoid)
);

-- De la contraseña NUNCA se guarda el valor: solo "Asignada" o "Cambiada"
CREATE TABLE tbempleadoclavehistorico (
    tbempleadoclavehistoricoid INT,
    tbempleadoid INT,
    tbempleadoclavehistoricovalor VARCHAR(20),
    tbempleadoidresponsable INT,
    tbempleadoclavehistoricofecha DATETIME,
    PRIMARY KEY (tbempleadoclavehistoricoid)
);

CREATE TABLE tbempleadoestadohistorico (
    tbempleadoestadohistoricoid INT,
    tbempleadoid INT,
    tbempleadoestadohistoricovalor TINYINT(1),
    tbempleadoidresponsable INT,
    tbempleadoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbempleadoestadohistoricoid)
);

-- ---------- Históricos de pedidos y solicitudes de encargo ----------
-- Pendiente, Confirmado o Recibido (así queda la fecha en que se recibió)
CREATE TABLE tbpedidoestadohistorico (
    tbpedidoestadohistoricoid INT,
    tbpedidoid INT,
    tbpedidoestadohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbpedidoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbpedidoestadohistoricoid)
);

CREATE TABLE tbsolicitudencargofechaentregahistorico (
    tbsolicitudencargofechaentregahistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargofechaentregahistoricovalor DATE,
    tbempleadoid INT,
    tbsolicitudencargofechaentregahistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargofechaentregahistoricoid)
);

-- El total cobrado: cambia si se agregan o quitan insumos o si cambia el express
CREATE TABLE tbsolicitudencargototalhistorico (
    tbsolicitudencargototalhistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargototalhistoricovalor DECIMAL(10,2),
    tbempleadoid INT,
    tbsolicitudencargototalhistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargototalhistoricoid)
);

-- ---------- Históricos de clientes ----------
CREATE TABLE tbclientenombrehistorico (
    tbclientenombrehistoricoid INT,
    tbclienteid INT,
    tbclientenombrehistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbclientenombrehistoricofecha DATETIME,
    PRIMARY KEY (tbclientenombrehistoricoid)
);

CREATE TABLE tbclientetelefonohistorico (
    tbclientetelefonohistoricoid INT,
    tbclienteid INT,
    tbclientetelefonohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbclientetelefonohistoricofecha DATETIME,
    PRIMARY KEY (tbclientetelefonohistoricoid)
);

CREATE TABLE tbclientedireccionhistorico (
    tbclientedireccionhistoricoid INT,
    tbclienteid INT,
    tbclientedireccionhistoricovalor VARCHAR(200),
    tbempleadoid INT,
    tbclientedireccionhistoricofecha DATETIME,
    PRIMARY KEY (tbclientedireccionhistoricoid)
);

CREATE TABLE tbclienteestadohistorico (
    tbclienteestadohistoricoid INT,
    tbclienteid INT,
    tbclienteestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbclienteestadohistoricofecha DATETIME,
    PRIMARY KEY (tbclienteestadohistoricoid)
);

-- ---------- Históricos de zonas de entrega (servicio express) ----------
CREATE TABLE tbzonaentreganombrehistorico (
    tbzonaentreganombrehistoricoid INT,
    tbzonaentregaid INT,
    tbzonaentreganombrehistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbzonaentreganombrehistoricofecha DATETIME,
    PRIMARY KEY (tbzonaentreganombrehistoricoid)
);

CREATE TABLE tbzonaentregapreciohistorico (
    tbzonaentregapreciohistoricoid INT,
    tbzonaentregaid INT,
    tbzonaentregapreciohistoricovalor DECIMAL(10,2),
    tbempleadoid INT,
    tbzonaentregapreciohistoricofecha DATETIME,
    PRIMARY KEY (tbzonaentregapreciohistoricoid)
);

CREATE TABLE tbzonaentregaestadohistorico (
    tbzonaentregaestadohistoricoid INT,
    tbzonaentregaid INT,
    tbzonaentregaestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbzonaentregaestadohistoricofecha DATETIME,
    PRIMARY KEY (tbzonaentregaestadohistoricoid)
);
