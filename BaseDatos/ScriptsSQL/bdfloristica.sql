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
    tbinventariostockreservado INT DEFAULT 0,
    tbinventarioestado TINYINT(1),
    tbinventariofoto VARCHAR(255),
    PRIMARY KEY (tbinventarioid)
);

CREATE TABLE tbinventariootroproveedor (
    tbinventariootroproveedorid INT,
    tbinventarioid INT,
    tbproveedorid INT,
    tbinventariootroproveedorpreciopaquete DECIMAL(10,2),
    PRIMARY KEY (tbinventariootroproveedorid)
);

CREATE TABLE tbcolor (
    tbcolorid INT,
    tbcolornombre VARCHAR(30),
    tbcolorestado TINYINT(1),
    PRIMARY KEY (tbcolorid)
);

CREATE TABLE tbtipoflor (
    tbtipoflorid INT,
    tbtipoflornombre VARCHAR(50),
    tbtipoflorclase VARCHAR(10),
    tbtipoflorestado TINYINT(1),
    PRIMARY KEY (tbtipoflorid)
);

CREATE TABLE tbinventariocategoriaflor (
    tbinventariocategoriaflorid INT,
    tbinventarioid INT,
    tbinventariocategoriaflortipo VARCHAR(20),
    tbtipoflorid INT,
    tbcolorid INT,
    tbinventariocategoriaflorlargotallo INT,
    tbinventariocategoriaflordiasduracion INT,
    PRIMARY KEY (tbinventariocategoriaflorid)
);

CREATE TABLE tbinventariocategoriaenvoltura (
    tbinventariocategoriaenvolturaid INT,
    tbinventarioid INT,
    tbinventariocategoriaenvolturaclase VARCHAR(10),
    tbinventariocategoriaenvolturatipo VARCHAR(30),
    tbcolorid INT,
    tbinventariocategoriaenvolturadiseno VARCHAR(50),
    tbinventariocategoriaenvolturamedida VARCHAR(30),
    tbinventariocategoriaenvolturamaterial VARCHAR(30),
    tbinventariocategoriaenvolturaacabado VARCHAR(30),
    tbinventariocategoriaenvolturapersonalizable TINYINT(1),
    PRIMARY KEY (tbinventariocategoriaenvolturaid)
);

CREATE TABLE tbinventariocategoriabase (
    tbinventariocategoriabaseid INT,
    tbinventarioid INT,
    tbinventariocategoriabasetipo VARCHAR(30),
    tbinventariocategoriabasematerial VARCHAR(30),
    tbcolorid INT,
    tbinventariocategoriabaseforma VARCHAR(30),
    tbinventariocategoriabasemedida VARCHAR(30),
    tbinventariocategoriabasediseno VARCHAR(50),
    PRIMARY KEY (tbinventariocategoriabaseid)
);

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

CREATE TABLE tbinventariocategoriapeluche (
    tbinventariocategoriapelucheid INT,
    tbinventarioid INT,
    tbinventariocategoriapeluchetipo VARCHAR(30),
    tbinventariocategoriapeluchetamano INT,
    tbcolorid INT,
    tbinventariocategoriapeluchediseno VARCHAR(50),
    tbinventariocategoriapeluchematerial VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriapelucheid)
);

CREATE TABLE tbinventariocategoriatarjeta (
    tbinventariocategoriatarjetaid INT,
    tbinventarioid INT,
    tbinventariocategoriatarjetatipo VARCHAR(30),
    tbinventariocategoriatarjetatematica VARCHAR(30),
    tbcolorid INT,
    tbinventariocategoriatarjetamaterial VARCHAR(30),
    tbinventariocategoriatarjetamedida VARCHAR(30),
    tbinventariocategoriatarjetapersonalizable TINYINT(1),
    tbinventariocategoriatarjetaelaboracion VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriatarjetaid)
);

CREATE TABLE tbinventariocategoriaspray (
    tbinventariocategoriasprayid INT,
    tbinventarioid INT,
    tbinventariocategoriaspraytipo VARCHAR(30),
    tbinventariocategoriaspraymarca VARCHAR(50),
    tbcolorid INT,
    tbinventariocategoriasprayaroma VARCHAR(30),
    tbinventariocategoriaspraycontenido INT,
    tbinventariocategoriasprayfechavencimiento DATE,
    tbinventariocategoriaspraycobroflor DECIMAL(10,2),
    PRIMARY KEY (tbinventariocategoriasprayid)
);

CREATE TABLE tbinventariocategoriaaccesorio (
    tbinventariocategoriaaccesorioid INT,
    tbinventarioid INT,
    tbinventariocategoriaaccesoriotipo VARCHAR(30),
    tbinventariocategoriaaccesoriotamano VARCHAR(20),
    tbcolorid INT,
    tbinventariocategoriaaccesoriomaterial VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriaaccesorioid)
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

