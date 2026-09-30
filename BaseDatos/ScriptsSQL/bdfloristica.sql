CREATE DATABASE IF NOT EXISTS bdfloristica;
USE bdfloristica;

CREATE TABLE tbproveedor (
    tbproveedorid INT,
    tbproveedornombreempresa VARCHAR(100),
    tbproveedornombrecontacto VARCHAR(100),
    tbproveedortelefono VARCHAR(20),
    tbproveedorcorreo VARCHAR(100),
    tbproveedordireccion VARCHAR(200),
    tbproveedordescripcion VARCHAR(255),
    tbproveedorestado TINYINT(1),
    tbproveedorfecharegistro DATE,
    PRIMARY KEY (tbproveedorid)
);

CREATE TABLE tbproveedororden (
    tbproveedorordenid INT,
    tbproveedorid INT,
    tbproveedorordenfecha DATE,
    tbproveedorordenestado TINYINT(1),
    PRIMARY KEY (tbproveedorordenid)
);

CREATE TABLE tbproveedorordendetalle (
    tbproveedorordendetalleid INT,
    tbproveedorordenid INT,
    tbproveedorordendetalleinsumoid INT,
    tbproveedorordendetallecantidad INT,
    PRIMARY KEY (tbproveedorordendetalleid)
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

CREATE TABLE tbproveedorestadohistorico (
    tbproveedorestadohistoricoid INT,
    tbproveedorid INT,
    tbproveedorestadohistoricovalor TINYINT(1),
    tbproveedorestadohistoricofecha DATETIME,
    PRIMARY KEY (tbproveedorestadohistoricoid)
);