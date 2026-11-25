<?php
declare(strict_types=1);
require_once __DIR__ . '/components/asset.php';
require __DIR__ . '/config/page_guard.php';
// Quien ya tiene sesión pasa directamente a la aplicación.
page_guard_login_redirect();

$aviso = null;
if (isset($_GET['expirada'])) {
    $aviso = ['tipo' => 'warning', 'icono' => 'ph-clock-countdown', 'texto' => 'Su sesión expiró por inactividad. Inicie sesión de nuevo para continuar.'];
} elseif (isset($_GET['salida'])) {
    $aviso = ['tipo' => 'positive', 'icono' => 'ph-check-circle', 'texto' => 'Cerró la sesión correctamente.'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0c234b">
    <meta name="referrer" content="same-origin">
    <meta name="robots" content="noindex, nofollow">
    <title>Iniciar sesión · Salud UNEFA</title>
    <link rel="icon" type="image/png" href="<?= asset_url('assets/favicon-unefa.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset_url('assets/favicon-unefa.png') ?>">
    <link rel="preload" href="fonts/geist-latin.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset_url('vendor/phosphor/style.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/app.css') ?>">
</head>
<body class="login-body">
    <main class="login-layout">
        <section class="login-brand" aria-labelledby="loginBrandTitle">
            <div class="brand-lockup">
                <img src="<?= asset_url('assets/logo-unefa.png') ?>" class="brand-emblem brand-emblem-lg" width="48" height="55" alt="Escudo institucional de la UNEFA" decoding="async">
                <span><strong id="loginBrandTitle">Salud UNEFA</strong><small>Servicio médico universitario</small></span>
            </div>
            <div class="login-brand-copy">
                <h1>Expedientes, consultas y citas en un solo lugar.</h1>
                <p>Acceso exclusivo para el personal autorizado del servicio de salud. La información clínica es confidencial y cada acceso queda registrado.</p>
            </div>
            <ul class="login-points">
                <li><i class="ph ph-folder-open" aria-hidden="true"></i><span>Historia clínica por cédula</span></li>
                <li><i class="ph ph-calendar-check" aria-hidden="true"></i><span>Agenda de citas del día</span></li>
                <li><i class="ph ph-shield-check" aria-hidden="true"></i><span>Acceso por rol y registro de actividad</span></li>
            </ul>
        </section>

        <section class="login-panel" aria-labelledby="loginTitle">
            <div class="login-card">
                <div class="login-card-heading">
                    <h2 id="loginTitle">Iniciar sesión</h2>
                    <p>Use el usuario y la contraseña que le asignó el administrador.</p>
                </div>

                <?php if ($aviso): ?>
                <p class="login-notice is-<?= $aviso['tipo'] ?>" role="status"><i class="ph <?= $aviso['icono'] ?>" aria-hidden="true"></i><span><?= htmlspecialchars($aviso['texto'], ENT_QUOTES, 'UTF-8') ?></span></p>
                <?php endif; ?>

                <div id="loginError" class="login-notice is-negative" role="alert" hidden><i class="ph ph-warning-circle" aria-hidden="true"></i><span id="loginErrorText"></span></div>

                <form id="loginForm" class="login-form" method="post" action="api/login.php" novalidate>
                    <div class="field">
                        <label for="loginUsuario">Usuario</label>
                        <div class="input-icon"><i class="ph ph-user" aria-hidden="true"></i>
                            <input type="text" id="loginUsuario" name="usuario" required maxlength="50" autocomplete="username" autocapitalize="none" autocorrect="off" spellcheck="false" autofocus>
                        </div>
                    </div>
                    <div class="field">
                        <label for="loginPassword">Contraseña</label>
                        <div class="input-icon password-field"><i class="ph ph-lock-simple" aria-hidden="true"></i>
                            <input type="password" id="loginPassword" name="password" required maxlength="1024" autocomplete="current-password" aria-describedby="capsLockHint">
                            <button type="button" id="togglePassword" class="password-toggle" aria-label="Mostrar contraseña" aria-pressed="false"><i class="ph ph-eye" aria-hidden="true"></i></button>
                        </div>
                        <p id="capsLockHint" class="field-help caps-hint" hidden>Bloq Mayús está activado.</p>
                    </div>
                    <button type="submit" id="loginSubmit" class="btn btn-primary login-submit"><span>Entrar</span><i class="ph ph-arrow-right" aria-hidden="true"></i></button>
                </form>
                <p class="login-help">¿Olvidó su contraseña o tiene la cuenta bloqueada? Solicite a un administrador del servicio que la restablezca.</p>
            </div>
            <p class="login-footer"><span>UNEFA</span><span>Salud universitaria</span></p>
        </section>
    </main>

    <noscript><p class="login-noscript">Esta aplicación necesita JavaScript activado para funcionar.</p></noscript>

    <script>
    (() => {
        const form = document.getElementById('loginForm');
        const user = document.getElementById('loginUsuario');
        const password = document.getElementById('loginPassword');
        const submit = document.getElementById('loginSubmit');
        const errorBox = document.getElementById('loginError');
        const errorText = document.getElementById('loginErrorText');
        const toggle = document.getElementById('togglePassword');
        const capsHint = document.getElementById('capsLockHint');
        const submitLabel = submit.querySelector('span');

        // Quita los parámetros de aviso para que al recargar no se repita el mensaje.
        if (location.search) history.replaceState(null, '', location.pathname);

        function showError(message, field) {
            errorText.textContent = message;
            errorBox.hidden = false;
            [user, password].forEach(input => input.removeAttribute('aria-invalid'));
            if (field) { field.setAttribute('aria-invalid', 'true'); field.focus(); }
        }
        function clearError() {
            errorBox.hidden = true;
            errorText.textContent = '';
            [user, password].forEach(input => input.removeAttribute('aria-invalid'));
        }
        async function sessionOpened() {
            try {
                const response = await fetch('api/session.php', { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' } });
                const payload = await response.json();
                return response.ok && Boolean(payload && payload.success);
            } catch {
                return false;
            }
        }
        function setBusy(busy) {
            submit.disabled = busy;
            form.setAttribute('aria-busy', String(busy));
            submitLabel.textContent = busy ? 'Verificando…' : 'Entrar';
        }

        toggle.addEventListener('click', () => {
            const show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(show));
            toggle.setAttribute('aria-label', show ? 'Ocultar contraseña' : 'Mostrar contraseña');
            toggle.querySelector('i').className = `ph ${show ? 'ph-eye-slash' : 'ph-eye'}`;
            password.focus();
        });
        password.addEventListener('keyup', event => {
            if (typeof event.getModifierState === 'function') capsHint.hidden = !event.getModifierState('CapsLock');
        });
        password.addEventListener('blur', () => { capsHint.hidden = true; });
        [user, password].forEach(input => input.addEventListener('input', () => input.removeAttribute('aria-invalid')));

        form.addEventListener('submit', async event => {
            event.preventDefault();
            if (submit.disabled) return;
            clearError();
            const usuario = user.value.trim().toLowerCase();
            user.value = usuario;
            if (!usuario) { showError('Escriba su usuario.', user); return; }
            if (!password.value) { showError('Escriba su contraseña.', password); return; }

            setBusy(true);
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 20000);
            try {
                const body = new FormData();
                body.append('usuario', usuario);
                body.append('password', password.value);
                const response = await fetch('api/login.php', {
                    method: 'POST', body, credentials: 'same-origin', cache: 'no-store', signal: controller.signal,
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                let payload = null;
                try { payload = await response.json(); } catch { /* respuesta no JSON */ }
                if (response.ok && payload && payload.success) {
                    submitLabel.textContent = 'Entrando…';
                    location.replace('index.php');
                    return;
                }
                // Ante un fallo interno se comprueba si la sesión quedó abierta igualmente.
                if (response.status >= 500 && await sessionOpened()) {
                    location.replace('index.php');
                    return;
                }
                const message = payload && payload.message ? payload.message : 'No fue posible iniciar la sesión. Inténtelo de nuevo.';
                const errors = payload && payload.errors && typeof payload.errors === 'object' ? payload.errors : {};
                if (errors.usuario) showError(String(errors.usuario), user);
                else if (errors.password) showError(String(errors.password), password);
                else if (response.status === 401) {
                    password.value = '';
                    showError(message, password);
                } else showError(message, response.status === 429 ? null : user);
                setBusy(false);
            } catch (error) {
                showError(error.name === 'AbortError'
                    ? 'El servidor tardó demasiado en responder. Inténtelo de nuevo.'
                    : 'No se pudo conectar con el servidor. Revise su conexión e inténtelo de nuevo.');
                setBusy(false);
            } finally {
                clearTimeout(timer);
            }
        });
    })();
    </script>
</body>
</html>