CREATE TABLE tbpedidoabono (
    tbpedidoabonoid INT,
    tbpedidoid INT,
    tbpedidoabonofecha DATE,
    tbpedidoabonomonto DECIMAL(10,2),
    tbpedidoabonometodo VARCHAR(20),
    tbpedidoabononota VARCHAR(100),
    tbempleadoid INT,
    tbpedidoabonoestado TINYINT(1) DEFAULT 1,
    tbpedidoabonomotivoanulacion VARCHAR(100),
    PRIMARY KEY (tbpedidoabonoid)
);

CREATE TABLE tbsolicitudencargoabono (
    tbsolicitudencargoabonoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoabonofecha DATE,
    tbsolicitudencargoabonomonto DECIMAL(10,2),
    tbsolicitudencargoabonometodo VARCHAR(20),
    tbsolicitudencargoabononota VARCHAR(100),
    tbempleadoid INT,
    tbsolicitudencargoabonoestado TINYINT(1) DEFAULT 1,
    tbsolicitudencargoabonomotivoanulacion VARCHAR(100),
    PRIMARY KEY (tbsolicitudencargoabonoid)
);

CREATE TABLE tbpedidoabonoestadohistorico (
    tbpedidoabonoestadohistoricoid INT,
    tbpedidoabonoid INT,
    tbpedidoabonoestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbpedidoabonoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbpedidoabonoestadohistoricoid)
);

CREATE TABLE tbsolicitudencargoabonoestadohistorico (
    tbsolicitudencargoabonoestadohistoricoid INT,
    tbsolicitudencargoabonoid INT,
    tbsolicitudencargoabonoestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbsolicitudencargoabonoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoabonoestadohistoricoid)
);

CREATE TABLE tbgasto (
    tbgastoid INT,
    tbgastocategoria VARCHAR(30),
    tbgastodescripcion VARCHAR(100),
    tbgastomonto DECIMAL(10,2),
    tbgastofecha DATE,
    tbempleadoid INT,
    tbgastoestado TINYINT(1),
    tbgastoorigen VARCHAR(20) DEFAULT 'Manual',
    tbgastoreferencia INT,
    PRIMARY KEY (tbgastoid)
);

CREATE TABLE tbgastocategoriahistorico (
    tbgastocategoriahistoricoid INT,
    tbgastoid INT,
    tbgastocategoriahistoricovalor VARCHAR(30),
    tbempleadoid INT,
    tbgastocategoriahistoricofecha DATETIME,
    PRIMARY KEY (tbgastocategoriahistoricoid)
);
CREATE TABLE tbgastodescripcionhistorico (
    tbgastodescripcionhistoricoid INT,
    tbgastoid INT,
    tbgastodescripcionhistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbgastodescripcionhistoricofecha DATETIME,
    PRIMARY KEY (tbgastodescripcionhistoricoid)
);
CREATE TABLE tbgastomontohistorico (
    tbgastomontohistoricoid INT,
    tbgastoid INT,
    tbgastomontohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbgastomontohistoricofecha DATETIME,
    PRIMARY KEY (tbgastomontohistoricoid)
);
CREATE TABLE tbgastofechahistorico (
    tbgastofechahistoricoid INT,
    tbgastoid INT,
    tbgastofechahistoricovalor VARCHAR(10),
    tbempleadoid INT,
    tbgastofechahistoricofecha DATETIME,
    PRIMARY KEY (tbgastofechahistoricoid)
);
CREATE TABLE tbgastoestadohistorico (
    tbgastoestadohistoricoid INT,
    tbgastoid INT,
    tbgastoestadohistoricovalor VARCHAR(1),
    tbempleadoid INT,
    tbgastoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbgastoestadohistoricoid)
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

CREATE TABLE tbzonaentrega (
    tbzonaentregaid INT,
    tbzonaentreganombre VARCHAR(100),
    tbzonaentregadescripcion VARCHAR(200),
    tbzonaentregaprecio DECIMAL(10,2),
    tbzonaentregaestado TINYINT(1),
    PRIMARY KEY (tbzonaentregaid)
);

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
    tbtemporadaid INT,
    tbsolicitudencargosprayid INT,
    tbsolicitudencargoflorespintadas INT,
    tbsolicitudencargopreciopintura DECIMAL(10,2),
    tbsolicitudencargocostopintura DECIMAL(10,2),
    tbsolicitudencargoporcentaje DECIMAL(6,2),
    tbsolicitudencargocosto DECIMAL(10,2),
    tbsolicitudencargofoto VARCHAR(255),
    tbsolicitudencargoreferencia VARCHAR(255),
    PRIMARY KEY (tbsolicitudencargoid)
);

CREATE TABLE tbsolicitudencargoinsumo (
    tbsolicitudencargoinsumoid INT,
    tbsolicitudencargoid INT,
    tbinventarioid INT,
    tbsolicitudencargoinsumocantidad INT,
    tbsolicitudencargoinsumopreciounitario DECIMAL(10,2),
    PRIMARY KEY (tbsolicitudencargoinsumoid)
);

