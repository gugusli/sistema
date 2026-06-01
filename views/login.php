<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1F3864">
    <title>Ingresar — Sistema de Previas E.E.S.T. N°5</title>
    <link rel="stylesheet" href="/public/css/base.css">
    <style>
        body {
            background: linear-gradient(145deg, #0f2040 0%, #1F3864 50%, #1a4a8a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        .login-wrap {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1.5rem;
        }

        /* Logo + nombre escuela */
        .login-header {
            text-align: center;
            color: #fff;
        }
        .login-logo {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid rgba(255,255,255,.35);
            box-shadow: 0 4px 20px rgba(0,0,0,.4);
            margin: 0 auto 1rem;
            display: block;
        }
        .login-logo-fallback {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: rgba(255,255,255,.1);
            border: 3px solid rgba(255,255,255,.25);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 1rem;
        }
        .login-escuela {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: .03em;
            margin-bottom: .2rem;
        }
        .login-subtitulo {
            font-size: .85rem;
            color: #a8bde0;
        }

        /* Card */
        .login-card {
            background: #fff;
            border-radius: 18px;
            padding: 2rem 2rem 2.25rem;
            width: 100%;
            box-shadow: 0 16px 48px rgba(0,0,0,.35);
        }

        .login-title {
            color: var(--primario);
            font-size: 1.25rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1.5rem;
        }

        /* Toggle alumno/preceptora */
        .toggle-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .5rem;
            margin-bottom: 1.5rem;
            background: #f0f4fa;
            padding: .3rem;
            border-radius: 10px;
        }
        .toggle-btns button {
            padding: .6rem;
            border: none;
            border-radius: 8px;
            background: transparent;
            color: var(--texto-suave);
            font-weight: 600;
            cursor: pointer;
            font-size: .9rem;
            transition: background var(--transition), color var(--transition), box-shadow var(--transition);
        }
        .toggle-btns button.active {
            background: var(--primario);
            color: #fff;
            box-shadow: 0 2px 8px rgba(31,56,100,.3);
        }

        /* Inputs */
        .login-card .form-group label { color: var(--texto); }
        .login-card .form-group input {
            padding: .7rem .9rem;
            font-size: 1rem;
            border-radius: 8px;
        }

        /* Submit */
        .btn-submit {
            width: 100%;
            padding: .8rem;
            background: linear-gradient(135deg, var(--primario), var(--secundario));
            color: #fff;
            border: none;
            border-radius: 9px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity var(--transition), transform var(--transition);
            box-shadow: 0 4px 14px rgba(31,56,100,.3);
            margin-top: .5rem;
        }
        .btn-submit:hover { opacity: .92; transform: translateY(-1px); }
        .btn-submit:active { transform: translateY(0); }

        .hidden {
            display: none !important;
        }

        /* Error */
        .login-card .error-msg { margin-bottom: 1.1rem; }

        /* Footer login */
        .login-footer {
            color: rgba(255,255,255,.4);
            font-size: .75rem;
            text-align: center;
        }

        @media (max-width: 480px) {
            .login-card { padding: 1.5rem 1.25rem 1.75rem; border-radius: 14px; }
            .login-logo { width: 72px; height: 72px; }
        }
    </style>
</head>
<body>
<div class="login-wrap">

    <div class="login-header">
        <img src="/public/logo.png" alt="Logo E.E.S.T. N°5" class="login-logo"
             onerror="this.style.display='none'; document.getElementById('logo-fallback').style.display='flex'">
        <div class="login-logo-fallback" id="logo-fallback" style="display:none">🏫</div>
        <div class="login-escuela">E.E.S.T. N°5 — Berazategui</div>
        <div class="login-subtitulo">Informática · Programación · Construcciones</div>
    </div>

    <div class="login-card">
        <div class="login-title">Sistema de Previas</div>

        <?php if (!empty($error)): ?>
        <div class="error-msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <div class="toggle-btns">
            <button id="btn-alumno" class="active" onclick="setModo('alumno')" type="button">
                🎓 Soy alumno
            </button>
            <button id="btn-preceptora" onclick="setModo('preceptora')" type="button">
                👩‍💼 Soy preceptora
            </button>
        </div>

        <form method="POST" action="/login" id="form-login" autocomplete="on">
            <input type="hidden" name="modo" id="campo-modo" value="alumno">

            <div id="grupo-dni" class="form-group">
                <label for="dni">DNI</label>
                <input type="text" id="dni" name="dni"
                       placeholder="Ingresá tu DNI"
                       autocomplete="username"
                       inputmode="numeric"
                       pattern="[0-9]*">
            </div>

            <div id="grupo-usuario" class="form-group hidden">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario"
                       placeholder="Usuario"
                       autocomplete="username"
                       disabled>
            </div>

            <div id="grupo-password" class="form-group hidden">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password"
                       placeholder="Contraseña"
                       autocomplete="current-password"
                       disabled>
            </div>

            <button type="submit" class="btn-submit">Ingresar</button>
        </form>
    </div>

    <div class="login-footer">
        &copy; <?= date('Y') ?> E.E.S.T. N°5 Berazategui
    </div>
</div>

<script>
function setModo(modo) {
    document.getElementById('campo-modo').value = modo;
    document.getElementById('btn-alumno').classList.toggle('active', modo === 'alumno');
    document.getElementById('btn-preceptora').classList.toggle('active', modo === 'preceptora');

    const grupoDni      = document.getElementById('grupo-dni');
    const grupoUsuario  = document.getElementById('grupo-usuario');
    const grupoPassword = document.getElementById('grupo-password');
    const inputDni      = document.getElementById('dni');
    const inputUsuario  = document.getElementById('usuario');
    const inputPassword = document.getElementById('password');

    if (modo === 'alumno') {
        grupoDni.classList.remove('hidden');
        grupoUsuario.classList.add('hidden');
        grupoPassword.classList.add('hidden');
        inputDni.disabled      = false;
        inputUsuario.disabled  = true;
        inputPassword.disabled = true;
        setTimeout(() => inputDni.focus(), 50);
    } else {
        grupoDni.classList.add('hidden');
        grupoUsuario.classList.remove('hidden');
        grupoPassword.classList.remove('hidden');
        inputDni.disabled      = true;
        inputUsuario.disabled  = false;
        inputPassword.disabled = false;
        setTimeout(() => inputUsuario.focus(), 50);
    }
}
// Restaurar modo si hubo error
<?php if (!empty($error) && isset($_POST['modo'])): ?>
setModo('<?= htmlspecialchars($_POST['modo'], ENT_QUOTES, 'UTF-8') ?>');
<?php endif; ?>
</script>
</body>
</html>
