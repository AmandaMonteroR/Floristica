# Florística

Sistema web para la gestión de una floristería: proveedores, inventario, pedidos de compra, catálogo de arreglos florales, encargos y empleados, con inicio de sesión por roles. Desarrollado en PHP (sin framework, patrón MVC) con base de datos MySQL o MariaDB.

## Requisitos

- Linux basado en Debian (probado en Ubuntu y Kali Linux)
- PHP 8 o superior, con las extensiones `pdo_mysql` y `mbstring`
- MySQL o MariaDB (sirve el que trae XAMPP)

## Instalación

Todos los comandos se ejecutan desde la carpeta raíz del proyecto (`Floristica`).

### 1. Instalar PHP

```bash
sudo apt update
sudo apt install php php-mysql php-mbstring
```

### 2. Base de datos: elegir una opción

**Opción A: XAMPP (si ya lo tiene instalado)**

```bash
sudo /opt/lampp/lampp startmysql
```

En los comandos de este README, use `/opt/lampp/bin/mysql -u root` donde dice `mysql -u root -h 127.0.0.1`.

**Opción B: MariaDB del sistema**

```bash
sudo apt install mariadb-server
sudo systemctl start mariadb
sudo systemctl enable mariadb
sudo mariadb -e "ALTER USER 'root'@'localhost' IDENTIFIED VIA mysql_native_password USING PASSWORD(''); FLUSH PRIVILEGES;"
```

No use XAMPP y el MySQL/MariaDB del sistema al mismo tiempo: los dos usan el puerto 3306.

### 3. Crear la base de datos y cargar los datos de prueba

```bash
mysql -u root -h 127.0.0.1 --default-character-set=utf8mb4 < BaseDatos/ScriptsSQL/bdfloristica.sql
mysql -u root -h 127.0.0.1 --default-character-set=utf8mb4 < BaseDatos/DatosIniciales/datosprueba.sql
```

El script `bdfloristica.sql` borra la base y la crea de nuevo, así que también sirve para reiniciarla.

### 4. Ejecutar el proyecto

```bash
cd Publico
php -S localhost:8000
```