CREATE TABLE tbsolicitudencargoestadohistorico (
    tbsolicitudencargoestadohistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoestadohistoricovalor VARCHAR(20),
    tbempleadoid INT,
    tbsolicitudencargoestadohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoestadohistoricoid)
);

CREATE TABLE tbsolicitudencargoempleadohistorico (
    tbsolicitudencargoempleadohistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoempleadohistoricovalor INT,
    tbempleadoid INT,
    tbsolicitudencargoempleadohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoempleadohistoricoid)
);

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
    tbproveedorid INT,
    PRIMARY KEY (tbinventariomovimientoid)
);

CREATE TABLE tbinventariopreciohistorico (
    tbinventariopreciohistoricoid INT,
    tbinventarioid INT,
    tbinventariopreciohistoricovalor VARCHAR(40),
    tbempleadoid INT,
    tbinventariopreciohistoricofecha DATETIME,
    PRIMARY KEY (tbinventariopreciohistoricoid)
);

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

CREATE TABLE tbinventariofotohistorico (
    tbinventariofotohistoricoid INT,
    tbinventarioid INT,
    tbinventariofotohistoricovalor VARCHAR(255),
    tbempleadoid INT,
    tbinventariofotohistoricofecha DATETIME,
    PRIMARY KEY (tbinventariofotohistoricoid)
);

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

CREATE TABLE tbsolicitudencargototalhistorico (
    tbsolicitudencargototalhistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargototalhistoricovalor DECIMAL(10,2),
    tbempleadoid INT,
    tbsolicitudencargototalhistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargototalhistoricoid)
);
CREATE TABLE tbarreglofloralhuella (
    tbarreglofloralid INT,
    tbarreglofloralhuellafoto VARCHAR(255),
    tbarreglofloralhuellavalor TEXT,
    PRIMARY KEY (tbarreglofloralid)
);
CREATE TABLE tbsolicitudencargofotohistorico (
    tbsolicitudencargofotohistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargofotohistoricovalor VARCHAR(255),
    tbempleadoid INT,
    tbsolicitudencargofotohistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargofotohistoricoid)
);
CREATE TABLE tbsolicitudencargoreferenciahistorico (
    tbsolicitudencargoreferenciahistoricoid INT,
    tbsolicitudencargoid INT,
    tbsolicitudencargoreferenciahistoricovalor VARCHAR(255),
    tbempleadoid INT,
    tbsolicitudencargoreferenciahistoricofecha DATETIME,
    PRIMARY KEY (tbsolicitudencargoreferenciahistoricoid)
);

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

CREATE TABLE tbtemporada (
    tbtemporadaid INT,
    tbtemporadanombre VARCHAR(100),
    tbtemporadadescripcion VARCHAR(200),
    tbtemporadavigente TINYINT(1),
    tbtemporadaestado TINYINT(1),
    PRIMARY KEY (tbtemporadaid)
);

CREATE TABLE tbinventariotemporada (
    tbinventariotemporadaid INT,
    tbinventarioid INT,
    tbtemporadaid INT,
    tbinventariotemporadapreciopaquete DECIMAL(10,2),
    PRIMARY KEY (tbinventariotemporadaid)
);

CREATE TABLE tbtemporadanombrehistorico (
    tbtemporadanombrehistoricoid INT,
    tbtemporadaid INT,
    tbtemporadanombrehistoricovalor VARCHAR(100),
    tbempleadoid INT,
    tbtemporadanombrehistoricofecha DATETIME,
    PRIMARY KEY (tbtemporadanombrehistoricoid)
);

CREATE TABLE tbtemporadavigentehistorico (
    tbtemporadavigentehistoricoid INT,
    tbtemporadaid INT,
    tbtemporadavigentehistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbtemporadavigentehistoricofecha DATETIME,
    PRIMARY KEY (tbtemporadavigentehistoricoid)
);

CREATE TABLE tbtemporadaestadohistorico (
    tbtemporadaestadohistoricoid INT,
    tbtemporadaid INT,
    tbtemporadaestadohistoricovalor TINYINT(1),
    tbempleadoid INT,
    tbtemporadaestadohistoricofecha DATETIME,
    PRIMARY KEY (tbtemporadaestadohistoricoid)
);

CREATE TABLE tbinventariopreciotemporadahistorico (
    tbinventariopreciotemporadahistoricoid INT,
    tbinventarioid INT,
    tbinventariopreciotemporadahistoricovalor VARCHAR(30),
    tbempleadoid INT,
    tbinventariopreciotemporadahistoricofecha DATETIME,
    PRIMARY KEY (tbinventariopreciotemporadahistoricoid)
);

CREATE TABLE tbinventariootroproveedorhistorico (
    tbinventariootroproveedorhistoricoid INT,
    tbinventarioid INT,
    tbinventariootroproveedorhistoricovalor VARCHAR(30),
    tbempleadoid INT,
    tbinventariootroproveedorhistoricofecha DATETIME,
    PRIMARY KEY (tbinventariootroproveedorhistoricoid)
);