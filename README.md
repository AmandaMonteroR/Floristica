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
mariadb -u root -h 127.0.0.1 bdfloristica -e "SHOW TABLES;"
```

```bash
cd Publico
php -S localhost:8000
```

Abrir en el navegador: [http://localhost:8000](http://localhost:8000)