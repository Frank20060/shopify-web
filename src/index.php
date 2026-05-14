<?php
/**
 * SHOPIFY MASTER HUB - ARCHIVO PRINCIPAL
 * Con sistema de usuarios y progreso individual
 */
session_start();
require 'Parsedown.php';
$parsedown = new Parsedown();

require 'db.php';
require 'auth.php';

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php'); exit;
}

// --- LOGIN ---
$login_error = '';
$reg_error   = '';
$auth_tab    = 'login'; // tab activo del modal

if (isset($_POST['do_login'])) {
    $u = R::findOne('user', 'username = ?', [trim($_POST['username'])]);
    if ($u && password_verify($_POST['password'], $u->password)) {
        $_SESSION['user_id']  = (int)$u->id;
        $_SESSION['username'] = $u->username;
        $_SESSION['role']     = $u->role;
        header('Location: ' . ($_POST['redirect'] ?? 'index.php')); exit;
    }
    $login_error = 'Usuario o contraseña incorrectos.';
}

// --- REGISTRO ---
if (isset($_POST['do_register'])) {
    $uname   = trim($_POST['reg_username'] ?? '');
    $pass    = $_POST['reg_password']  ?? '';
    $confirm = $_POST['reg_confirm']   ?? '';

    if (strlen($uname) < 3) {
        $reg_error = 'El nombre debe tener al menos 3 caracteres.';
    } elseif (strlen($pass) < 6) {
        $reg_error = 'La contraseña debe tener al menos 6 caracteres.';
    } elseif ($pass !== $confirm) {
        $reg_error = 'Las contraseñas no coinciden.';
    } elseif (R::findOne('user', 'username = ?', [$uname])) {
        $reg_error = 'Ese nombre de usuario ya está en uso.';
    } else {
        $nu           = R::dispense('user');
        $nu->username = $uname;
        $nu->password = password_hash($pass, PASSWORD_DEFAULT);
        $nu->role     = 'student';
        R::store($nu);
        $_SESSION['user_id']  = (int)$nu->id;
        $_SESSION['username'] = $nu->username;
        $_SESSION['role']     = $nu->role;
        header('Location: index.php'); exit;
    }
    $auth_tab = 'register';
}

// --- ESTADO DE SESIÓN ---
$logged_user_id = auth_user_id();
$is_admin       = auth_is_admin();

// --- MÓDULO ACTIVO ---
$current_slug   = $_GET['module'] ?? null;
$current_module = $current_slug ? R::findOne('module', 'slug = ?', [$current_slug]) : null;

// --- COMPLETAR EJERCICIO ---
if (isset($_POST['toggle_complete']) && $logged_user_id && $current_module) {
    $prog = R::findOne('progress', 'user_id = ? AND module_id = ?', [$logged_user_id, $current_module->id]);
    if (!$prog) {
        $prog            = R::dispense('progress');
        $prog->user_id   = $logged_user_id;
        $prog->module_id = (int)$current_module->id;
        $prog->completed = true;
    } else {
        $prog->completed = !$prog->completed;
    }
    $prog->updated_at = date('Y-m-d H:i:s');
    R::store($prog);
    header("Location: index.php?module={$current_slug}&success=1"); exit;
}

// --- PROGRESO DEL USUARIO ---
$user_completed_ids   = $logged_user_id ? get_user_completed_ids($logged_user_id) : [];
$is_module_completed  = $current_module ? in_array((int)$current_module->id, $user_completed_ids) : false;
$total_modules        = R::count('module');
$user_completed_count = count($user_completed_ids);
$progress_pct         = $total_modules > 0 ? round($user_completed_count / $total_modules * 100) : 0;

// --- MARKDOWN ---
$html_content = null;
if ($current_module && !empty($current_module->markdown_path)) {
    $fp = 'docs/md/' . $current_module->markdown_path;
    $html_content = file_exists($fp)
        ? $parsedown->text(file_get_contents($fp))
        : "<div class='alert-error'>⚠️ El archivo <code>{$current_module->markdown_path}</code> no existe.</div>";
}

