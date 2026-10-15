# Florística

Sistema web para la gestión de una floristería ubicada en El Humo de Roxana, Cariari (Limón): proveedores, inventario, pedidos de compra, catálogo de arreglos florales, clientes, solicitudes de encargo (con servicio express por zonas) y empleados, con inicio de sesión por roles. Desarrollado en PHP (sin framework, patrón MVC) con base de datos MySQL o MariaDB.

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
| `propietario` | `propietario1` | Patricia Mora Solano | Propietario |
| `empleado1` | `empleado1` | Lorena Bermúdez Badilla | Empleado |
| `empleado2` | `empleado2` | Carlos Jiménez Rojas | Empleado |
| `empleado3` | `empleado3` | Valeria Castro Núñez | Empleado |
| `empleado4` | `empleado4` | Andrea Solís Vega | Empleado (inactiva: no puede entrar) |

Si la base se crea sin datos de prueba, la primera vez que se abre el sistema aparece un formulario para crear la cuenta del propietario.

Los datos de prueba también traen:

- 8 proveedores y 32 productos de inventario de las 6 categorías;
- 7 arreglos con foto (en `Publico/imagenes/muestras/`);
- 4 pedidos y 8 clientes;
- 6 zonas de entrega, de El Humo de Roxana (₡1 000) a Limón centro (₡6 000);
- 12 solicitudes de encargo en distintos estados: predefinidas con adicionales, personalizadas con presupuesto y libres, con y sin express.

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
├── Pruebas/          # pruebasbackend.php (pruebas automáticas del backend)
└── Publico/          # index.php (punto de entrada), css, js e imágenes
```

`Publico/index.php` recibe todo: `?modulo=X` muestra la página del módulo y `?modulo=X&accion=Y` responde en JSON. Las acciones que modifican datos solo aceptan POST.

## Inicio de sesión y roles

- Las contraseñas se guardan cifradas con `password_hash`.
- Después de 5 intentos fallidos, el ingreso se bloquea 60 segundos.
- Los permisos se revisan en el servidor en cada petición. Ocultar un botón en la pantalla no es la única protección.
- Si el propietario inactiva a un empleado, este pierde el acceso de inmediato.

| Puede… | Propietario | Empleado |
|--------|:-----:|:--------:|
| Ver el inicio | ✓ | ✓ |
| Ver el catálogo (sin costos ni ganancias) | ✓ | ✓ |
| Crear, editar o inactivar arreglos | ✓ | |
| Registrar, editar e inactivar clientes | ✓ | ✓ |
| Registrar solicitudes de encargo | ✓ | ✓ (sin asignar o para sí mismo) |
| Asignar o reasignar solicitudes a cualquiera | ✓ | |
| Asignarse una solicitud libre | ✓ | ✓ |
| Iniciar y terminar solicitudes | ✓ (todas) | ✓ (solo las suyas) |
| Cancelar o editar solicitudes | ✓ | |
| Zonas de entrega y precios del express | ✓ | |
| Proveedores, inventario, pedidos y empleados | ✓ | |

## Módulos

### Inicio

Resumen según el rol:

- **Propietario:** solicitudes abiertas y atrasadas, productos con stock bajo y pedidos sin recibir.
- **Empleado:** sus solicitudes y las que puede tomar.

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

Cada producto tiene los datos generales en `tbinventario` y sus atributos en la tabla de su categoría (`tbinventariocategoria<categoría>`):

| Categoría | Atributos |
|-----------|-----------|
| Flor | Tipo (flor o follaje), variedad, color, largo del tallo y días de duración |
| Envoltura | Papel o cinta, tipo, color, diseño, medida y material. El papel lleva acabado y la cinta indica si es personalizable |
| Base | Tipo, material, color, forma, medidas y diseño |
| Chocolate | Tipo, marca, presentación, peso, sabor y fecha de vencimiento (no anterior a hoy) |
| Peluche | Tipo, tamaño, color, diseño y material |
| Tarjeta | Tipo, temática, color, material, medidas, si es personalizable y elaboración (hecha en el negocio o comprada) |

- **Listas:** las opciones de cada lista están en `inventariocontrolador::opciones()`. La pantalla y la validación usan las mismas.
- **Medidas:** en centímetros, con formato `20` o `20 x 30`.
- **Nombre:** no puede repetirse para el mismo proveedor.
- **Precios:**
  - El precio del paquete debe ser mayor que 0, con máximo 2 decimales.
  - El costo por unidad se calcula solo: precio del paquete ÷ unidades por paquete.
  - El **precio de venta** por unidad es el que se le cobra al cliente en un arreglo personalizado o como adicional. No puede ser menor que el costo, y la pantalla muestra la ganancia.
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
- **Productos:** cada arreglo lleva al menos un producto del inventario, con su cantidad en unidades (tabla `tbarreglofloralinsumo`).
- **Costo y ganancia:** se calculan con el precio por unidad de cada producto. Solo el propietario los ve.

### Clientes

- **Nombre:** obligatorio, solo letras.
- **Teléfono (WhatsApp):** obligatorio, 8 dígitos y no se repite. En la lista se puede tocar para abrir WhatsApp.
- **Correo y dirección:** opcionales. La dirección se propone como dirección de entrega cuando se pide express.
- **Búsqueda:** por nombre o por teléfono, con o sin guion.
- **Inactivar:** no se puede si el cliente tiene solicitudes pendientes o en proceso.

### Zonas de entrega (servicio express)

- **Quién:** solo el propietario las crea y les cambia el precio. Cada cambio de precio queda en el historial.
- **Nombre:** obligatorio y no se repite.
- **Precio:** mayor que 0.
- **Orden:** la lista va de la zona más barata (la más cercana) a la más cara.
- **Inactivar:** no se puede si hay solicitudes abiertas con entrega a esa zona.

### Solicitudes de encargo

- **Datos de toda solicitud:** cliente, tipo, fecha de entrega (no anterior a hoy), nota opcional y, si se quiere, a quién se asigna.
- **Predefinida:** un arreglo del catálogo (el que el cliente eligió del catálogo de WhatsApp) por la cantidad (1 a 99), más **adicionales** opcionales: chocolates, cintas, peluches, tarjetas u otros productos, cada uno a su precio de venta.
- **Personalizada:** el cliente elige los productos.
  - **Con presupuesto:** los productos no pueden pasarse del monto que dio el cliente. La pantalla muestra cuánto queda y desactiva los productos que ya no alcanzan.
  - **Libre:** se eligen los productos y el sistema calcula el total para cotizarlo.
- **Servicio express:** se elige la zona y se cobra su precio. La dirección de entrega es obligatoria.
- **Total:** arreglo × cantidad + productos + express. El botón **Copiar cotización para WhatsApp** copia el detalle con el total.
- **Precios guardados:** se guardan los precios del momento. Si después cambian en el catálogo, el inventario o las zonas, las solicitudes ya registradas no cambian, ni siquiera al editarlas.
- **Estados:** Pendiente → En proceso → Terminado; también puede pasar a Cancelado.
  - Para iniciarla, debe estar asignada a alguien.
  - **Terminar:** descuenta del inventario los productos del arreglo × cantidad y los adicionales, o los productos del personalizado. Si no alcanza el stock, no deja terminarla y dice qué falta.
- **Listado:** se filtra por estado, tipo y persona asignada. Las atrasadas se marcan en rojo, y "Falta stock" avisa cuando el inventario no alcanza.

### Empleados

- **Nombre:** solo letras.
- **Teléfono:** 8 dígitos, no se repite.
- **Usuario:** de 4 a 30 caracteres (letras sin tildes, números, punto o guion bajo); no se repite.
- **Contraseña:** de 8 a 72 caracteres, con al menos una letra y un número. Al editar, se puede dejar vacía para conservar la anterior.
- **Propietario:** solo puede haber un propietario activo. El propietario no puede inactivarse a sí mismo ni quitarse el rol.

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

### Inventario, catálogo, empleados, clientes y zonas

Tablas `tb<entidad><dato>historico`. Se ven con **Historial** y muestran el valor anterior, el nuevo, la fecha y quién hizo el cambio:

| Módulo | Datos con histórico |
|--------|---------------------|
| Inventario | Nombre, proveedor, precio del paquete, precio de venta, stock mínimo y estado |
| Catálogo | Nombre, precio y estado |
| Empleados | Nombre, teléfono, rol, usuario, contraseña (solo "Asignada" o "Cambiada") y estado |
| Clientes | Nombre, teléfono, dirección y estado |
| Zonas de entrega | Nombre, precio y estado |

### Solicitudes de encargo

Se ven en **Ver** de cada solicitud, con fecha, hora y quién hizo el cambio:

| Dato | Tabla |
|------|-------|
| Estado (Pendiente, En proceso, Terminado, Cancelado) | `tbsolicitudencargoestadohistorico` |
| Empleado asignado | `tbsolicitudencargoempleadohistorico` |
| Fecha de entrega | `tbsolicitudencargofechaentregahistorico` |
| Total | `tbsolicitudencargototalhistorico` |

### Movimientos de inventario

Cada cambio de stock queda en `tbinventariomovimiento`: el motivo, la cantidad (positiva si entra, negativa si sale), cómo quedó el stock y quién lo hizo. Se ven en **Ver** de cada producto.

| Tipo | Cuándo |
|------|--------|
| Inicial | Al registrar el producto |
| Entrada | Al recibir un pedido |
| Salida | Al terminar una solicitud de encargo |
| Ajuste | Al corregir el stock a mano editando el producto |

## Pruebas automáticas

`Pruebas/pruebasbackend.php` revisa el backend con peticiones reales al servidor: sesión, permisos por rol, validaciones, cambios de estado, stock e históricos de todos los módulos.

1. Cargue la base limpia (paso 3 de la instalación).
2. Encienda el servidor en otra terminal (paso 4).
3. Desde la carpeta raíz, corra:

```bash
   php Pruebas/pruebasbackend.php
```

Al final muestra cuántas pruebas pasaron. Las pruebas crean datos, así que después hay que volver a cargar la base.

## Solución de problemas

- **`Can't connect to local MySQL server through socket`:** el servidor de base de datos está apagado. Con XAMPP: `sudo /opt/lampp/lampp startmysql`. Con MariaDB: `sudo systemctl start mariadb`.
- **`Address already in use` al ejecutar `php -S`:** el puerto 8000 está ocupado. Use otro, por ejemplo `php -S localhost:8080`.
- **"No se pudo completar la operación en la base de datos" o "Table … doesn't exist":** la base se creó con una versión anterior del script. Vuelva a ejecutar el paso 3, que borra la base y la crea de nuevo.
- **Una página se queda en "Cargando…":** recargue con **Ctrl + Shift + R** para que el navegador no use archivos viejos. Si sigue igual, abra la consola (F12) para ver el error.
- **Las fotos de los arreglos no aparecen:** revise que existan los archivos en `Publico/imagenes/muestras/`. Las fotos que se suben desde el sistema se guardan en `Publico/imagenes/arreglos/`, carpeta que no se sube a GitHub.