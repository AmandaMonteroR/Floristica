# Florística

Sistema web para la gestión de proveedores de una floristería, desarrollado en PHP con base de datos MySQL/MariaDB.

## Requisitos

- Linux basado en Debian (probado en Ubuntu y Kali Linux)
- PHP 8 o superior, con las extensiones `pdo_mysql` y `mbstring`
- MariaDB o MySQL

## Instalación

### 1. Instalar los paquetes necesarios

```bash
sudo apt update
sudo apt install php php-mysql php-mbstring mariadb-server
```

### 2. Iniciar el servidor de base de datos

```bash
sudo systemctl start mariadb
sudo systemctl enable mariadb
```

### 3. Crear la base de datos

Desde la carpeta raíz del proyecto (`Floristica`):

```bash
sudo mariadb < BaseDatos/ScriptsSQL/bdfloristica.sql
```

### 4. Permitir el acceso de root sin contraseña

El proyecto se conecta con el usuario `root` sin contraseña. Por defecto, MariaDB solo permite entrar como root usando `sudo`, así que hay que habilitar el acceso desde PHP:

```bash
sudo mariadb -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;"
```

Si se usa MySQL en lugar de MariaDB:

```bash
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY ''; FLUSH PRIVILEGES;"
```

Para verificar (debe mostrar la lista de tablas):

```bash
mysql -u root -h 127.0.0.1 bdfloristica -e "SHOW TABLES;"
```

### 5. Ejecutar el proyecto

```bash
cd Publico
php -S localhost:8000
```

Abrir en el navegador: [http://localhost:8000](http://localhost:8000)


Opcional: para cargar proveedores de prueba (desde la carpeta raíz del proyecto):

```bash
php BaseDatos/DatosIniciales/cargardatosprueba.php
```

## Configuración

Los datos de conexión se encuentran en `Configuracion/Configuracion.php`. Por defecto el proyecto usa:

| Parámetro | Valor        |
|-----------|--------------|
| Servidor  | 127.0.0.1    |
| Puerto    | 3306         |
| Base      | bdfloristica |
| Usuario   | root         |
| Clave     | (vacía)      |

Si se usa un usuario o contraseña distintos, se deben modificar en ese archivo.

## Solución de problemas

- **"No se pudo completar la operación en la base de datos"**: MariaDB no está corriendo (repetir el paso 2), root no tiene acceso sin contraseña (repetir el paso 4) o la base de datos está desactualizada (ver los siguientes puntos).
- **`mariadb: command not found`**: falta instalar MariaDB (repetir el paso 1).
- **`Can't connect to local server through socket`**: el servicio está apagado. Ejecutar `sudo systemctl start mariadb`.
- **`Address already in use` al ejecutar `php -S`**: el puerto 8000 está ocupado. Usar otro, por ejemplo `php -S localhost:8080`.
- **`Unknown column 'tbproveedorprovincia'` o error al guardar un proveedor**: la base de datos se creó con una versión anterior del script y le faltan las columnas de ubicación. Se pueden agregar sin perder datos:

```bash
mysql -u root -h 127.0.0.1 bdfloristica -e "ALTER TABLE tbproveedor ADD COLUMN tbproveedorprovincia VARCHAR(50) AFTER tbproveedorcorreo, ADD COLUMN tbproveedorcanton VARCHAR(50) AFTER tbproveedorprovincia, ADD COLUMN tbproveedordistrito VARCHAR(60) AFTER tbproveedorcanton;"
```

- **`Table 'bdfloristica.tbproveedorubicacionhistorico' doesn't exist`**: la base se creó antes de que se agregara el histórico de ubicación. Se puede crear la tabla sin perder datos:

```bash
mysql -u root -h 127.0.0.1 bdfloristica -e "CREATE TABLE tbproveedorubicacionhistorico (tbproveedorubicacionhistoricoid INT, tbproveedorid INT, tbproveedorubicacionhistoricovalor VARCHAR(370), tbproveedorubicacionhistoricofecha DATETIME, PRIMARY KEY (tbproveedorubicacionhistoricoid));"
```

- **Recrear la base de datos desde cero** (borra todos los datos registrados), desde la carpeta raíz del proyecto:

```bash
mysql -u root -h 127.0.0.1 -e "DROP DATABASE IF EXISTS bdfloristica;"
mysql -u root -h 127.0.0.1 < BaseDatos/ScriptsSQL/bdfloristica.sql
```

## Estructura del proyecto

```
Floristica/
├── Aplicacion/       # Controladores, modelos, repositorios y vistas
├── BaseDatos/        # Script SQL, datos iniciales y respaldos
├── Configuracion/    # Conexión y parámetros de la base de datos
├── Documentacion/
└── Publico/          # Punto de entrada (index.php), CSS, JS e imágenes
```


## Reglas del módulo Proveedores

Todas las reglas se aplican en PHP (`Aplicacion/Controlador/proveedorcontrolador.php`); la base de datos solo almacena la información.

- **Obligatorios**: nombre de la empresa, nombre del contacto, teléfono, provincia, cantón y distrito.
- **Nombre de la empresa**: máximo 100 caracteres, al menos una letra (se permiten números, como en "Flores 2000") y no puede repetirse.
- **Nombre del contacto**: máximo 100 caracteres; solo letras (con tildes y ñ) y espacios, sin números ni símbolos.
- **Teléfono**: exactamente 8 dígitos, se guarda con formato `0000-0000` y no puede repetirse.
- **Correo**: opcional; si se ingresa, debe tener formato válido y no puede repetirse.
- **Ubicación**: provincia, cantón y distrito deben corresponder entre sí según `BaseDatos/DatosIniciales/ubicaciones.txt`.

### Históricos

Cada vez que se crea un proveedor o cambia uno de estos datos, se registra el nuevo valor con fecha y hora:

| Dato | Tabla |
|------|-------|
| Nombre de la empresa | `tbproveedornombreempresahistorico` |
| Nombre del contacto | `tbproveedornombrecontactohistorico` |
| Teléfono | `tbproveedortelefonohistorico` |
| Correo | `tbproveedorcorreohistorico` |
| Ubicación (provincia / cantón / distrito / dirección) | `tbproveedorubicacionhistorico` |
| Estado (activo / inactivo) | `tbproveedorestadohistorico` |

La descripción es texto libre y no lleva histórico.