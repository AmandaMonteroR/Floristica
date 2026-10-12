<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sin permiso | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
<?php require __DIR__ . '/menu.php'; ?>

<main class="contenedor">
    <section class="sin-permiso">
        <div class="icono-confirmacion" aria-hidden="true">!</div>
        <h1>No tiene permiso para ver esta página</h1>
        <p>Esta sección solo está disponible para la dueña de la floristería.</p>
        <a class="boton boton-principal" href="index.php">Ir a mi página de inicio</a>
    </section>
</main>

<script src="js/menu.js"></script>
</body>
</html>