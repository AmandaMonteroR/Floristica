<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ingresar | Floristica</title>
    <link rel="icon" type="image/png" href="imagenes/logo.png">
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body class="pagina-login">

<main class="tarjeta-login">
    <div class="marca-login">
        <img class="logo-login" src="imagenes/logo.png" alt="">
        <span>Floristica</span>
    </div>

    <?php if ($primeravez): ?>
        <h1>Bienvenida</h1>
        <p class="nota">Es la primera vez que se usa el sistema. Cree la cuenta de la dueña de la floristería; después ella podrá registrar a sus empleados.</p>

        <form id="formulario" data-accion="registrarduena" novalidate>
            <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>

            <label class="campo">
                <span>Nombre completo *</span>
                <input type="text" name="tbempleadonombre" maxlength="100" autocomplete="name">
                <small class="error" data-error="tbempleadonombre"></small>
            </label>
            <label class="campo">
                <span>Teléfono *</span>
                <input type="tel" name="tbempleadotelefono" inputmode="numeric" maxlength="9" placeholder="8888-8888" autocomplete="tel">
                <small class="error" data-error="tbempleadotelefono"></small>
            </label>
            <label class="campo">
                <span>Usuario *</span>
                <input type="text" name="tbempleadousuario" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false">
                <small class="error" data-error="tbempleadousuario"></small>
            </label>
            <label class="campo">
                <span>Contraseña *</span>
                <input type="password" name="tbempleadoclave" maxlength="72" autocomplete="new-password">
                <small class="nota-campo">Mínimo 8 caracteres, con al menos una letra y un número.</small>
                <small class="error" data-error="tbempleadoclave"></small>
            </label>
            <label class="campo">
                <span>Repita la contraseña *</span>
                <input type="password" name="tbempleadoclaveconfirmar" maxlength="72" autocomplete="new-password">
                <small class="error" data-error="tbempleadoclaveconfirmar"></small>
                <small class="correcto" data-correcto="tbempleadoclaveconfirmar" aria-live="polite"></small>
            </label>

            <button type="submit" class="boton boton-principal boton-ancho" id="botonguardar">Crear cuenta e ingresar</button>
        </form>
    <?php else: ?>
        <h1>Ingresar</h1>
        <p class="nota">Use el usuario y la contraseña que le dio la dueña de la floristería.</p>

        <form id="formulario" data-accion="ingresar" novalidate>
            <p id="avisoformulario" class="aviso aviso-error" role="alert" hidden></p>

            <label class="campo">
                <span>Usuario</span>
                <input type="text" name="usuario" maxlength="30" autocomplete="username" autocapitalize="none" spellcheck="false" autofocus>
                <small class="error" data-error="usuario"></small>
            </label>
            <label class="campo">
                <span>Contraseña</span>
                <input type="password" name="clave" maxlength="72" autocomplete="current-password">
                <small class="error" data-error="clave"></small>
            </label>

            <button type="submit" class="boton boton-principal boton-ancho" id="botonguardar">Ingresar</button>
        </form>
    <?php endif; ?>
</main>

<script src="js/login.js"></script>
</body>
</html>