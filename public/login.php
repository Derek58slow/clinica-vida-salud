
<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión - Clínica Vida Salud</title>
    <style>
        * { box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            background: #eaf4f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login {
            background: white;
            padding: 35px;
            border-radius: 12px;
            width: 360px;
            max-width: 92%;
            box-shadow: 0 4px 20px #00000018;
        }

        h2 {
            color: #167b94;
            text-align: center;
        }

        p {
            text-align: center;
            color: #666;
        }

        label {
            display: block;
            margin-top: 18px;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 11px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #167b94;
            color: white;
            border: none;
            border-radius: 6px;
            margin-top: 24px;
            cursor: pointer;
        }

        button:hover {
            background: #105c70;
        }
    </style>
</head>
<body>
    <div class="login">
        <h2>Clínica Vida Salud</h2>
        <p>Inicio de sesión</p>

        <form action="procesar_login.php" method="POST">
            <label for="usuario">Usuario</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                required
            >

            <label for="contrasena">Contraseña</label>
            <input
                type="password"
                id="contrasena"
                name="contrasena"
                required
            >

            <button type="submit">
                Ingresar
            </button>
        </form>
    </div>
</body>
</html>
