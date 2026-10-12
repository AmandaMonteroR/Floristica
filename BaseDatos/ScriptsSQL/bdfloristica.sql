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
    tbproveedornombreempresahistoricofecha DATETIME,
    PRIMARY KEY (tbproveedornombreempresahistoricoid)
);

CREATE TABLE tbproveedornombrecontactohistorico (
    tbproveedornombrecontactohistoricoid INT,
    tbproveedorid INT,
    tbproveedornombrecontactohistoricovalor VARCHAR(100),
    tbproveedornombrecontactohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedornombrecontactohistoricoid)
);

CREATE TABLE tbproveedortelefonohistorico (
    tbproveedortelefonohistoricoid INT,
    tbproveedorid INT,
    tbproveedortelefonohistoricovalor VARCHAR(20),
    tbproveedortelefonohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedortelefonohistoricoid)
);

CREATE TABLE tbproveedorcorreohistorico (
    tbproveedorcorreohistoricoid INT,
    tbproveedorid INT,
    tbproveedorcorreohistoricovalor VARCHAR(100),
    tbproveedorcorreohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorcorreohistoricoid)
);

-- Guarda provincia, cantón, distrito y dirección juntos, separados por " / "
CREATE TABLE tbproveedorubicacionhistorico (
    tbproveedorubicacionhistoricoid INT,
    tbproveedorid INT,
    tbproveedorubicacionhistoricovalor VARCHAR(370),
    tbproveedorubicacionhistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorubicacionhistoricoid)
);

CREATE TABLE tbproveedorestadohistorico (
    tbproveedorestadohistoricoid INT,
    tbproveedorid INT,
    tbproveedorestadohistoricovalor TINYINT(1),
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
    tbinventariostock INT,
    tbinventariostockminimo INT,
    tbinventarioestado TINYINT(1),
    PRIMARY KEY (tbinventarioid)
);

CREATE TABLE tbinventariocategoriaflor (
    tbinventariocategoriaflorid INT,
    tbinventarioid INT,
    tbinventariocategoriaflortipo VARCHAR(50),
    tbinventariocategoriaflorcolor VARCHAR(30),
    tbinventariocategoriaflorlargotallo INT,
    tbinventariocategoriaflordiasduracion INT,
    PRIMARY KEY (tbinventariocategoriaflorid)
);

CREATE TABLE tbinventariocategoriabase (
    tbinventariocategoriabaseid INT,
    tbinventarioid INT,
    tbinventariocategoriabasetipo VARCHAR(30),
    tbinventariocategoriabasematerial VARCHAR(30),
    tbinventariocategoriabasecolor VARCHAR(30),
    tbinventariocategoriabaseforma VARCHAR(30),
    tbinventariocategoriabasemedida VARCHAR(30),
    PRIMARY KEY (tbinventariocategoriabaseid)
);

CREATE TABLE tbinventariocategoriaadorno (
    tbinventariocategoriaadornoid INT,
    tbinventarioid INT,
    tbinventariocategoriaadornotipo VARCHAR(30),
    tbinventariocategoriaadornomaterial VARCHAR(50),
    tbinventariocategoriaadornocolor VARCHAR(30),
    tbinventariocategoriaadornomedida VARCHAR(30),
    tbinventariocategoriaadornodiseno VARCHAR(50),
    PRIMARY KEY (tbinventariocategoriaadornoid)
);

CREATE TABLE tbinventariocategoriaextra (
    tbinventariocategoriaextraid INT,
    tbinventarioid INT,
    tbinventariocategoriaextratipo VARCHAR(30),
    tbinventariocategoriaextramarca VARCHAR(50),
    tbinventariocategoriaextratamano VARCHAR(30),
    tbinventariocategoriaextracolor VARCHAR(30),
    tbinventariocategoriaextracontenido VARCHAR(30),
    tbinventariocategoriaextrafechavencimiento DATE,
    PRIMARY KEY (tbinventariocategoriaextraid)
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

CREATE TABLE tbarreglofloraldetalle (
    tbarreglofloraldetalleid INT,
    tbarreglofloralid INT,
    tbinventarioid INT,
    tbarreglofloraldetallecantidad INT,
    PRIMARY KEY (tbarreglofloraldetalleid)
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

CREATE TABLE tbencargo (
    tbencargoid INT,
    tbarreglofloralid INT,
    tbempleadoid INT,
    tbencargocantidad INT,
    tbencargofechaentrega DATE,
    tbencargonota VARCHAR(255),
    tbencargoestado VARCHAR(20),
    tbencargofecharegistro DATE,
    PRIMARY KEY (tbencargoid)
);