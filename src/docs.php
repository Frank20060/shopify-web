<?php
/**
 * DOCUMENTATION VIEWER - PAGINA PARA VER ARCHIVOS MD
 */
session_start();
require 'Parsedown.php';
$parsedown = new Parsedown();

require 'db.php';
require 'auth.php';

// --- LOGOUT ---
if (isset($_GET['logout'])) { session_destroy(); header('Location: docs.php'); exit; }

// --- LOGIN ---
$login_error = '';
$reg_error   = '';
$auth_tab    = 'login';

if (isset($_POST['do_login'])) {
    $u = R::findOne('user', 'username = ?', [trim($_POST['username'])]);
    if ($u && password_verify($_POST['password'], $u->password)) {
        $_SESSION['user_id']  = (int)$u->id;
        $_SESSION['username'] = $u->username;
        $_SESSION['role']     = $u->role;
        header('Location: ' . ($_POST['redirect'] ?? 'docs.php')); exit;
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
        $nu = R::dispense('user');
        $nu->username = $uname;
        $nu->password = password_hash($pass, PASSWORD_DEFAULT);
        $nu->role = 'student';
        R::store($nu);
        $_SESSION['user_id']  = (int)$nu->id;
        $_SESSION['username'] = $nu->username;
        $_SESSION['role']     = $nu->role;
        header('Location: docs.php'); exit;
    }
    $auth_tab = 'register';
}

// Obtener el archivo solicitado
$current_file = $_GET['file'] ?? null;
$html_content = null;
$title = 'Documentación';

// Si se especifica un archivo, intentar renderizarlo
if ($current_file) {
    $file_path = 'docs/md/' . basename($current_file); // Seguridad: solo basename
    if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'md') {
        $md_content = file_get_contents($file_path);
        $html_content = $parsedown->text($md_content);
        // Extraer título del primer heading
        if (preg_match('/^#\s+(.+)$/m', $md_content, $matches)) {
            $title = trim($matches[1]);
        }
    } else {
        $html_content = "<div class='alert-error'>⚠️ El archivo de documentación <code>{$current_file}</code> no existe o no es válido.</div>";
    }
}

// Obtener lista de archivos MD (excluyendo ocultos)
$hidden_docs_file = "docs/.hidden_docs.json";
$hidden_docs = [];
if (file_exists($hidden_docs_file)) {
    $hidden_docs = json_decode(file_get_contents($hidden_docs_file), true) ?: [];
}

