# Florística

Sistema web para la gestión de una floristería ubicada en El Humo de Roxana, Cariari (Limón): proveedores, inventario de insumos (con stock apartado y alertas), pedidos de compra, catálogo de arreglos florales (con búsqueda y valoración por foto), clientes, solicitudes de pedido de arreglo (con servicio express por zonas), cuentas por cobrar y por pagar, gastos, reportes y empleados, con inicio de sesión por roles. El ciclo completo está en el [diagrama de flujo](#diagrama-de-flujo). Desarrollado en PHP (sin framework, patrón MVC) con base de datos MySQL o MariaDB.

## Requisitos

- Linux basado en Debian (probado en Ubuntu y Kali Linux)
- PHP 8 o superior, con las extensiones `pdo_mysql`, `mbstring` y `gd` (la búsqueda por foto usa `gd`; en Ubuntu: `sudo apt install php-gd`)
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

### 3. Crear la base de datos con los datos de prueba

```bash
mysql -u root -h 127.0.0.1 --default-character-set=utf8mb4 < BaseDatos/ScriptsSQL/bdfloristica.sql
```

El script `bdfloristica.sql` crea las tablas y carga los datos de prueba en un solo paso. Borra la base y la crea de nuevo, así que también sirve para reiniciarla.

### 4. Ejecutar el proyecto

```bash
cd Publico
php -S localhost:8000
```

Abrir en el navegador: [http://localhost:8000](http://localhost:8000)

## Usuarios de prueba

Los crea `bdfloristica.sql`:

| Usuario | Contraseña | Nombre | Rol |
|---------|------------|--------|-----|
| `propietario` | `propietario1` | Patricia Mora Solano | Propietario |
| `empleado1` | `empleado1` | Lorena Bermúdez Badilla | Empleado |
| `empleado2` | `empleado2` | Carlos Jiménez Rojas | Empleado |
| `empleado3` | `empleado3` | Valeria Castro Núñez | Empleado |
| `empleado4` | `empleado4` | Andrea Solís Vega | Empleado (inactiva: no puede entrar) |

Si la base se crea sin datos de prueba, la primera vez que se abre el sistema aparece un formulario para crear la cuenta del propietario.

Los datos de prueba también traen:

- 8 proveedores y 65 productos de inventario de las 8 categorías. Las flores y follajes tienen los precios reales de un proveedor de la zona (temporada baja); también hay 4 sprays y 4 accesorios (coronas, perfume y splash);
- 18 arreglos del catálogo real de una floristería de la zona, con su foto (en `Publico/imagenes/catalogo/`) y sus precios;
- 4 pedidos y 8 clientes;
- 6 zonas de entrega, de El Humo de Roxana (₡1 000) a Limón centro (₡6 000);
- 22 solicitudes de pedido de arreglo en distintos estados: predefinidas con adicionales, personalizadas con presupuesto y libres, con y sin express. 11 están terminadas (son las ventas de setiembre y octubre). Las 10 abiertas se entregan a partir del día en que se carga la base: una hoy, una atrasada un día (para ver la marca roja) y las demás en la próxima semana, así que nunca salen todas atrasadas;
- la temporada alta, apagada, con los precios reales del proveedor en temporada alta (por ejemplo, 24 rosas pasan de ₡7 000 a ₡14 000);
- 21 colores y 37 tipos de flor y follaje en sus tablas catálogo;
- 6 insumos que también se le compran a otros proveedores (por ejemplo, la rosa roja a Vivero Orosi y a Floricultura Liberia);
- abonos: un pedido a crédito con un abono; de las solicitudes, 9 pagadas completas, 3 con adelanto y 2 ventas a crédito con saldo;
- el stock alcanza para todo lo apartado: ningún insumo tiene más apartado que en inventario (el Lirio rosado tiene 27 y 24 apartados, y sale en rojo por quedar debajo del mínimo);
- 15 gastos de setiembre a noviembre: 13 registrados a mano (alquiler, luz, agua, internet, transporte, publicidad, un ayudante y mantenimiento), uno anulado, y 2 compras a proveedores que se registraron solas (el pedido N.º 1 de contado y el abono al pedido N.º 2).

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
│   ├── ScriptsSQL/     # bdfloristica.sql (tablas y datos de prueba)
│   └── DatosIniciales/ # ubicaciones de Costa Rica
├── Configuracion/    # Conexión, países y seguridad (sesión y permisos)
├── Documentacion/    # diagramaflujo.html (diagrama de flujo del ciclo) y su PDF
├── Pruebas/          # pruebasbackend.php (pruebas automáticas del backend)
└── Publico/          # index.php (punto de entrada), css, js e imágenes
```

`Publico/index.php` recibe todo: `?modulo=X` muestra la página del módulo y `?modulo=X&accion=Y` responde en JSON. Las acciones que modifican datos solo aceptan POST.

## Diagrama de flujo

`Documentacion/diagramaflujo.html` tiene el diagrama de flujo del ciclo del negocio, en tres páginas:

1. **El ciclo completo:** inventario → catálogo → solicitud → preparación → venta y cobro → reportes → compras → recepción, y otra vez al inventario; con los estados de cada cosa y sus tablas.
2. **Una solicitud de pedido de arreglo:** desde que la clienta pide (con o sin foto) hasta que se
   entrega y se cobra: si hay inventario disponible, la proforma con fotos por WhatsApp, si deja adelanto,
   quién la toma, si cancela y si pagó todo.
3. **Una compra al proveedor:** desde la alerta de stock bajo hasta que sube el stock: con pedido
   (si el proveedor no lo confirma se cancela; de contado o a crédito) o comprada directo con
   **Agregar stock**.

Cada paso tiene el color de quién lo hace: un empleado o el propietario, solo el propietario, o el sistema solo. Se abre con doble clic en el navegador; para el PDF, **Ctrl + P → Guardar como PDF** (`Documentacion/diagramaflujo.pdf`).

## Inicio de sesión y roles

- Las contraseñas se guardan cifradas con `password_hash`.
- Cada contraseña incorrecta dice cuántos intentos quedan. Al **tercer intento fallido** el ingreso se bloquea 60 segundos, contados desde ese intento; la pantalla muestra los segundos que faltan bajando de uno en uno (aunque se recargue la página) y el botón **Ingresar** queda apagado hasta que se pueda intentar de nuevo.
- Los scripts y estilos se cargan con su versión (`js/login.js?v=…`, función `recurso()` de `index.php`), así el navegador usa siempre los archivos nuevos y no los que tenía guardados.
- Los permisos se revisan en el servidor en cada petición. Ocultar un botón en la pantalla no es la única protección.
- Si el propietario inactiva a un empleado, este pierde el acceso de inmediato.

| Puede… | Propietario | Empleado |
|--------|:-----:|:--------:|
| Ver el inicio | ✓ | ✓ |
| Ver el catálogo (sin costos ni ganancias) | ✓ | ✓ |
| Crear y editar arreglos (con el costo de los productos y el precio sugerido) | ✓ | ✓ |
| Inactivar o reactivar arreglos | ✓ | |
| Registrar, editar e inactivar clientes | ✓ | ✓ |
| Registrar solicitudes de pedido de arreglo (y asignarlas a cualquiera desde el formulario) | ✓ | ✓ |
| Asignar o reasignar solicitudes a cualquiera | ✓ | |
| Asignar una solicitud sin asignar, o pasarle la suya a otra persona | ✓ | ✓ |
| Asignarse una solicitud libre | ✓ | ✓ |
| Iniciar y terminar solicitudes | ✓ (todas) | ✓ (solo las suyas) |
| Cancelar o editar solicitudes | ✓ | |
| Zonas de entrega y precios del express | ✓ | |
| Temporada alta: precios del proveedor, encenderla y apagarla | ✓ | |
| Ventas y ganancias | ✓ | |
| Cuentas por cobrar y por pagar (abonos) | ✓ | |
| Gastos del negocio y reportes (balance, trazabilidad) | ✓ | |
| Proveedores, inventario, pedidos y empleados | ✓ | |

## Módulos

### Inicio

Resumen según el rol:

- **Propietario:** solicitudes abiertas y atrasadas, insumos con poco disponible (y cuántos faltan para las solicitudes abiertas) y pedidos sin recibir.
- **Empleado:** sus solicitudes y las que puede tomar.
- **Encargos para hoy:** al tocarlo abre Encargos con la fecha de entrega en **Hoy** y solo los abiertos.

### Proveedores

- **Obligatorios:** nombre de la empresa, nombre del contacto, teléfono, provincia, cantón y distrito.
- **Nombre de la empresa:** máximo 100 caracteres, al menos una letra y no puede repetirse.
- **Nombre del contacto:** máximo 100 caracteres, solo letras y espacios.
- **Teléfono:**
  - Nacional: exactamente 8 dígitos, con formato `0000-0000`.
  - Internacional: código de país más 6 a 12 dígitos.
  - No puede repetirse.
- **Correo:** opcional; si se ingresa, debe ser válido y no repetirse.
- **WhatsApp:** en la lista y en **Ver**, el teléfono se puede tocar para escribirle por WhatsApp (los internacionales usan su código de país).
- **Ubicación:** provincia, cantón y distrito deben corresponder entre sí, según `BaseDatos/DatosIniciales/ubicaciones.txt`.

### Inventario de insumos

Los **insumos** son lo que se le compra a los proveedores para armar los arreglos o agregarlos como adicionales (flores, papel, cintas, bases, chocolates…). Los arreglos que se venden están en el Catálogo. Cada insumo tiene los datos generales en `tbinventario` y sus atributos en la tabla de su categoría (`tbinventariocategoria<categoría>`):

| Categoría | Atributos |
|-----------|-----------|
| Flor | Flor o follaje, tipo de flor (Rosa, Eucalipto…), color, largo del tallo y días de duración |
| Envoltura | Papel o cinta, tipo, color, diseño, medida y material. El papel lleva acabado y la cinta indica si es personalizable |
| Base | Tipo, material, color, forma, medidas y diseño |
| Chocolate | Tipo, marca, presentación, peso, sabor y fecha de vencimiento (al menos 30 días después de hoy) |
| Peluche | Tipo, tamaño, color, diseño y material |
| Tarjeta | Tipo, temática, color, material, medidas, si es personalizable y elaboración (hecha en el negocio o comprada) |
| Spray | Tipo (pintura floral, abrillantador de follaje o aromatizante floral), marca, contenido (ml) y fecha de vencimiento (opcional; si se pone, al menos 30 días después de hoy). La pintura lleva color y **cobro por flor pintada**; el aromatizante, aroma |
| Accesorio | Tipo (corona, perfume, splash, moño, topper u otro), tamaño y, si se quiere, color y material. Se puede poner en un arreglo o agregar como adicional |

- **Listas:** las opciones de cada lista están en `inventariocontrolador::opciones()`. La pantalla y la validación usan las mismas.
- **Colores y tipos de flor (tablas catálogo):** el color de todos los insumos sale de `tbcolor` y el tipo de flor de `tbtipoflor` (con su clase, Flor o Follaje). En las tablas de categoría se guarda el id (`tbcolorid`, `tbtipoflorid`), así que ya no se puede escribir un color inventado como "carro". El tipo de flor tiene que ser de la misma clase que se eligió (Eucalipto es follaje). El propietario agrega colores y tipos, o los inactiva, en **Inventario → Colores y tipos de flor**; uno inactivo ya no se puede elegir en insumos nuevos, pero los que ya lo tienen no cambian.
- **Medidas:** en centímetros, con formato `20` o `20 x 30`.
- **Nombre:** un insumo se registra **una sola vez**: no se puede repetir el nombre (sin importar mayúsculas, tildes ni el proveedor). La comparación se hace en PHP, así funciona igual con cualquier configuración de la base. Si otro proveedor también lo vende, se agrega en **Otros proveedores** del insumo que ya existe.
- **Nuevo insumo que ya existe:** apenas se escribe un nombre que ya está registrado (por ejemplo "rosa roja"), el formulario se llena solo con los datos de ese insumo, que quedan sin poder cambiarse, y pasa a **Agregar stock**: se elige **a qué proveedor se lo compró** (el principal o uno de Otros proveedores, cada uno con su precio del paquete) y **cuántos paquetes**; abajo dice cuántas unidades se suman, cómo queda el stock y cuánto costó la compra. Con **Es otro insumo** se vuelve al formulario en blanco.
- **Fecha de vencimiento (chocolates y sprays):** al menos 30 días después de hoy, para no comprar algo que está por vencer. Al editar, si la fecha no se cambia, no se vuelve a revisar.
- **Stock:** al registrar el insumo se escribe el **stock inicial**. Después ya no se escribe a mano (al editar solo se muestra):
  - Lo que llega con un pedido se suma solo al marcarlo **Recibido**.
  - Lo que se compra sin pedido se suma con **Stock** (en la lista) o **Agregar stock** (en Ver y en Editar), o escribiendo el nombre en Nuevo insumo: proveedor y paquetes, y se suma a lo que ya hay. Lo que se pagó (paquetes × precio del paquete de ese proveedor) queda solo como gasto de hoy en "Compras a proveedores".
  - Lo que sale en los arreglos se rebaja solo al terminar la solicitud.
  - Las flores que **se dañaron o se marchitaron** se rebajan aparte, con **Rebajar dañadas** en Ver. No se puede rebajar más de lo que hay, y la ventana avisa si deja de alcanzar para lo apartado.
  - Cada cambio queda en los movimientos con su motivo, el proveedor de la compra y quién lo hizo.
- **Varios proveedores:** cada insumo tiene un **proveedor principal** (`tbinventario.tbproveedorid`), y su precio es el costo del insumo. En **Otros proveedores** se agregan los demás a los que se les puede comprar el mismo paquete, cada uno con su precio (tabla `tbinventariootroproveedor`). El formulario muestra a cuánto sale la unidad con cada uno y si es más barato o más caro que el principal; en la lista dice "y 2 proveedores más", y en **Ver** salen todos con la comparación. No se puede repetir un proveedor, ni poner el principal, ni uno inactivo.
- **Precios:**
  - El precio del paquete debe ser mayor que 0, con máximo 2 decimales.
  - El costo por unidad se calcula solo: precio del paquete ÷ unidades por paquete.
  - El **precio de venta** por unidad es el que se le cobra al cliente en un arreglo personalizado o como adicional. No puede ser menor que el costo, y la pantalla muestra la ganancia.
- **Stock apartado (previsibilidad):** cada insumo guarda cuánto está apartado para las solicitudes abiertas (`tbinventariostockreservado`). Lo **disponible** es el stock menos lo apartado. En la lista, debajo del stock dice "8 apartadas · 32 libres", o "Faltan 9" si las solicitudes abiertas piden más de lo que hay; en **Ver** salen Stock actual, Apartado para solicitudes y Disponible.
- **Foto del producto:** en **Ver** está **Agregar foto** (o **Cambiar foto** y **Quitar foto**). Es la foto que sale en la **proforma** que se le manda al cliente cuando lo agrega como adicional (por ejemplo la caja de chocolates o la tarjeta). JPG, PNG o WEBP de hasta 2 MB, en `Publico/imagenes/productos/` (columna `tbinventariofoto`). Los productos sin foto salen en la proforma con un cuadro que dice "Sin foto".
- **Ver:** la foto del producto y los datos por secciones: estado, **Stock** (actual, apartado, disponible y mínimo), **Proveedores y precios** (el principal y los otros con la comparación, costo, venta, ganancia y temporada alta) y los datos de la categoría, y debajo los movimientos del stock.
- **Nivel de stock**, con un círculo de color (según lo disponible):
  - Rojo: igual o por debajo del mínimo.
  - Amarillo: hasta 1,5 veces el mínimo.
  - Verde: suficiente.

### Pedidos (compras a proveedores)

- **Proveedor:** solo se puede pedir a proveedores activos, y solo insumos que se le compran a ese proveedor: los que lo tienen como principal y los que lo tienen en **Otros proveedores**. Cada insumo sale con el precio de ese proveedor.
- **Fecha:** no puede ser posterior a hoy.
- **Cantidades:** se piden en paquetes, de 1 a 9999.
- **Estados:** Pendiente → Confirmado → Recibido (o Cancelado).
  - Solo un pedido pendiente se puede editar, confirmar o **cancelar**.
  - Cancelar es para cuando el proveedor no confirma el pedido: queda "Cancelado", no se suma nada
    al inventario y el historial dice quién lo canceló.
- **Confirmar:** se elige el pago, de contado o a crédito. A crédito el plazo es de 1 a 365 días. De contado, la compra queda sola como gasto de ese día; a crédito, cada abono queda como gasto del día en que se paga (ver Gastos del negocio).
- **Recibir:** suma al stock las unidades de cada paquete.

### Catálogo de arreglos

- **Foto:** obligatoria; JPG, PNG o WEBP de máximo 2 MB.
- **Nombre:** no puede repetirse.
- **Precio:** mayor que 0.
- **Productos:** cada arreglo lleva al menos un producto del inventario, con su cantidad en unidades (tabla `tbarreglofloralinsumo`). Se eligen en la ventana **Agregar productos**, con buscador, filtro por categoría y las unidades que hay en inventario.
- **Contra el inventario:** no se puede poner más unidades de un producto de las que hay en inventario (al editar, solo se revisa lo que se aumentó).
- **Precio sugerido:** se elige el % de ganancia (100% por defecto, con − y +, o 50%, 80%, 100% y 150%) y el precio de venta se llena solo con el costo de los productos más ese %, redondeado hacia arriba a ₡100. Se puede escribir otro precio; al tocar un % vuelve al sugerido. Al editar, el % que se muestra es el que da el precio que ya tiene el arreglo.
- **Empleados:** también crean y editan arreglos, con el costo de los productos para ponerles precio. Si ponen un precio que no paga lo que cuestan los productos, no se guarda. Inactivar o reactivar es solo del propietario.
- **Dos precios:** cada tarjeta muestra el precio **normal** y el de **temporada alta**; el que se cobra ahora va resaltado.
- **Ver:** foto, los dos precios y lo que incluye. El costo y la ganancia (normal y en temporada alta) están en **Costo y ganancia**, que solo ve el propietario y se abre con un clic.
- **Costo y ganancia:** se calculan con el precio por unidad de cada producto. Solo el propietario los ve.
- **Exportar catálogo:** vista previa para el cliente con los arreglos y regalos disponibles (foto completa, nombre, descripción y precio, 3 por fila). Se puede imprimir, compartir o **descargar en PDF**; las fotos van en alta resolución.

### Búsqueda por foto (algoritmo)

Cuando la clienta manda por WhatsApp la foto de un arreglo que le gustó, en **Catálogo → Buscar por foto** se sube esa foto y el sistema muestra los 6 arreglos del catálogo más parecidos, con su % de parecido, su precio y el botón **Crear solicitud** (abre una solicitud nueva con ese arreglo ya elegido). Lo pueden usar el propietario y las empleadas.

El algoritmo está en `Aplicacion/Controlador/huellafoto.php` y usa la extensión GD de PHP (en Ubuntu se instala con `sudo apt install php-gd`). No usa servicios externos ni se paga nada.

1. **Huella de la foto.** La foto se achica a 48 × 48 píxeles y se le saca:
   - **Colores de toda la foto:** cada píxel se pasa a tono, saturación y brillo, y se cuenta en uno de 16 grupos (rojo, naranja, amarillo, verde lima, verde, verde agua, celeste, azul, azul oscuro, morado, fucsia, rosado, blanco, gris, negro y café). Los píxeles del centro cuentan más (campana de Gauss), porque ahí suele estar el arreglo, y el blanco, gris, negro y café cuentan menos, porque casi siempre son el fondo.
   - **Tonos cálidos con cuidado:** el naranja y el amarillo solo cuentan como flor si el color es **vivo** (saturado y claro), como una gerbera o un girasol. Si es apagado, es **café** (papel kraft, la piel de la mano, madera, pared beige); si es oscuro, es follaje en sombra (**verde**); y si es muy pálido, es **blanco crema** (rosas blancas). Del mismo modo, un rojo muy saturado es **rojo** (rosa roja) y uno menos saturado es **rosado** (lirio o rosa rosada).
   - **Colores por zonas:** lo mismo en cada cuarto de la foto, para que importe dónde está cada color.
   - **Forma:** la foto en blanco y negro de 9 × 8 píxeles; cada uno de los 64 bits dice si un píxel es más claro que el de su derecha.
2. **Parecido** (0 a 100%) = 50% colores en común + 30% colores en común por zonas + 20% bits iguales de la forma. "Colores en común" es la suma, grupo por grupo, del menor de los dos valores.
3. **Resultado:** se compara con la huella de cada arreglo activo y se ordenan del más parecido al menos parecido. 75% o más es "Muy parecido"; 65% o más, "Parecido".

Las huellas del catálogo se guardan en `tbarreglofloralhuella`. Se calculan solas la primera vez que se busca (tarda unos segundos) y otra vez solo si cambia la foto del arreglo. La foto de la clienta se achica en el navegador a 800 píxeles antes de subirla y no se guarda.

**Limitación:** compara colores y forma, no "entiende" qué flores son: un ramo de rosas rojas se parece a otro ramo rojo, aunque sea de otras flores.

**Versión del algoritmo:** cada huella guarda su versión (`huellafoto::VERSION`). Si el algoritmo cambia, las huellas del catálogo se calculan de nuevo solas en la siguiente búsqueda.

**Lupa:** en los resultados, en las propuestas del asistente y en las fotos de las solicitudes, al pasar el mouse por una foto se ve más grande junto al puntero, y con un clic (o tocándola en el celular) se abre en toda la pantalla (`Publico/js/lupa.js`; sirve para cualquier `<img data-lupa>`).

### Valorar un ramo por foto (algoritmo)

Si ningún arreglo del catálogo se parece a la foto de la clienta, en el mismo **Buscar por foto** aparece **Valorar este ramo** (arriba y resaltado si el más parecido tiene menos de 75%; si no, al final). Calcula cuánto costaría hacer ese ramo con lo que hay en el inventario, con el % de ganancia, y **Crear solicitud con esta valoración** abre una solicitud personalizada (libre) con esos productos y la foto de la clienta como foto de referencia. Lo pueden usar el propietario y las empleadas; el costo y la ganancia solo los ve el propietario.

El sistema **no reconoce ni cuenta las flores**: la empleada elige el tamaño (**Pequeño** 6 flores, **Mediano** 12, **Grande** 24) o escribe cuántas ve (1 a 100), y el algoritmo (`Aplicacion/Controlador/valoracioncontrolador.php`) hace lo demás con la misma huella de colores de la búsqueda:

1. **Quitar el fondo:** con un **relleno por inundación** (búsqueda en anchura, BFS) se empieza en la orilla de la foto y se avanza a los píxeles vecinos mientras sean blancos, grises o cafés y **lisos** (casi sin diferencia de brillo con los de alrededor). Así se marca la pared, la mesa o el piso. Las flores blancas no son lisas (los pétalos tienen sombras y bordes), así que el relleno se detiene en ellas y no se confunden con la pared.
2. **Colores de flores:** de lo que no es fondo, con más peso en el centro, se toman los grupos de color de flores (rojo, naranja, amarillo, celeste, azul, morado, fucsia, rosado y blanco). El café (kraft, piel), los verdes, el gris y el negro no cuentan. Un color se toma si es al menos 12% de los colores de flores **y** al menos la cuarta parte del color principal, máximo 4: así los centros amarillos de las margaritas o un detalle pequeño no se vuelven otra flor.
3. **Flores de cada color:** la flor activa de ese color (`tbinventariocategoriaflor`) que más se usa en las recetas del catálogo y que tiene stock.
4. **Cantidades:** las flores se reparten según cuánto ocupa cada color en la foto (cada color lleva al menos 1). Si de un color van 6 o más, se reparten entre 2 flores de ese color (3 desde 12 flores), en proporción 3 : 2 : 1, pero **solo con flores que van juntas en alguna receta del catálogo**: un ramo blanco queda con áster, margaritas y claveles blancos, y uno de rosas rojas queda solo con rosas rojas (en el catálogo nunca van con anturios). Si una flor no tiene suficiente stock, se completa con otra del mismo color; si aun así no alcanza, se avisa cuántas faltan.
5. **Follaje:** 1 por cada 3 flores si la foto tiene verde (5% o más); si no, 1 por cada 6.
6. **Papel:** 1 pliego por cada 8 flores (de 1 a 4); negro si la foto tiene 10% de negro o más; si no, el que más se usa en el catálogo junto con la flor principal. **Cinta:** 1, la que más se usa con la flor principal.
7. **Precio:** cada producto a costo × (1 + % / 100), hacia arriba a ₡10, igual que en un personalizado.

**Qué tan bien acierta:** se midió con las 18 fotos del catálogo, comparando los colores que ve el algoritmo con los de la receta real de cada arreglo, más la foto de un ramo blanco de una clienta. El color principal acierta en **13 de 19** (antes de la versión 2, en 4 de 19: casi todo salía "naranja" por el papel kraft y la mano). Los que fallan son fotos donde el blanco es papel, una cinta o el cielo, o donde la receta no coincide con la foto. El girasol (amarillo dorado) y la gerbera naranja tienen casi el mismo tono, así que a veces se confunden: se corrige en la solicitud.

La foto de la clienta se guarda en `Publico/imagenes/solicitudes/referencia_…` para usarla como foto de referencia y para volver a calcular sin subirla otra vez al cambiar el tamaño o el %. Todo se puede corregir después en la solicitud.

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

### Solicitudes de pedido de arreglo

- **Datos de toda solicitud:** cliente, tipo, fecha de entrega (no anterior a hoy y como máximo dentro de 6 meses), nota opcional y, si se quiere, a quién se asigna.
- **Predefinida:** un arreglo del catálogo (el que el cliente eligió del catálogo de WhatsApp) por la cantidad (1 a 99), más **adicionales** opcionales: chocolates, cintas, peluches, tarjetas u otros productos, cada uno a su precio de venta.
- **Personalizada:** el cliente elige los productos. Cada producto se cobra a **su costo + el % de ganancia** que se elige (100% por defecto, es decir, el doble del costo), redondeado hacia arriba a ₡10. El % se cambia con − y +, con los botones 50%, 80%, 100% y 150%, o escribiéndolo (de 0 a 500), y se guarda en la solicitud.
  - **Con presupuesto:** los productos y las flores pintadas no pueden pasarse del monto que dio el cliente. Los precios ya llevan el % de ganancia: debajo de cada uno se ve "costo ₡294 + 80%" (el costo solo lo ve el propietario). La pantalla muestra cuánto queda, desactiva los productos que ya no alcanzan y no deja pintar más flores de las que alcanzan.
  - **Libre:** se eligen los productos y el sistema calcula el total para cotizarlo. **Sugerir arreglos** también funciona aquí (ver Asistente).
- **Servicio express:** se elige la zona y se cobra su precio. La dirección de entrega es obligatoria.
- **Flores pintadas (opcional):** se elige la pintura floral (spray) y cuántas flores se pintan; se cobra el "cobro por flor" de esa pintura por cada una. El campo no deja escribir más flores de las que lleva el arreglo y el botón + se apaga al llegar al tope. El spray no se agrega como producto ni va dentro de los arreglos del catálogo.
- **Total:** arreglo × cantidad + productos + flores pintadas + express. Debajo se ven los dos totales, con precios normales y en temporada alta, y (solo el propietario) el costo y la ganancia del personalizado. El botón **Ver proforma para el cliente** arma la proforma (ver abajo).
- **Proforma para el cliente:** reemplaza al texto que se copiaba y se pegaba en WhatsApp. Se abre desde el formulario con **Ver proforma para el cliente** (también antes de guardar la solicitud) y sale como una hoja con el logo, el cliente, la fecha y forma de entrega, y un renglón por cada cosa con **su foto**, cantidad, precio y subtotal, y al final el total:
  - **Predefinida:** la foto del arreglo del catálogo con lo que lleva, y debajo cada adicional con la foto del producto (por ejemplo 1 chocolate y 1 tarjeta, cada uno con su foto).
  - **Personalizada:** el ramo con la foto de referencia y lo que lleva, con un solo precio (no se dice cuánto cuesta cada flor); los chocolates, peluches y tarjetas van aparte con su foto.
  - Las flores pintadas y el servicio express van en su propio renglón; la nota de la solicitud sale como **Indicaciones**.
  - **Descargar PDF** genera el PDF y lo descarga de una vez, ya con el nombre ("Proforma Ana Mora 17-11-2026.pdf"), en hojas tamaño carta; si no cabe en una hoja, se corta entre renglones. Sale igual desde la computadora o el celular. Se hace en el navegador con dos librerías de licencia MIT que vienen en `Publico/js/librerias/` (no necesitan internet): **html2canvas** toma la hoja como imagen, con las fotos y el ₡, y **jsPDF** arma el PDF.
  - **Imprimir** la manda a la impresora (solo sale la hoja). **Abrir chat de WhatsApp** abre la conversación con el cliente para mandarle el PDF.
- **Costo:** cada solicitud guarda lo que costaron sus flores y productos (`tbsolicitudencargocosto`), para calcular la ganancia en Ventas.
- **Precios guardados:** se guardan los precios del momento. Si después cambian en el catálogo, el inventario o las zonas, las solicitudes ya registradas no cambian, ni siquiera al editarlas.
- **Stock reservado (previsibilidad):** al guardar, el sistema revisa que haya inventario **disponible** (stock − lo apartado para otras solicitudes abiertas) para todo lo que lleva: la receta del arreglo × cantidad y los adicionales, o los productos del personalizado. Si no alcanza, no deja guardarla y dice, por ejemplo, "Girasol: se necesitan 15 y hay 5 disponibles (12 en inventario, 7 apartadas para otras solicitudes)". Al guardarla, eso queda apartado; al cancelarla vuelve a estar disponible, y al terminarla sale del stock. Al editar, lo que la misma solicitud ya tenía apartado cuenta como suyo, y si no se aumenta no se vuelve a revisar. En el formulario, los productos sin disponible dicen "(no hay disponible)". El inicio del propietario avisa los productos con poco disponible y cuántos faltan para preparar todas las solicitudes abiertas.
- **Estados:** Pendiente → En proceso → Terminado; también puede pasar a Cancelado.
  - Para iniciarla, debe estar asignada a alguien.
  - **Terminar:** descuenta del inventario los productos del arreglo × cantidad y los adicionales, o los productos del personalizado. Si no alcanza el stock, no deja terminarla y dice qué falta.
- **Listado:** se filtra por estado, tipo y persona asignada. Las atrasadas se marcan en rojo, y "Falta stock" avisa cuando el inventario no alcanza.
- **Foto de referencia (personalizados):** lo que quiere la clienta. Al **Usar esta propuesta** del asistente queda la foto del arreglo del catálogo; al crear la solicitud desde **Valorar este ramo**, la foto que mandó la clienta. Se ve en el formulario (con **Quitar foto de referencia**), en Ver y en la lista (si todavía no hay foto de cómo quedó), y se agranda con la lupa. Es aparte de la foto de cómo quedó el arreglo (`tbsolicitudencargoreferencia`).
- **Foto del personalizado:** en **Ver** está el botón **Agregar foto** (o **Cambiar foto**) para guardar cómo quedó el arreglo. Se puede en cualquier estado menos Cancelado, la sube el propietario o quien tiene asignada la solicitud, y se ve en la lista y en Ver. JPG, PNG o WEBP de hasta 2 MB, en `Publico/imagenes/solicitudes/`.

### Asistente de presupuesto (algoritmo)

En una solicitud **personalizada con presupuesto**, el botón **Sugerir arreglos** propone hasta 3 arreglos que caben en el presupuesto con el % de ganancia elegido. Si ya se eligieron productos (por ejemplo 5 rosas rojas), se mantienen. Cada propuesta muestra sus productos, el total, la ganancia (solo el propietario) y lo que sobra; con **Usar esta propuesta** se llena la tabla de productos y se puede ajustar antes de guardar.

El algoritmo está en `Aplicacion/Controlador/asistentecontrolador.php`. Cada arreglo del catálogo es una **receta**: qué flores, papel y demás lleva y en qué proporción.

1. Del presupuesto se apartan los productos que ya eligió la clienta y lo que cuestan las flores pintadas.
2. Para cada receta se ponen el papel, la base y la cinta (lo que no depende del tamaño) y los adicionales de la receta (tarjeta, chocolate…); si no alcanza, se quitan los adicionales.
3. Las flores y follajes de la receta se agrandan o se achican en la misma proporción, de 5% en 5% y hasta 3 veces la receta (6 veces si la receta lleva la flor que pidió la clienta, para que un arreglo pequeño con girasol también llene un presupuesto grande), hasta llenar lo que queda sin pasar del stock. Si la clienta pidió una flor que está en la receta, esas cuentan dentro de la proporción y nunca se ponen menos de las que pidió.
4. Lo que sobra se completa de a una flor, la que más le falte para mantener la proporción.
5. Cada producto se cobra igual que en el personalizado: costo × (1 + % / 100), hacia arriba a ₡10. Si la temporada alta está encendida, con sus costos.
6. **Orden:** primero las recetas que llevan las flores que pidió la clienta (si pidió girasoles, primero los arreglos del catálogo con girasol). Entre ellas: 60% qué tanto aprovecha el presupuesto + 25% si la receta lleva algo de lo que pidió + 15% qué tan cerca queda del tamaño original. No se repiten dos propuestas con las mismas flores.

Si sobra dinero, la propuesta lo dice, avisa si fue porque no hay más de alguna flor en inventario y sugiere adicionales que caben (peluche, chocolates, corona…).

**Personalizado libre (sin presupuesto):** el mismo botón (**Asistente de arreglos**) propone hasta 3 arreglos para cotizar:

1. Cada receta va **de su tamaño original**, con lo que hay en stock: si de una flor no alcanza, van las que hay y la propuesta lo avisa. Las flores que eligió la clienta se mantienen (si pidió más que la receta, van las que pidió). El papel, la base, la cinta y los adicionales de la receta van si hay stock.
2. Cada producto se cobra igual que en el personalizado: costo × (1 + % / 100), hacia arriba a ₡10.
3. **Orden:** 70% cuántos de los tipos de flor que eligió la clienta lleva la receta + 30% qué parte de la receta alcanzó el stock. No se repiten dos propuestas con las mismas flores.

### Temporada alta

- **Qué es:** las fechas en que suben las flores (San Valentín, Día de la Madre…). Hay una sola, "Temporada alta", y se enciende y se apaga a mano.
- **Precios:** lo único que se escribe es **lo que cobra el proveedor por el paquete** en temporada alta (tabla `tbinventariotemporada`). Se puede hacer en la pantalla **Temporada alta** (todas las flores en una tabla) o en el formulario del producto. Lo demás se calcula solo, **con la misma ganancia en colones**:
  - **Precio de venta del producto:** el normal + lo que subió su costo por unidad. Por ejemplo, si el paquete de 24 rosas pasa de ₡7 000 a ₡14 000, la rosa cuesta ₡291,66 más y pasa de ₡600 a ₡900.
  - **Precio de los arreglos:** el normal + lo que subió el costo de todo lo que lleva. Por ejemplo, el Ramo Delicado cuesta ₡3 550 normal y ₡5 620 en temporada alta, así que pasa de ₡12 000 a ₡14 100.
  - Los precios calculados se redondean hacia arriba a ₡100.
  - Si un producto no tiene precio del proveedor en temporada alta, se usa el normal.
- **Encender:** en **Temporada alta → Encender temporada alta**. Mientras esté encendida, el catálogo, el inventario y las solicitudes nuevas usan sus precios, y en la barra de arriba aparece "Precios de Temporada alta". **Apagar y volver a precios normales** regresa a la temporada baja.
- **Aviso flotante:** si está encendida y hay flores o follajes sin el precio del proveedor, el inventario lo avisa. Con **Agregar precios** se ponen todos desde una sola ventana.
- **Solicitudes:** guardan si se cotizaron en temporada alta (`tbtemporadaid`) y sus precios no cambian después.

### Ventas y ganancias

Muestra lo que entró y lo que salió de la caja en un período (hoy, esta semana, este mes, el mes pasado, este año, todo o las fechas que se elijan). Solo el propietario la ve.

- **Vendido:** los encargos **terminados**, el día en que se marcaron como terminados, con cuántos arreglos y cuánto fue de express.
- **Gastos:** lo que se pagó ese día: los gastos registrados en **Gastos** (luz, alquiler, salarios…) y las **compras a proveedores**, que se registran solas (ver Gastos del negocio). Ya no se resta "lo que costaron las flores de lo que se vendió": las flores se cuentan el día en que se le pagan al proveedor, así no se cuentan dos veces.
- **Balance = vendido − gastos**, en verde si quedó dinero o en rojo si se gastó más de lo que se vendió.
- **Ver el balance por día, semana o mes:** una tabla con cada día (o cada semana de lunes a domingo, o cada mes) que tuvo ventas o gastos: cuántas ventas, cuánto se vendió, cuánto se gastó y el balance, con el total abajo. **Ver detalle** de una semana o un mes muestra esos días uno por uno.
- **Lo que se vendió:** cada venta con la fecha, el cliente, qué se vendió y el total, 7 por página. El detalle de cada gasto está en **Gastos**.

### Cuentas por cobrar y por pagar

En **Finanzas → Cuentas por cobrar y por pagar** (solo el propietario), con dos pestañas:

- **Por cobrar (clientas):** cada solicitud que no está cancelada tiene un saldo: total − lo que ha abonado la clienta (`tbsolicitudencargoabono`). Si dejó un adelanto dice "Con adelanto"; si se entregó sin pagar completa es una **venta a crédito** ("Entregada sin pagar") y sale de primera. En **Ver** de la solicitud dice cuánto ha pagado y cuánto falta. Si ya pagó, la solicitud no se puede editar para que el total quede por debajo de lo pagado.
- **Por pagar (proveedores):** los pedidos que se confirmaron **a crédito**. Saldo = monto de la compra − abonos (`tbpedidoabono`). Vence en la fecha de confirmación + el plazo; dice "Por vencer" en los últimos 7 días y "Vencido" después. Los de contado se pagan al confirmar y no aparecen.
- **Tarjetas:** lo que deben las clientas por arreglos entregados, lo que falta de las solicitudes abiertas y lo que se les debe a los proveedores (en rojo si hay algo vencido).
- **Abonar:** monto (no más que el saldo; el botón "Todo el saldo" lo llena), fecha (no futura ni anterior a la compra o a la solicitud), cómo se pagó (efectivo, SINPE Móvil, transferencia o tarjeta) y una nota opcional. Cada abono guarda quién lo registró. Con "Mostrar también las que ya están pagadas" se ven las cuentas canceladas por completo.
- **Anular un abono:** si un abono se registró mal (monto equivocado, en la cuenta equivocada o dos veces), en la ventana de la cuenta se toca **Anular** y se escribe el motivo (5 a 100 caracteres). No se borra: queda en la lista tachado, con el motivo, quién lo anuló y cuándo; deja de sumar y el saldo vuelve a subir. Si el monto estaba mal, se anula y se registra el abono correcto. Un abono anulado no se puede volver a anular, y la trazabilidad de la solicitud o del pedido muestra la anulación con su responsable. Con esto las cuentas por cobrar y por pagar tienen su CRUD: registrar, consultar, corregir (anular y registrar de nuevo) e historial.

### Gastos del negocio

En **Finanzas → Gastos** (solo el propietario). Es todo lo que sale de la caja (tabla `tbgasto`): alquiler, electricidad, agua, internet y teléfono, salarios, transporte, mantenimiento, publicidad u otros, y las **compras a proveedores**.

- **Compras a proveedores (se registran solas):** no hay que registrarlas a mano. Quedan en la categoría "Compras a proveedores" con la etiqueta **Automático**, y `tbgastoorigen` y `tbgastoreferencia` dicen de dónde salieron:
  - **Pedido de contado:** al confirmarlo se registra el monto de la compra como gasto de ese día (origen `Pedido`, con el id del pedido).
  - **Pedido a crédito:** al confirmarlo no se registra nada; **cada abono** al proveedor es el gasto del día en que se paga (origen `Abono`, con el id del abono). Si el abono se anula, su gasto también queda anulado.
  - **Compra sin pedido:** en **Inventario → Agregar stock**, los paquetes × el precio del paquete de ese proveedor quedan como gasto de hoy (origen `Compra`, con el id del movimiento de inventario).
  - Estos gastos no se editan ni se anulan desde Gastos (se corrigen desde el pedido o el abono), y la categoría "Compras a proveedores" no se puede elegir al registrar un gasto a mano.

- **Registrar y editar:** categoría (de la lista), descripción (3 a 100 caracteres, con letras), monto (mayor que 0, máximo 2 decimales) y fecha del pago (no futura ni de hace más de 2 años). No deja registrar dos veces el mismo gasto (misma categoría, descripción, monto y fecha).
- **Anular:** un gasto mal registrado no se borra: se anula, deja de sumar y queda en el historial. Se puede "volver a contar" si no hay otro igual.
- **Lista:** por período (este mes, mes pasado, este año, todo o fechas), categoría y estado, con el total y una barra por categoría.

### Reportes

En **Finanzas → Reportes** (solo el propietario), con dos pestañas y un botón **Descargar PDF** (descarga la pestaña que se está viendo):

- **Balance:** para el período elegido, vendido (encargos terminados) − gastos (con el detalle por categoría, incluidas las compras a proveedores) = **balance**, en verde si es positivo o en rojo si hubo pérdida, con el % de lo vendido. Es la misma cuenta de **Ventas y ganancias**. Al lado, una tabla con los últimos 6 meses para comparar.
- **Trazabilidad:** se elige una solicitud de arreglo o un pedido al proveedor y se ve todo lo que pasó, en orden, con fecha, hora y **responsable**: registro, asignación, cuándo empezó a prepararse, cuándo se terminó (y qué salió del inventario), cancelación, cada pago de la clienta; en los pedidos, cuándo se pidió, se confirmó (contado o crédito), se recibió (qué entró al inventario) y cada abono al proveedor. Si se anuló un abono, aparece cuándo, quién y por qué. Arriba dice cuánto tardó de principio a fin. El enlace se puede compartir (`index.php?modulo=reporte&ver=trazabilidad&tipo=solicitud&id=17`).

### Empleados

- **Nombre:** solo letras.
- **Teléfono:** 8 dígitos, no se repite.
- **Usuario:** de 4 a 30 caracteres (letras sin tildes, números, punto o guion bajo); no se repite.
- **Contraseña:** de 8 a 72 caracteres, con al menos una letra y un número. Al editar, se puede dejar vacía para conservar la anterior.
- **Propietario:** solo puede haber un propietario activo. El propietario no puede inactivarse a sí mismo ni quitarse el rol.

## Borradores y confirmaciones

- Si se cierra un formulario sin guardar, los datos quedan en el navegador. Al abrirlo de nuevo aparece "Se recuperaron datos que no se habían guardado", con la opción **Descartar**.
- Editar, inactivar, cancelar o terminar algo siempre pide confirmación.
- Todas las ventanas tienen una **X** arriba a la derecha que hace lo mismo que su botón Cancelar o Cerrar.
- **Botones de las tablas:** Ver, Historial y Asignar en gris; Editar, Iniciar y Terminar con el color de la floristería; Inactivar y Cancelar en rojo; Reactivar en verde.
- El botón principal de cada pantalla (Nuevo producto, Nueva solicitud…) va siempre a la derecha del encabezado.

## Históricos

Todo se registra en PHP. La base de datos solo guarda la información.

La ventana **Historial** muestra en una sola fila los datos con que se registró, y después cada cambio como "antes → ahora", con la fecha y quién lo hizo. Arriba hay un filtro para ver solo los cambios de un dato (por ejemplo, Teléfono o Estado). En el celular, cada cambio se ve como una tarjeta.

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
| Inventario | Nombre, proveedor, otros proveedores (cuándo se agregó o quitó cada uno y su precio), precio del paquete, precio de venta, precio del proveedor en cada temporada, stock mínimo, estado y foto (cuándo se agregó, cambió o quitó) |
| Catálogo | Nombre, precio y estado |
| Empleados | Nombre, teléfono, rol, usuario, contraseña (solo "Asignada" o "Cambiada") y estado |
| Clientes | Nombre, teléfono, dirección y estado |
| Zonas de entrega | Nombre, precio y estado |
| Gastos | Categoría, descripción, monto, fecha y estado (cuenta o anulado) |
| Abonos (por cobrar y por pagar) | Cuándo y quién anuló cada abono (`tbsolicitudencargoabonoestadohistorico`, `tbpedidoabonoestadohistorico`); el motivo queda en el abono |
| Temporada alta | Cuándo se encendió y se apagó |

### Solicitudes de pedido de arreglo

Se ven en **Ver** de cada solicitud, con fecha, hora y quién hizo el cambio:

| Dato | Tabla |
|------|-------|
| Estado (Pendiente, En proceso, Terminado, Cancelado) | `tbsolicitudencargoestadohistorico` |
| Empleado asignado | `tbsolicitudencargoempleadohistorico` |
| Fecha de entrega | `tbsolicitudencargofechaentregahistorico` |
| Total | `tbsolicitudencargototalhistorico` |
| Foto del arreglo personalizado | `tbsolicitudencargofotohistorico` |
| Foto de referencia (se puso, se cambió o se quitó) | `tbsolicitudencargoreferenciahistorico` |

La tabla `tbarreglofloralhuella` no tiene histórico: es una copia calculada de cada foto del catálogo para buscar más rápido.

### Movimientos de inventario

Cada cambio de stock queda en `tbinventariomovimiento`: el motivo, la cantidad (positiva si entra, negativa si sale), cómo quedó el stock y quién lo hizo. Se ven en **Ver** de cada producto.

| Tipo | Cuándo |
|------|--------|
| Inicial | Al registrar el producto |
| Entrada | Al recibir un pedido |
| Salida | Al terminar una solicitud de pedido de arreglo |
| Compra | Al agregar stock comprado sin pedido (guarda a qué proveedor, en `tbproveedorid`) |
| Dañado | Al rebajar las que se dañaron o se marchitaron |
| Ajuste | Datos viejos: cuando el stock se corregía editando el producto |

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
- **Las fotos de los arreglos no aparecen:** revise que existan los archivos en `Publico/imagenes/catalogo/`. Las fotos que se suben desde el sistema se guardan en `Publico/imagenes/arreglos/`, carpeta que no se sube a GitHub.