Abrir en el navegador: [http://localhost:8000](http://localhost:8000)

## Usuarios de prueba

Los crea `datosprueba.sql`:

| Usuario | Contraseña | Nombre | Rol |
|---------|------------|--------|-----|
| `duena` | `duena1234` | Patricia Mora Solano | Dueña |
| `empleado1` | `empleado1` | Lorena Bermúdez Badilla | Empleado |
| `empleado2` | `empleado2` | Carlos Jiménez Rojas | Empleado |
| `empleado3` | `empleado3` | Valeria Castro Núñez | Empleado |
| `empleado4` | `empleado4` | Andrea Solís Vega | Empleado (inactiva: no puede entrar) |

Si la base se crea sin datos de prueba, la primera vez que se abre el sistema aparece un formulario para crear la cuenta de la dueña.

Los datos de prueba también traen 8 proveedores, 24 productos de inventario, 7 arreglos con foto (en `Publico/imagenes/muestras/`), 4 pedidos y 9 encargos en distintos estados.

## Configuración

Los datos de conexión están en `Configuracion/Configuracion.php`:

| Parámetro | Valor        |
|-----------|--------------|
| Servidor  | 127.0.0.1    |
| Puerto    | 3306         |
| Base      | bdfloristica |
| Usuario   | root         |
| Clave     | (vacía)      |
| Zona horaria | America/Costa_Rica |

Si se usa otro usuario o contraseña, se deben cambiar en ese archivo.

## Estructura del proyecto

```
Floristica/
├── Aplicacion/
│   ├── Controlador/  # Reglas y validaciones de cada módulo
│   ├── Modelo/       # Clases con getters y setters de cada tabla
│   ├── Repositorio/  # Consultas a la base de datos (PDO)
│   └── Vista/        # Páginas en PHP y el menú
├── BaseDatos/
│   ├── ScriptsSQL/     # bdfloristica.sql (estructura)
│   └── DatosIniciales/ # datosprueba.sql y ubicaciones de Costa Rica
├── Configuracion/    # Conexión, países y seguridad (sesión y permisos)
├── Documentacion/
└── Publico/          # index.php (punto de entrada), css, js e imágenes
```

`Publico/index.php` recibe todo: `?modulo=X` muestra la página del módulo y `?modulo=X&accion=Y` responde en JSON. Las acciones que modifican datos solo aceptan POST.

## Inicio de sesión y roles

- Las contraseñas se guardan cifradas con `password_hash`.
- Después de 5 intentos fallidos, el ingreso se bloquea 60 segundos.
- Los permisos se revisan en el servidor en cada petición. Ocultar un botón en la pantalla no es la única protección.
- Si la dueña inactiva a un empleado, este pierde el acceso de inmediato.

| Puede… | Dueña | Empleado |
|--------|:-----:|:--------:|
| Ver el inicio | ✓ | ✓ |
| Ver el catálogo (sin costos ni ganancias) | ✓ | ✓ |
| Crear, editar o inactivar arreglos | ✓ | |
| Registrar encargos | ✓ | ✓ (sin asignar o para sí mismo) |
| Asignar o reasignar encargos a cualquiera | ✓ | |
| Asignarse un encargo libre | ✓ | ✓ |
| Iniciar y terminar encargos | ✓ (todos) | ✓ (solo los suyos) |
| Cancelar o editar encargos | ✓ | |
| Proveedores, inventario, pedidos y empleados | ✓ | |

## Módulos

### Inicio

Resumen según el rol:

- **Dueña:** encargos abiertos y atrasados, productos con stock bajo y pedidos sin recibir.
- **Empleado:** sus encargos y los que puede tomar.

### Proveedores

- **Obligatorios:** nombre de la empresa, nombre del contacto, teléfono, provincia, cantón y distrito.
- **Nombre de la empresa:** máximo 100 caracteres, al menos una letra y no puede repetirse.
- **Nombre del contacto:** máximo 100 caracteres, solo letras y espacios.
- **Teléfono:**
  - Nacional: exactamente 8 dígitos, con formato `0000-0000`.
  - Internacional: código de país más 6 a 12 dígitos.
  - No puede repetirse.
- **Correo:** opcional; si se ingresa, debe ser válido y no repetirse.
- **Ubicación:** provincia, cantón y distrito deben corresponder entre sí, según `BaseDatos/DatosIniciales/ubicaciones.txt`.

### Inventario

- **Categorías:**
  - **Flor:** tipo, color, largo del tallo y días de duración.
  - **Base:** caja o base; plástico o madera; color, forma y medida.
  - **Adorno:** cinta, papel o tarjeta; en las tarjetas el diseño es obligatorio.
  - **Extra:** peluche, chocolate, maquillaje o juguete. Chocolates y maquillaje piden fecha de vencimiento, y no puede ser anterior a hoy.
- **Medidas:** en centímetros, con formato `20` o `20 x 30`.
- **Nombre:** no puede repetirse para el mismo proveedor.
- **Precio:** el del paquete es mayor que 0, con máximo 2 decimales. El precio por unidad se calcula solo: precio del paquete ÷ unidades por paquete.
- **Nivel de stock**, con un círculo de color:
  - Rojo: igual o por debajo del mínimo.
  - Amarillo: hasta 1,5 veces el mínimo.
  - Verde: suficiente.

### Pedidos (compras a proveedores)

- **Proveedor:** solo se puede pedir a proveedores activos, y solo productos de ese proveedor.
- **Fecha:** no puede ser posterior a hoy.
- **Cantidades:** se piden en paquetes, de 1 a 9999.
- **Estados:** Pendiente → Confirmado → Recibido.
  - Solo un pedido pendiente se puede editar o confirmar.
- **Confirmar:** se elige el pago, de contado o a crédito. A crédito el plazo es de 1 a 365 días.
- **Recibir:** suma al stock las unidades de cada paquete.

### Catálogo de arreglos

- **Foto:** obligatoria; JPG, PNG o WEBP de máximo 2 MB.
- **Nombre:** no puede repetirse.
- **Precio:** mayor que 0.
- **Productos:** cada arreglo lleva al menos un producto del inventario, con su cantidad en unidades.
- **Costo y ganancia:** se calculan con el precio por unidad de cada producto. Solo la dueña los ve.

### Encargos

- **Qué es:** un arreglo del catálogo que hay que preparar, con cantidad (1 a 99), fecha de entrega (no anterior a hoy) y nota opcional.
- **Estados:** Pendiente → En proceso → Terminado; también puede pasar a Cancelado.
  - Para iniciarlo, debe estar asignado a alguien.
  - **Terminar:** descuenta del inventario los productos del arreglo multiplicados por la cantidad. Si no alcanza el stock, no deja terminarlo y dice qué falta.
- **Listado:** los encargos atrasados se marcan en rojo, y "Falta stock" avisa cuando el inventario no alcanza.

### Empleados

- **Nombre:** solo letras.
- **Teléfono:** 8 dígitos, no se repite.
- **Usuario:** de 4 a 30 caracteres (letras sin tildes, números, punto o guion bajo); no se repite.
- **Contraseña:** de 8 a 72 caracteres, con al menos una letra y un número. Al editar, se puede dejar vacía para conservar la anterior.
- **Dueña:** solo puede haber una dueña activa. La dueña no puede inactivarse a sí misma ni quitarse el rol.

## Borradores y confirmaciones

- Si se cierra un formulario sin guardar, los datos quedan en el navegador. Al abrirlo de nuevo aparece "Se recuperaron datos que no se habían guardado", con la opción **Descartar**.
- Editar, inactivar, cancelar o terminar algo siempre pide confirmación.

## Históricos

Todo se registra en PHP. La base de datos solo guarda la información.

### Proveedores

Al crear un proveedor, y cada vez que cambia uno de estos datos, se guarda el nuevo valor con fecha y hora:

| Dato | Tabla |
|------|-------|
| Nombre de la empresa | `tbproveedornombreempresahistorico` |
| Nombre del contacto | `tbproveedornombrecontactohistorico` |
| Teléfono | `tbproveedortelefonohistorico` |
| Correo | `tbproveedorcorreohistorico` |
| Ubicación (provincia / cantón / distrito / dirección) | `tbproveedorubicacionhistorico` |
| Estado (activo / inactivo) | `tbproveedorestadohistorico` |

### Encargos

Se ven en **Ver** de cada encargo, con fecha, hora y quién hizo el cambio:

| Dato | Tabla |
|------|-------|
| Estado (Pendiente, En proceso, Terminado, Cancelado) | `tbencargoestadohistorico` |
| Empleado asignado | `tbencargoempleadohistorico` |

### Movimientos de inventario

Cada cambio de stock queda en `tbinventariomovimiento`: el motivo, la cantidad (positiva si entra, negativa si sale), cómo quedó el stock y quién lo hizo. Se ven en **Ver** de cada producto.

| Tipo | Cuándo |
|------|--------|
| Inicial | Al registrar el producto |
| Entrada | Al recibir un pedido |
| Salida | Al terminar un encargo |
| Ajuste | Al corregir el stock a mano editando el producto |

## Solución de problemas

- **`Can't connect to local MySQL server through socket`:** el servidor de base de datos está apagado. Con XAMPP: `sudo /opt/lampp/lampp startmysql`. Con MariaDB: `sudo systemctl start mariadb`.
- **`Address already in use` al ejecutar `php -S`:** el puerto 8000 está ocupado. Use otro, por ejemplo `php -S localhost:8080`.
- **"No se pudo completar la operación en la base de datos" o "Table … doesn't exist":** la base se creó con una versión anterior del script. Vuelva a ejecutar el paso 3, que borra la base y la crea de nuevo.
- **Una página se queda en "Cargando…":** recargue con **Ctrl + Shift + R** para que el navegador no use archivos viejos. Si sigue igual, abra la consola (F12) para ver el error.
- **Las fotos de los arreglos no aparecen:** revise que existan los archivos en `Publico/imagenes/muestras/`. Las fotos que se suben desde el sistema se guardan en `Publico/imagenes/arreglos/`, carpeta que no se sube a GitHub.