$md_files = [];
$md_dir = 'docs/md/';
if (is_dir($md_dir)) {
    $files = scandir($md_dir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'md' && !in_array($file, $hidden_docs)) {
            $md_files[] = $file;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> | Documentación</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css">
    <style>
        /* Estilos para el contenido Markdown */
        .markdown-body {
            color: #e0e0e0;
            line-height: 1.6;
            max-width: 100%;
        }
        .markdown-body h1 {
            color: var(--neon-green);
            margin: 2rem 0 1rem;
            border-bottom: 2px solid var(--neon-green);
            padding-bottom: 0.5rem;
            font-size: 2.5rem;
        }
        .markdown-body h2 {
            color: var(--neon-green);
            margin: 2rem 0 1rem;
            border-bottom: 1px solid rgba(0, 255, 189, 0.3);
            padding-bottom: 0.3rem;
            font-size: 2rem;
        }
        .markdown-body h3 {
            color: #fff;
            margin: 1.5rem 0 0.8rem;
            border-left: 4px solid var(--neon-green);
            padding-left: 1rem;
            font-size: 1.5rem;
        }
        .markdown-body h4 {
            color: var(--neon-green);
            margin: 1.2rem 0 0.6rem;
            font-size: 1.2rem;
        }
        .markdown-body p {
            margin-bottom: 1rem;
        }
        .markdown-body code {
            background: rgba(0, 255, 189, 0.1);
            color: var(--neon-green);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85em;
        }
        .markdown-body pre {
            background: #000 !important;
            border: 1px solid var(--glass-border);
            padding: 1.5rem !important;
            border-radius: 12px;
            margin: 1.5rem 0;
            overflow-x: auto;
        }
        .markdown-body pre code {
            background: none;
            padding: 0;
            white-space: pre;
        }
        .markdown-body ul, .markdown-body ol {
            margin-left: 1.5rem;
            margin-bottom: 1rem;
        }
        .markdown-body li {
            margin-bottom: 0.5rem;
        }
        .markdown-body blockquote {
            margin: 2rem 0;
            padding: 1.5rem;
            background: rgba(0, 255, 189, 0.05);
            border-left: 5px solid var(--neon-green);
            border-radius: 0 12px 12px 0;
            color: #fff;
        }
        .markdown-body table {
            width: 100%;
            border-collapse: collapse;
            margin: 2rem 0;
            background: rgba(255,255,255,0.01);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--glass-border);
        }
        .markdown-body th, .markdown-body td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--glass-border);
        }
        .markdown-body th {
            background: rgba(0, 255, 189, 0.1);
            color: var(--neon-green);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 1px;
        }
        .alert-error {
            background: rgba(255, 68, 68, 0.1);
            border: 1px solid #ff4444;
            color: #ff4444;
            padding: 1rem;
            border-radius: 8px;
            margin: 2rem 0;
        }
        .docs-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .doc-card {
            background: var(--glass);
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
            text-decoration: none;
            color: inherit;
            transition: var(--transition);
            display: block;
        }
        .doc-card:hover {
            border-color: var(--neon-green);
            transform: translateY(-2px);
        }
        .doc-card h3 {
            color: var(--neon-green);
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        .doc-card p {
            color: var(--text-dim);
            font-size: 0.9rem;
            margin: 0;
        }
        a{
            color: var(--neon-green);
        }
    </style>
</head>
<body>
    <div class="bg-mesh"></div>

    <?php
    $categories = R::findAll('category', 'ORDER BY name ASC');
    $grouped_modules = [];
    
    foreach ($categories as $cat) {
        // ILIKE = case-insensitive: 'Liquid' (seeder) coincide con slug 'liquid' (admin)
        $grouped_modules[$cat->name] = R::find('module', 'category ILIKE ? ORDER BY id ASC', [$cat->slug]);
    }
    
    // Variables que necesita sidebar.php
    $current_module = null;
    $current_slug = null;
    ?>

    <?php include 'sidebar.php'; ?>

    <main style="max-width: 1200px;">
        <?php if (!$current_file): ?>
            <!-- Lista de documentos disponibles -->
            <section class="hero">
                <h1>📚 <span style="color: var(--neon-green)">Documentación</span></h1>
                <p>Archivos de documentación disponibles en formato Markdown.</p>
            </section>

            <div class="docs-list">
                <?php foreach ($md_files as $file): ?>
                    <?php
                    $file_path = 'docs/md/' . $file;
                    $content = file_get_contents($file_path);
                    $preview = '';
                    // Extraer primera línea no vacía después del título
                    $lines = explode("\n", $content);
                    $title_found = false;
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (!$line) continue;
                        if (strpos($line, '#') === 0 && !$title_found) {
                            $title_found = true;
                            continue;
                        }
                        if ($title_found && !empty($line)) {
                            $preview = substr($line, 0, 100) . (strlen($line) > 100 ? '...' : '');
                            break;
                        }
                    }
                    ?>
                    <a href="?file=<?php echo urlencode($file); ?>" class="doc-card">
                        <h3><?php echo htmlspecialchars(basename($file, '.md')); ?></h3>
                        <p><?php echo htmlspecialchars($preview); ?></p>
                    </a>
                <?php endforeach; ?>
            </div>

            <?php if (empty($md_files)): ?>
                <div style="text-align: center; padding: 3rem; color: var(--text-dim);">
                    <p>No hay archivos de documentación disponibles.</p>
                    <p>Sube archivos .md a la carpeta <code>docs/md/</code> para verlos aquí.</p>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <!-- Vista del documento -->
            <section class="hero">
                <h1><?php echo htmlspecialchars($title); ?></h1>
                <p><a href="docs.php" style="color: var(--neon-green); text-decoration: none;">← Volver a Documentación</a></p>
            </section>

            <div class="markdown-body">
                <?php echo $html_content; ?>
            </div>
        <?php endif; ?>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-liquid.min.js"></script>

    <?php include 'auth_modal.php'; ?>
</body>
</html>