// --- SIDEBAR DATA ---
$categories = R::findAll('category', 'ORDER BY name ASC');
$grouped_modules = [];
foreach ($categories as $cat) {
    $grouped_modules[$cat->name] = R::find('module', 'category ILIKE ? ORDER BY id ASC', [$cat->slug]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopify Dev Mastery | Hub de Aprendizaje</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        .markdown-body { color: #e0e0e0; line-height: 1.6; }
        .markdown-body h1, .markdown-body h2 { color: var(--accent-color, var(--neon-green)); margin: 1.5rem 0 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); padding-bottom: 5px; }
        .markdown-body code { background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-family: 'JetBrains Mono', monospace; font-size: 0.9em; }
        .markdown-body pre { background: #000 !important; border: 1px solid #333; padding: 1.5rem !important; border-radius: 12px; margin: 1.5rem 0; }
        .markdown-body ul { margin-left: 1.5rem; margin-bottom: 1rem; }
        .alert-error { background: rgba(255,68,68,0.1); border: 1px solid #ff4444; color: #ff4444; padding: 1rem; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>

    <?php include 'sidebar.php'; ?>

    <main>
        <?php if (!$current_module): ?>
            <section class="hero">
                <h1>Ruta de Maestría <br><span style="color: var(--neon-green)">Liquid &amp; Functions</span></h1>
                <p>Domina el desarrollo técnico de Shopify con nuestra selección de elite de ejercicios prácticos.</p>
            </section>

            <?php if ($logged_user_id): ?>
            <!-- BARRA DE PROGRESO DEL USUARIO -->
            <div class="card" style="margin-bottom: 2rem; padding: 1.5rem 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span style="font-weight: 700; color: var(--neon-green);">Tu progreso</span>
                    <span style="color: #888; font-size: 0.9rem;"><?= $user_completed_count ?> / <?= $total_modules ?> ejercicios</span>
                </div>
                <div style="background: rgba(255,255,255,0.05); border-radius: 100px; height: 8px; overflow: hidden;">
                    <div style="width: <?= $progress_pct ?>%; background: linear-gradient(90deg, var(--neon-green), #5a31f4); height: 100%; border-radius: 100px; transition: width 1s ease;"></div>
                </div>
                <span style="font-size: 0.8rem; color: #888; margin-top: 0.5rem; display: block;"><?= $progress_pct ?>% completado</span>
            </div>
            <?php else: ?>
            <!-- BANNER PARA NO LOGUEADOS -->
            <div class="card" style="margin-bottom: 2rem; border-color: rgba(0,255,189,0.3); background: rgba(0,255,189,0.03); text-align: center; padding: 1.5rem 2rem;">
                <p style="color: #aaa; margin-bottom: 1rem;">Inicia sesión para guardar tu progreso y ver tus estadísticas.</p>
                <button onclick="openAuthModal('login')" class="btn-primary" style="padding: 0.8rem 2rem; border: none; cursor: pointer; font-size: 1rem; font-family: inherit;">
                    🔐 Iniciar sesión
                </button>
            </div>
            <?php endif; ?>

            <div style="display: flex; gap: 1.5rem; margin-bottom: 3rem;">
                <a href="docs.php" class="card" style="flex: 1; text-decoration: none; border-color: var(--neon-green); background: linear-gradient(135deg, rgba(0,255,189,0.1) 0%, rgba(0,0,0,0.4) 100%);">
                    <h2 style="color: var(--neon-green); margin-bottom: 0.5rem;">📚 Documentación</h2>
                    <p style="color: #aaa; font-size: 0.95rem;">Documentación completa de Liquid y Shopify Functions. Archivos Markdown dinámicos.</p>
                    <span style="display: inline-block; margin-top: 1rem; color: var(--neon-green); font-weight: 700;">Ver Documentos →</span>
                </a>
            </div>

            <h2 style="margin-bottom: 1.5rem; font-weight: 800; letter-spacing: -1px;">Retos de la Ruta</h2>
            <div class="lesson-grid">
                <?php foreach (R::findAll('module', 'ORDER BY id ASC') as $m):
                    $is_done = in_array((int)$m->id, $user_completed_ids);
                ?>
                <div class="card <?= $is_done ? 'is-completed' : '' ?>" onclick="window.location.href='?module=<?= $m->slug ?>'">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="badge" style="background: rgba(255,255,255,0.1);"><?= $m->category ?></span>
                        <span class="badge" style="<?= $is_done ? 'background: var(--neon-green); color: #000;' : '' ?>">
                            <?= $is_done ? '✓ HECHO' : $m->difficulty ?>
                        </span>
                    </div>
                    <h3 style="margin-top: 1.5rem;"><?= $m->title ?></h3>
                    <p style="font-size: 0.9rem; margin-bottom: 0;"><?= substr($m->instruction, 0, 80) ?>...</p>
                </div>
                <?php endforeach; ?>
            </div>

        <?php else: ?>
            <section class="hero">
                <a href="index.php" style="color: var(--neon-green); text-decoration: none; font-weight: 600; margin-bottom: 2rem; display: inline-block;">← Volver al Dashboard</a>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="badge"><?= $current_module->category ?></span>
                    <span class="badge"><?= $current_module->difficulty ?></span>
                    <?php if (isset($_GET['success'])): ?>
                        <span style="color: var(--neon-green); font-weight: 800; animation: fadeIn 0.5s;">¡SIGUIENTE NIVEL DESBLOQUEADO! 🚀</span>
                    <?php endif; ?>
                </div>
                <h1 style="margin-top: 1rem;"><?= $current_module->title ?></h1>

                <?php if ($html_content): ?>
                <div class="card" style="margin-top: 2rem; border-color: rgba(255,255,255,0.1); background: rgba(255,255,255,0.02);">
                    <h4 style="color: var(--neon-green); margin-bottom: 1.5rem; font-size: 0.8rem; text-transform: uppercase;">📚 Documentación y Teoría:</h4>
                    <div class="markdown-body"><?= $html_content ?></div>
                </div>
                <?php endif; ?>

                <div class="card" style="background: rgba(0,128,96,0.05); border-color: var(--neon-green); margin-top: 2rem;">
                    <h4 style="color: var(--neon-green); margin-bottom: 0.5rem;">ENUNCIADO DEL EJERCICIO:</h4>
                    <p style="font-size: 1.2rem; color: white; line-height: 1.4;"><?= $current_module->instruction ?></p>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(0,128,96,0.2);">
                        <h4 style="color: #888; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px;">Resultado esperado:</h4>
                        <div style="background: #000; padding: 1rem; border-radius: 8px; border: 1px dashed #333; color: #00ffbd; font-family: 'JetBrains Mono', monospace; margin-top: 0.5rem;">
                            <?= htmlspecialchars($current_module->expected_output ?? 'N/A') ?>
                        </div>
                    </div>
                </div>
            </section>

            <div class="card" style="width: 100%; cursor: default; margin-top: -2rem;">
                <h3 style="margin-bottom: 1.5rem;">Tu Solución:</h3>
                <form method="POST">
                    <input type="hidden" name="redirect" value="index.php?module=<?= $current_slug ?>">
                    <textarea name="user_code" id="mainEditor" spellcheck="false" placeholder="Escribe tu código aquí..."
                              style="width: 100%; height: 300px; background: #000; color: #00ffbd; border: 1px solid rgba(255,255,255,0.1); border-radius: 16px; padding: 1.5rem; font-family: 'JetBrains Mono', monospace; font-size: 1rem; line-height: 1.5; resize: vertical; outline: none;"></textarea>

                    <div style="margin-top: 2rem; display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                        <button type="button" onclick="handleCodeTry()"
                                style="padding: 1rem 2rem; background: #00ffbd; border: none; border-radius: 12px; color: black; font-weight: 800; cursor: pointer;">
                            VALIDAR EJERCICIO
                        </button>

                        <button type="button" onclick="toggleHint()"
                                style="padding: 1rem 2.5rem; background: rgba(0,128,96,0.1); border: 1px solid var(--neon-green); border-radius: 12px; color: var(--neon-green); font-weight: 700; cursor: pointer;">
                            VER SOLUCIÓN ✅
                        </button>

                        <?php if ($logged_user_id): ?>
                        <button type="submit" name="toggle_complete" id="completeBtn"
                                style="padding: 1rem 2rem; background: #008060; border: none; border-radius: 12px; color: white; font-weight: 800; cursor: pointer; display: <?= $is_module_completed ? 'block' : 'none' ?>;">
                            <?= $is_module_completed ? '↩ Marcar como pendiente' : '¡COMPLETADO! 🎉' ?>
                        </button>
                        <?php else: ?>
                        <button type="button" onclick="openAuthModal('login')" id="completeBtn"
                                style="padding: 1rem 2rem; background: rgba(0,255,189,0.1); border: 1px solid var(--neon-green); border-radius: 12px; color: var(--neon-green); font-weight: 700; cursor: pointer; display: none;">
                            🔐 Inicia sesión para guardar
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div id="hintArea" class="card" style="margin-top: 2rem; display: none; border-color: var(--neon-green); background: rgba(0,128,96,0.05);">
                <h4 style="color: var(--neon-green);">SOLUCIÓN CORRECTA:</h4>
                <div class="code-block" style="margin-top: 1rem; background: #111;">
                    <pre><code class="language-javascript"><?= htmlspecialchars($current_module->solution ?? '') ?></code></pre>
                </div>
            </div>

            <div id="executionFeedback" class="card" style="margin-top: 2rem; display: none; border-color: var(--neon-green);">
                <h4 id="feedbackTitle">Resultado:</h4>
                <div id="feedbackContent" style="margin-top: 1rem; font-family: 'JetBrains Mono', monospace;"></div>
            </div>
        <?php endif; ?>
    </main>

    <!-- ============================================================ -->
    <!-- MODAL DE AUTENTICACIÓN                                        -->
    <!-- ============================================================ -->
    <div id="authModal" class="auth-modal-overlay" style="display: none;" onclick="closeModalOnOverlay(event)">
        <div class="auth-modal-box">
            <!-- Tabs -->
            <div class="auth-tabs">
                <button class="auth-tab active" id="tabLogin"    onclick="switchTab('login')">Iniciar sesión</button>
                <button class="auth-tab"         id="tabRegister" onclick="switchTab('register')">Crear cuenta</button>
                <div class="auth-tab-indicator" id="tabIndicator"></div>
            </div>

            <!-- LOGIN FORM -->
            <div id="formLogin" class="auth-form">
                <h2 class="auth-title">Bienvenido de nuevo 👋</h2>
                <?php if ($login_error): ?>
                <div class="auth-error"><?= htmlspecialchars($login_error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <div class="auth-field">
                        <label>Usuario</label>
                        <input type="text" name="username" placeholder="Tu nombre de usuario" required autofocus>
                    </div>
                    <div class="auth-field">
                        <label>Contraseña</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="do_login" class="auth-submit">Entrar →</button>
                </form>
                <p class="auth-switch">¿No tienes cuenta? <a href="#" onclick="switchTab('register')">Regístrate</a></p>
            </div>

            <!-- REGISTER FORM -->
            <div id="formRegister" class="auth-form" style="display: none;">
                <h2 class="auth-title">Crea tu cuenta 🚀</h2>
                <?php if ($reg_error): ?>
                <div class="auth-error"><?= htmlspecialchars($reg_error) ?></div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-json.min.js"></script>

    <script>
        // ---- MODAL AUTH ----
        function openAuthModal(tab = 'login') {
            document.getElementById('authModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            switchTab(tab);
            setTimeout(() => document.getElementById('authModal').classList.add('open'), 10);
        }
        function closeAuthModal() {
            document.getElementById('authModal').classList.remove('open');
            setTimeout(() => {
                document.getElementById('authModal').style.display = 'none';
                document.body.style.overflow = '';
            }, 300);
        }
        function closeModalOnOverlay(e) {
            if (e.target.id === 'authModal') closeAuthModal();
        }
        function switchTab(tab) {
            const isLogin = tab === 'login';
            document.getElementById('formLogin').style.display    = isLogin ? 'block' : 'none';
            document.getElementById('formRegister').style.display = isLogin ? 'none'  : 'block';
            document.getElementById('tabLogin').classList.toggle('active',    isLogin);
            document.getElementById('tabRegister').classList.toggle('active', !isLogin);
            document.getElementById('tabIndicator').style.transform = isLogin ? 'translateX(0)' : 'translateX(100%)';
        }

        // Abrir modal si hay errores PHP
        <?php if ($login_error): ?>  openAuthModal('login'); <?php endif; ?>
        <?php if ($reg_error):   ?>  openAuthModal('register'); <?php endif; ?>

        // ---- EJERCICIO ----
        const funnyErrors = [
            "❌ Ese código tiene menos sentido que vender bufandas en el desierto.",
            "❌ Error 404: Talento no encontrado. Revisa los typos.",
            "❌ Ni el soporte premium de Shopify podría arreglar esto.",
            "❌ ¡Por las barbas de Tobi Lütke! Eso no es código válido."
        ];
        const funnySuccess = [
            "🎯 ¡Boom! Lo has clavado.",
            "🚀 Código perfecto. Shopify está orgulloso de ti.",
            "💎 Tu código es más limpio que una Apple Store.",
            "🔥 ¡Está ardiendo! Eres un Senior Developer."
        ];

        function handleCodeTry() {
            const userCode      = document.getElementById('mainEditor')?.value.trim().replace(/\s+/g, ' ');
            if (!userCode) return;
            const feedback      = document.getElementById('executionFeedback');
            const feedbackContent = document.getElementById('feedbackContent');
            const feedbackTitle = document.getElementById('feedbackTitle');
            const completeBtn   = document.getElementById('completeBtn');
            feedback.style.display = 'block';
            feedback.scrollIntoView({ behavior: 'smooth' });
            const solution = '<?= addslashes($current_module->solution ?? '') ?>'.trim().replace(/\s+/g, ' ');
            if (userCode.toLowerCase() === solution.toLowerCase()) {
                feedbackTitle.innerHTML    = '✨ ¡BRUTAL!';
                feedbackTitle.style.color = '#00ffbd';
                feedbackContent.innerHTML = funnySuccess[Math.floor(Math.random() * funnySuccess.length)];
                if (completeBtn) completeBtn.style.display = 'block';
            } else {
                feedbackTitle.innerHTML    = '🤡 ¡ERROR!';
                feedbackTitle.style.color = '#ff4444';
                feedbackContent.innerHTML = funnyErrors[Math.floor(Math.random() * funnyErrors.length)];
            }
        }
        function toggleHint() {
            const h = document.getElementById('hintArea');
            h.style.display = h.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>