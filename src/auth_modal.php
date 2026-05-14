<?php
/**
 * AUTH MODAL - Componente reutilizable de login/registro
 * Incluir antes de </body> en cualquier página que necesite autenticación.
 * Requiere que session_start() y auth.php ya estén cargados.
 */
$_am_login_error = $login_error ?? '';
$_am_reg_error   = $reg_error   ?? '';
$_am_tab         = $auth_tab    ?? 'login';
?>

<!-- MODAL DE AUTENTICACIÓN -->
<div id="authModal" class="auth-modal-overlay" style="display: none;" onclick="closeModalOnOverlay(event)">
    <div class="auth-modal-box">
        <!-- Tabs -->
        <div class="auth-tabs">
            <button class="auth-tab active" id="tabLogin"    onclick="switchTab('login')">Iniciar sesión</button>
            <button class="auth-tab"         id="tabRegister" onclick="switchTab('register')">Crear cuenta</button>
            <div class="auth-tab-indicator" id="tabIndicator"></div>
        </div>

        <!-- LOGIN -->
        <div id="formLogin" class="auth-form">
            <h2 class="auth-title">Bienvenido de nuevo 👋</h2>
            <?php if ($_am_login_error): ?>
            <div class="auth-error"><?= htmlspecialchars($_am_login_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                <div class="auth-field">
                    <label>Usuario</label>
                    <input type="text" name="username" placeholder="Tu nombre de usuario" required>
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" name="do_login" class="auth-submit">Entrar →</button>
            </form>
            <p class="auth-switch">¿No tienes cuenta? <a href="#" onclick="switchTab('register')">Regístrate</a></p>
        </div>

        <!-- REGISTRO -->
        <div id="formRegister" class="auth-form" style="display: none;">
            <h2 class="auth-title">Crea tu cuenta 🚀</h2>
            <?php if ($_am_reg_error): ?>
            <div class="auth-error"><?= htmlspecialchars($_am_reg_error) ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="auth-field">
                    <label>Nombre de usuario</label>
                    <input type="text" name="reg_username" placeholder="Elige un nombre" minlength="3" required>
                </div>
                <div class="auth-field">
                    <label>Contraseña</label>
                    <input type="password" name="reg_password" placeholder="Mínimo 6 caracteres" minlength="6" required>
                </div>
                <div class="auth-field">
                    <label>Confirmar contraseña</label>
                    <input type="password" name="reg_confirm" placeholder="Repite tu contraseña" required>
                </div>
                <button type="submit" name="do_register" class="auth-submit">Crear cuenta →</button>
            </form>
            <p class="auth-switch">¿Ya tienes cuenta? <a href="#" onclick="switchTab('login')">Inicia sesión</a></p>
        </div>

        <button class="auth-close" onclick="closeAuthModal()">✕</button>
    </div>
</div>

<script>
    function openAuthModal(tab) {
        tab = tab || 'login';
        document.getElementById('authModal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        switchTab(tab);
        setTimeout(function() {
            document.getElementById('authModal').classList.add('open');
        }, 10);
    }
    function closeAuthModal() {
        document.getElementById('authModal').classList.remove('open');
        setTimeout(function() {
            document.getElementById('authModal').style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
    }
    function closeModalOnOverlay(e) {
        if (e.target.id === 'authModal') closeAuthModal();
    }
    function switchTab(tab) {
        var isLogin = (tab === 'login');
        document.getElementById('formLogin').style.display    = isLogin ? 'block' : 'none';
        document.getElementById('formRegister').style.display = isLogin ? 'none'  : 'block';
        document.getElementById('tabLogin').classList.toggle('active',    isLogin);
        document.getElementById('tabRegister').classList.toggle('active', !isLogin);
        document.getElementById('tabIndicator').style.transform = isLogin ? 'translateX(0)' : 'translateX(100%)';
    }

    // Auto-abrir si hay errores PHP
    <?php if ($_am_login_error): ?> window.addEventListener('DOMContentLoaded', function(){ openAuthModal('login'); }); <?php endif; ?>
    <?php if ($_am_reg_error):   ?> window.addEventListener('DOMContentLoaded', function(){ openAuthModal('register'); }); <?php endif; ?>
</script>
