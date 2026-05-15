<?php
session_start();
require 'db.php';
require 'auth.php';

// SOLO EL SUPERUSER PUEDE ENTRAR AL PANEL
$is_admin = (auth_username() === 'superuser');

if (!$is_admin) {
    header("Location: index.php");
    exit;
}

// ENRUTAMIENTO
$view = $_GET['view'] ?? 'dashboard';

// CRUD LOGIC
if ($is_admin) {
    if (isset($_GET['delete'])) {
        $module = R::load('module', $_GET['delete']);
        R::trash($module);
        header("Location: admin.php?view=modules"); exit;
    }
    if (isset($_GET['delete_doc'])) {
        $doc_path = "docs/md/" . basename($_GET['delete_doc']);
        if (file_exists($doc_path)) {
            unlink($doc_path);
        }
        header("Location: admin.php?view=docs"); exit;
    }
    if (isset($_GET['toggle_doc'])) {
        $doc_name = basename($_GET['toggle_doc']);
        $hidden_docs_file = "docs/.hidden_docs.json";
        
        $hidden_docs = [];
        if (file_exists($hidden_docs_file)) {
            $hidden_docs = json_decode(file_get_contents($hidden_docs_file), true) ?: [];
        }
        
        if (in_array($doc_name, $hidden_docs)) {
            $hidden_docs = array_diff($hidden_docs, [$doc_name]);
        } else {
            $hidden_docs[] = $doc_name;
        }
        
        file_put_contents($hidden_docs_file, json_encode(array_values($hidden_docs)));
        header("Location: admin.php?view=docs"); exit;
    }
    if (isset($_POST['save_module'])) {
        $module = ($_POST['id']) ? R::load('module', $_POST['id']) : R::dispense('module');
        $module->title = $_POST['title'];
        $module->slug = $_POST['slug'];
        $module->category = $_POST['category'];
        $module->difficulty = $_POST['difficulty'];
        $module->instruction = $_POST['instruction'];
        $module->solution = $_POST['solution'];
        $module->expected_output = $_POST['expected_output'];
        $module->completed = false;
        
        if (isset($_FILES['md_file']) && $_FILES['md_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'docs/md/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            
            $filename = basename($_FILES['md_file']['name']);
            $target_path = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['md_file']['tmp_name'], $target_path)) {
                $module->markdown_path = $filename; // Guardamos solo el nombre del archivo
            }
        }
        R::store($module);
        header("Location: admin.php?view=modules"); exit;
    }
    if (isset($_POST['create_category'])) {
        $category = R::dispense('category');
        $category->name = $_POST['category_name'];
        $category->slug = $_POST['category_slug'];
        $category->description = $_POST['category_description'];
        $category->color = $_POST['category_color'] ?: '#10b981';
        R::store($category);
        header("Location: admin.php?view=categories"); exit;
    }
    if (isset($_GET['delete_category'])) {
        $category = R::load('category', $_GET['delete_category']);
        R::trash($category);
        header("Location: admin.php?view=categories"); exit;
    }
    if (isset($_POST['upload_md'])) {
        $target_dir = "docs/md/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        move_uploaded_file($_FILES["md_file"]["tmp_name"], $target_dir . basename($_FILES["md_file"]["name"]));
        header("Location: admin.php?view=docs"); exit;
    }
    if (isset($_GET['delete_user'])) {
        $u = R::load('user', $_GET['delete_user']);
        if ($u->id && $u->username !== 'superuser') {
            $progs = R::find('progress', 'user_id = ?', [$u->id]);
            R::trashAll($progs);
            R::trash($u);
        }
        header("Location: admin.php?view=users"); exit;
    }
}

// OBTENER DATOS
$modules = R::findAll('module', 'ORDER BY id DESC');
$total_modules = count($modules);
$categories = R::findAll('category', 'ORDER BY name ASC');
$all_users = R::findAll('user', 'ORDER BY id DESC');

// Obtener documentos ocultos
$hidden_docs_file = "docs/.hidden_docs.json";
$hidden_docs = [];
if (file_exists($hidden_docs_file)) {
    $hidden_docs = json_decode(file_get_contents($hidden_docs_file), true) ?: [];
}

$edit_module = null;
if (isset($_GET['edit'])) { 
    $edit_module = R::load('module', $_GET['edit']); 
    $view = 'module_form';
} elseif (isset($_GET['add'])) {
    $view = 'module_form';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración del sitio</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        /* ESTILO ADMIN PANEL MODERNO */
        :root {
            --bg-body: #0f1419;
            --bg-sidebar: #1a1f2e;
            --bg-header: #161b27;
            --bg-module: #1f2937;
            --bg-input: #252f3f;
            --text-main: #e2e8f0;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --accent-primary: #10b981;
            --accent-primary-hover: #059669;
            --accent-secondary: #3b82f6;
            --accent-danger: #ef4444;
            --accent-warning: #f59e0b;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif; 
            background-color: var(--bg-body); 
            color: var(--text-main); 
            font-size: 14px; 
            line-height: 1.6;
        }
        a { color: var(--accent-primary); text-decoration: none; transition: var(--transition); }
        a:hover { color: var(--accent-primary-hover); }

        /* HEADER */
        #header { 
            background: var(--bg-header); 
            padding: 16px 28px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }
        #branding h1 { font-size: 20px; font-weight: 600; color: #fff; margin: 0; letter-spacing: -0.5px; }
        #user-tools { font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 20px; }
        #user-tools strong { color: #fff; }
        #user-tools a { 
            color: var(--text-muted); 
            text-transform: uppercase; 
            letter-spacing: 0.5px; 
            font-size: 11px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            transition: var(--transition);
        }
        #user-tools a:hover { 
            color: #fff; 
            background: rgba(16, 185, 129, 0.1);
            text-decoration: none;
        }

        /* BREADCRUMBS */
        .breadcrumbs { 
            padding: 12px 28px; 
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-bottom: 1px solid var(--border-color); 
            font-size: 13px; 
            color: var(--text-muted);
        }
        .breadcrumbs a { color: var(--accent-secondary); }
        .breadcrumbs a:hover { color: var(--accent-primary); }

        /* LAYOUT PRINCIPAL */
        .main-layout { display: flex; min-height: calc(100vh - 89px); }
        
        /* SIDEBAR */
        #nav-sidebar { 
            width: 260px; 
            background: var(--bg-sidebar); 
            border-right: 1px solid var(--border-color); 
            padding: 24px 0; 
            overflow-y: auto;
        }
        .app-list h2 { 
            font-size: 11px; 
            text-transform: uppercase; 
            color: var(--text-muted); 
            padding: 8px 20px 10px; 
            border-bottom: 1px solid var(--border-color); 
            margin-bottom: 8px;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .app-list ul { list-style: none; }
        .app-list li a { 
            display: block; 
            padding: 10px 20px; 
            color: var(--text-muted); 
            font-size: 13px;
            transition: var(--transition);
            font-weight: 500;
            border-left: 3px solid transparent;
            margin: 2px 0;
        }
        .app-list li a:hover { 
            background: rgba(16, 185, 129, 0.1); 
            color: #fff; 
            text-decoration: none;
            border-left-color: var(--accent-primary);
        }
        .app-list li.active a { 
            background: rgba(16, 185, 129, 0.15); 
            color: var(--accent-primary); 
            text-decoration: none; 
            border-left-color: var(--accent-primary);
        }

        /* CONTENT */
        #content { 
            flex: 1; 
            padding: 32px 40px; 
            overflow-y: auto;
        }
        #content h1 { 
            font-size: 28px; 
            font-weight: 700; 
            margin-bottom: 24px; 
            color: #fff;
            letter-spacing: -0.5px;
        }
        #content h2 { 
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            margin-top: 24px;
            margin-bottom: 16px;
        }

        /* CARDS Y MODULES */
        .module { 
            background: var(--bg-module); 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            overflow: hidden; 
            margin-bottom: 24px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }
        .module:hover {
            border-color: var(--accent-primary);
            box-shadow: 0 8px 12px rgba(16, 185, 129, 0.1);
        }

        /* DASHBOARD CARDS */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        .stat-card {
            background: var(--bg-module);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 24px;
            transition: var(--transition);
        }
        .stat-card:hover {
            border-color: var(--accent-primary);
            transform: translateY(-4px);
        }
        .stat-card h3 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
            letter-spacing: 1px;
            font-weight: 600;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: var(--accent-primary);
        }

        /* TABLAS */
        table { width: 100%; border-collapse: collapse; }
        th { 
            background: rgba(255, 255, 255, 0.02); 
            padding: 14px 16px; 
            text-align: left; 
            font-size: 12px; 
            text-transform: uppercase; 
            color: var(--text-muted); 
            border-bottom: 1px solid var(--border-color); 
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        td { 
            padding: 14px 16px; 
            border-bottom: 1px solid var(--border-color); 
            font-size: 13px;
            color: var(--text-main);
        }
        tr:last-child td { border-bottom: none; }
        tr:hover { 
            background: rgba(16, 185, 129, 0.05); 
            transition: var(--transition);
        }
        td a {
            font-weight: 500;
        }
        td a:hover {
            text-decoration: underline;
        }

        /* STATUS BADGES */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-success { background: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-warning { background: rgba(245, 158, 11, 0.2); color: #f59e0b; }
        .badge-info { background: rgba(59, 130, 246, 0.2); color: #3b82f6; }
        .badge-danger { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

        /* BOTONES */
        .object-tools { 
            display: flex; 
            justify-content: flex-end; 
            margin-bottom: 20px; 
            gap: 10px;
        }
        .addlink { 
            background: var(--accent-primary); 
            color: #000; 
            padding: 10px 18px; 
            border-radius: 8px; 
            font-weight: 600; 
            font-size: 13px; 
            text-transform: uppercase;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .addlink:hover { 
            background: var(--accent-primary-hover); 
            text-decoration: none; 
            color: #000;
            transform: translateY(-2px);
        }
        
        .btn { 
            border: none; 
            padding: 10px 20px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 13px; 
            font-weight: 600; 
            text-transform: uppercase;
            transition: var(--transition);
            letter-spacing: 0.5px;
        }
        .btn-default { 
            background: var(--accent-primary); 
            color: #000;
        }
        .btn-default:hover { 
            background: var(--accent-primary-hover);
            transform: translateY(-2px);
        }
        .btn-danger { 
            background: var(--accent-danger); 
            color: #fff;
        }
        .btn-danger:hover { 
            background: #dc2626;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: var(--bg-input);
            color: var(--text-main);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            border-color: var(--accent-primary);
            color: var(--accent-primary);
        }

        /* FORMULARIOS */
        .form-row { 
            padding: 18px 20px; 
            border-bottom: 1px solid var(--border-color); 
            display: flex; 
            gap: 20px;
        }
        .form-row:last-child { border-bottom: none; }
        .form-row > div { flex: 1; }
        .form-row label { 
            display: block; 
            font-weight: 600; 
            color: var(--text-main); 
            margin-bottom: 8px; 
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-row input[type="text"], 
        .form-row input[type="email"],
        .form-row input[type="password"],
        .form-row input[type="number"],
        .form-row input[type="file"],
        .form-row select, 
        .form-row textarea { 
            width: 100%; 
            padding: 10px 14px; 
            background: var(--bg-input); 
            border: 1px solid var(--border-color); 
            color: #fff; 
            border-radius: 8px; 
            font-family: inherit; 
            font-size: 13px;
            transition: var(--transition);
        }
        .form-row input:focus, 
        .form-row textarea:focus,
        .form-row select:focus { 
            outline: none; 
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            background: var(--bg-input);
        }
        .form-row textarea { resize: vertical; min-height: 100px; }
        
        .submit-row { 
            padding: 18px 20px; 
            background: rgba(255, 255, 255, 0.02); 
            border-top: 1px solid var(--border-color); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-radius: 0 0 12px 12px;
            gap: 12px;
        }

        /* LOGIN */
        .login-wrapper { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(135deg, var(--bg-body) 0%, #1a2f4b 100%);
            position: relative;
        }
        .login-wrapper::before {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -100px;
            right: -100px;
            z-index: 0;
        }
        .login-box { 
            width: 420px; 
            background: var(--bg-module); 
            border: 1px solid var(--border-color); 
            border-radius: 16px; 
            overflow: hidden; 
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            z-index: 10;
            position: relative;
        }
        .login-header { 
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            padding: 32px 24px; 
            border-bottom: 1px solid var(--border-color); 
            text-align: center;
        }
        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
        }
        .login-body { padding: 32px 24px; }
        .login-body label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-muted);
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        .login-body input { 
            width: 100%; 
            margin-bottom: 16px; 
            padding: 12px 14px; 
            background: var(--bg-input); 
            border: 1px solid var(--border-color); 
            color: #fff; 
            border-radius: 8px;
            font-size: 13px;
            transition: var(--transition);
        }
        .login-body input:focus {
            border-color: var(--accent-primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        .errornote { 
            background: rgba(239, 68, 68, 0.1); 
            color: #fca5a5; 
            padding: 12px 14px; 
            margin-bottom: 16px; 
            border: 1px solid #991b1b; 
            border-radius: 8px; 
            font-size: 13px;
        }
        .login-body button {
            width: 100%;
            margin-top: 8px;
        }

        /* SEARCH BAR */
        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-box input {
            flex: 1;
            padding: 10px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 8px;
            font-size: 13px;
            transition: var(--transition);
        }
        .search-box input:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        /* MEJORA DE FORM-GROUPS Y INPUTS ESPECÍFICOS */
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--text-main);
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            background: var(--bg-input);
            border: 1px solid var(--border-color);
            color: #fff;
            border-radius: 10px;
            font-family: inherit;
            font-size: 13px;
            transition: var(--transition);
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--accent-primary);
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        /* File input styling */
        input[type="file"] {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 2px dashed var(--border-color) !important;
            padding: 15px !important;
            cursor: pointer;
            color: var(--text-muted);
        }
        input[type="file"]::file-selector-button {
            background: var(--accent-secondary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            margin-right: 15px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
        }
        input[type="file"]::file-selector-button:hover {
            background: var(--accent-primary);
            color: #000;
        }

        /* Color input styling */
        input[type="color"] {
            -webkit-appearance: none;
            border: none;
            width: 80px;
            height: 45px;
            cursor: pointer;
            background: none;
            padding: 0;
            vertical-align: middle;
        }
        input[type="color"]::-webkit-color-swatch-wrapper { padding: 0; }
        input[type="color"]::-webkit-color-swatch {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <!-- HEADER DJANGO STYLE -->
    <header id="header">
        <div id="branding">
            <h1><a href="admin.php" style="color:white; text-decoration:none;">Administración de ShopifyMastery</a></h1>
        </div>
        <div id="user-tools">
            BIENVENIDO, <strong><?= strtoupper(auth_username()) ?></strong>.
            <a href="index.php" target="_blank">Ver el sitio</a> /
            <a href="index.php?logout=1">Cerrar sesión</a>
        </div>
    </header>

    <!-- BREADCRUMBS -->
    <div class="breadcrumbs">
        <a href="admin.php">Inicio</a>
        <?php if ($view === 'modules'): ?>
            &rsaquo; <a href="?view=modules">Retos</a>
        <?php elseif ($view === 'module_form'): ?>
            &rsaquo; <a href="?view=modules">Retos</a> &rsaquo; <?= $edit_module ? $edit_module->title : 'Añadir reto' ?>
        <?php elseif ($view === 'docs'): ?>
            &rsaquo; <a href="?view=docs">Documentación</a>
        <?php elseif ($view === 'categories'): ?>
            &rsaquo; <a href="?view=categories">Categorías</a>
        <?php endif; ?>
    </div>

    <!-- MAIN LAYOUT -->
    <div class="main-layout">
        
        <!-- SIDEBAR -->
        <nav id="nav-sidebar">
            <div class="app-list">
                <h2>Recursos</h2>
                <ul>
                    <li class="<?= in_array($view, ['modules', 'module_form']) ? 'active' : '' ?>"><a href="?view=modules">Retos (Modules)</a></li>
                    <li class="<?= $view === 'categories' ? 'active' : '' ?>"><a href="?view=categories">Categorías</a></li>
                </ul>
                <h2 style="margin-top: 20px;">Gestor de Archivos</h2>
                <ul>
                    <li class="<?= $view === 'docs' ? 'active' : '' ?>"><a href="?view=docs">Documentación Markdown</a></li>
                </ul>
                <h2 style="margin-top: 20px;">Usuarios</h2>
                <ul>
                    <li class="<?= $view === 'users' ? 'active' : '' ?>"><a href="?view=users">Alumnos y Profesores</a></li>
                </ul>
            </div>
        </nav>

        <!-- CONTENT AREA -->
        <div id="content">
            
            <?php if ($view === 'dashboard'): ?>
                <h1>📊 Panel de Control</h1>
                
                <?php 
                $total_students = R::count('user', 'role = ?', ['student']);
                $total_progress = R::count('progress');
                ?>
                
                <div class="dashboard-grid">
                    <div class="stat-card">
                        <h3>📝 Total de Retos</h3>
                        <div class="value"><?= $total_modules ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>🎓 Alumnos</h3>
                        <div class="value"><?= $total_students ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>📈 Ejercicios Hechos</h3>
                        <div class="value"><?= $total_progress ?></div>
                    </div>
                </div>

                <div class="module">
                    <table>
                        <caption style="padding: 16px; text-align: left; font-weight: 600; color: #fff; background: rgba(255,255,255,0.02);"><strong>🎯 Acceso Rápido</strong></caption>
                        <tbody>
                            <tr>
                                <td>
                                    <strong style="color: var(--accent-primary);">Gestionar Retos</strong><br>
                                    <span style="font-size: 12px; color: var(--text-muted);">Crear, editar o eliminar retos de la plataforma</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="?view=modules" style="color: var(--accent-primary); margin-right: 15px; font-weight: 600;">Ver todos →</a>
                                    <a href="?add" style="color: var(--accent-secondary); font-weight: 600;">Crear nuevo →</a>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <strong style="color: var(--accent-primary);">Documentación</strong><br>
                                    <span style="font-size: 12px; color: var(--text-muted);">Subir y gestionar archivos Markdown</span>
                                </td>
                                <td style="text-align: right;">
                                    <a href="?view=docs" style="color: var(--accent-primary); font-weight: 600;">Ir a documentos →</a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($view === 'modules'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h1>📋 Gestión de Retos</h1>
                    <a href="?add" class="addlink">➕ Añadir nuevo reto</a>
                </div>

                <div class="search-box">
                    <input type="text" id="moduleSearch" placeholder="🔍 Buscar reto por título o slug...">
                </div>

                <div class="module">
                    <table id="modulesTable">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Título</th>
                                <th style="width: 15%;">Categoría</th>
                                <th style="width: 15%;">Dificultad</th>
                                <th style="width: 20%;">Estado</th>
                                <th style="width: 15%;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="modulesBody">
                            <?php foreach ($modules as $m): ?>
                            <tr data-title="<?= strtolower($m->title) ?>" data-slug="<?= strtolower($m->slug) ?>">
                                <td><strong><?= htmlspecialchars($m->title) ?></strong></td>
                                <td>
                                    <?php if ($m->category === 'Liquid'): ?>
                                        <span class="badge badge-info">💧 Liquid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">⚙️ Functions</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    $colors = [
                                        'Básico' => 'badge-success',
                                        'Intermedio' => 'badge-info', 
                                        'Avanzado' => 'badge-danger'
                                    ];
                                    $color = $colors[$m->difficulty] ?? 'badge-info';
                                    ?>
                                    <span class="badge <?= $color ?>"><?= $m->difficulty ?></span>
                                </td>
                                <td>
                                    <?php if ($m->completed): ?>
                                        <span class="badge badge-success">✅ Completado</span>
                                    <?php else: ?>
                                        <span class="badge">⏳ Pendiente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?= $m->id ?>" style="margin-right: 10px; font-weight: 600;">✏️ Editar</a>
                                    <a href="?delete=<?= $m->id ?>" style="color: var(--accent-danger); font-weight: 600;" onclick="return confirm('¿Estás seguro de que quieres eliminar este reto?')">🗑️ Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($modules)): ?>
                            <tr><td colspan="5" style="text-align:center; color: var(--text-muted); padding: 40px;">
                                <div style="font-size: 48px; margin-bottom: 10px;">📭</div>
                                No hay retos. <a href="?add" style="color: var(--accent-primary);">Crea uno ahora</a>
                            </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <script>
                    const searchInput = document.getElementById('moduleSearch');
                    const modulesBody = document.getElementById('modulesBody');
                    const rows = modulesBody.querySelectorAll('tr');

                    searchInput.addEventListener('keyup', function() {
                        const searchTerm = this.value.toLowerCase();
                        rows.forEach(row => {
                            const title = row.dataset.title || '';
                            const slug = row.dataset.slug || '';
                            if (title.includes(searchTerm) || slug.includes(searchTerm)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    });
                </script>

            <?php elseif ($view === 'module_form'): ?>
                <div style="display: flex; gap: 20px; margin-bottom: 24px; align-items: center;">
                    <div>
                        <h1><?= $edit_module ? '✏️ Editar Reto' : '➕ Crear Nuevo Reto' ?></h1>
                        <p style="color: var(--text-muted); margin-top: 4px;">
                            <?= $edit_module ? 'Actualiza los detalles de este reto' : 'Completa el formulario para crear un nuevo reto' ?>
                        </p>
                    </div>
                </div>

                <form method="POST" id="moduleForm">
                    <input type="hidden" name="id" value="<?= $edit_module ? $edit_module->id : '' ?>">
                    
                    <div class="module">
                        <!-- INFORMACIÓN BÁSICA -->
                        <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                            <h2 style="margin: 0 0 20px 0;">📝 Información Básica</h2>
                            <div class="form-row">
                                <div><label>Título del Reto:</label><input type="text" name="title" value="<?= $edit_module ? htmlspecialchars($edit_module->title) : '' ?>" required placeholder="Ej: Liquid 1: Output y Filtros"></div>
                            </div>
                            <div class="form-row">
                                <div><label>Slug (URL):</label><input type="text" name="slug" value="<?= $edit_module ? htmlspecialchars($edit_module->slug) : '' ?>" required placeholder="Ej: l-1"></div>
                            </div>
                            <div class="form-row">
                                <div>
                                    <label>Categoría:</label>
                                    <select name="category" required>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= htmlspecialchars($cat->slug) ?>" 
                                                    style="background: <?= $cat->color ?>; color: white;"
                                                    <?= ($edit_module && $edit_module->category == $cat->slug) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat->name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php if (empty($categories)): ?>
                                            <option value="" disabled>No hay categorías disponibles. Crea una primero.</option>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div>
                                    <label>Dificultad:</label>
                                    <select name="difficulty" required>
                                        <option value="Básico" <?= ($edit_module && $edit_module->difficulty == 'Básico') ? 'selected' : '' ?>>Básico</option>
                                        <option value="Intermedio" <?= ($edit_module && $edit_module->difficulty == 'Intermedio') ? 'selected' : '' ?>>Intermedio</option>
                                        <option value="Avanzado" <?= ($edit_module && $edit_module->difficulty == 'Avanzado') ? 'selected' : '' ?>>Avanzado</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- CONTENIDO -->
                        <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                            <h2 style="margin: 0 0 20px 0;">📚 Contenido</h2>
                            <div class="form-row" style="flex-direction: column;">
                                <label>Instrucciones:</label>
                                <textarea name="instruction" required placeholder="Describe qué debe hacer el usuario..."><?= $edit_module ? htmlspecialchars($edit_module->instruction) : '' ?></textarea>
                            </div>
                        </div>

                        <!-- SOLUCIONES -->
                        <div style="padding: 20px; border-bottom: 1px solid var(--border-color);">
                            <h2 style="margin: 0 0 20px 0;">💻 Soluciones</h2>
                            <div class="form-row">
                                <div>
                                    <label>Código/Solución:</label>
                                    <textarea name="solution" required placeholder="Código Liquid, JSON, SQL, etc..." style="font-family: 'Courier New', monospace; font-size: 12px;"><?= $edit_module ? htmlspecialchars($edit_module->solution) : '' ?></textarea>
                                </div>
                                <div>
                                    <label>Output Esperado:</label>
                                    <textarea name="expected_output" required placeholder="Resultado esperado tras ejecutar la solución..."><?= $edit_module ? htmlspecialchars($edit_module->expected_output) : '' ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="submit-row">
                            <div style="display: flex; gap: 10px;">
                                <a href="?view=modules" class="btn btn-secondary">← Volver</a>
                                <?php if($edit_module): ?>
                                    <a href="?delete=<?= $edit_module->id ?>" class="btn btn-danger" onclick="return confirm('¿Estás seguro? Esta acción no se puede deshacer.')">🗑️ Eliminar Reto</a>
                                <?php endif; ?>
                            </div>
                            <button type="submit" name="save_module" class="btn btn-default">💾 Guardar Cambios</button>
                        </div>
                    </div>
                </form>

            <?php elseif ($view === 'docs'): ?>
    <h1>📚 Gestor de Documentación Markdown</h1>
    <p style="color: var(--text-muted); margin-bottom: 24px;">Sube y gestiona archivos Markdown para la documentación de la plataforma</p>

    <div class="module" style="margin-bottom: 32px;">
        <div style="padding: 28px;">
            <h2 style="margin: 0 0 20px 0; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 24px;">📤</span> Subir Nuevo Archivo (.md)
            </h2>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Selecciona un archivo Markdown de tu equipo:</label>
                    <input type="file" name="md_file" accept=".md" required>
                    <p style="font-size: 11px; color: var(--text-muted); margin-top: 8px;">El archivo se guardará en la carpeta <code>docs/md/</code> y estará disponible para asignar a retos.</p>
                </div>
                <div style="margin-top: 25px;">
                    <button type="submit" name="upload_md" class="addlink" style="width: 100%; justify-content: center; padding: 14px;">🚀 SUBIR A LA CARPETA DOCS/MD/</button>
                </div>
            </form>
        </div>
    </div>

    <h2>📁 Archivos en el Servidor</h2>
    <div class="module">
        <table>
            <thead>
                <tr>
                    <th>Nombre de archivo</th>
                    <th>Tamaño</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $files = glob("docs/md/*.md");
                if (empty($files)): ?>
                    <tr><td colspan="4" style="text-align:center; padding: 40px;">No hay archivos en docs/md/</td></tr>
                <?php else: ?>
                    <?php foreach ($files as $file): 
                        $filename = basename($file);
                        $is_hidden = in_array($filename, $hidden_docs);
                    ?>
                    <tr>
                        <td><strong>📄 <?= $filename ?></strong></td>
                        <td><?= round(filesize($file) / 1024, 2) ?> KB</td>
                        <td>
                            <?php if ($is_hidden): ?>
                                <span class="badge badge-danger">👁️ Oculto</span>
                            <?php else: ?>
                                <span class="badge badge-success">👁️ Visible</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="?toggle_doc=<?= urlencode($filename) ?>" style="color: var(--accent-warning); margin-right: 12px;">
                                <?php echo $is_hidden ? '👁️ Mostrar' : '🙈 Ocultar'; ?>
                            </a>
                            <a href="?delete_doc=<?= urlencode($filename) ?>" 
                               style="color: var(--accent-danger); margin-right: 12px;"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar este archivo?')">🗑️ Eliminar</a>
                            <a href="<?= $file ?>" download style="color: var(--accent-secondary);">⬇️ Descargar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

            <?php elseif ($view === 'categories'): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                    <h1>🏷️ Gestión de Categorías</h1>
                    <button class="addlink" onclick="document.getElementById('categoryForm').style.display='block'">➕ Crear Categoría</button>
                </div>

                <div id="categoryForm" class="module" style="display: none; margin-bottom: 32px; border-color: var(--accent-primary);">
                    <div style="padding: 28px;">
                        <h2 style="margin-bottom: 24px; color: var(--accent-primary);">🏷️ Crear Nueva Categoría</h2>
                        <form method="POST">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div class="form-group">
                                    <label>Nombre de la categoría:</label>
                                    <input type="text" name="category_name" placeholder="Ej: Liquid Avanzado" required>
                                </div>
                                <div class="form-group">
                                    <label>Slug (URL amigable):</label>
                                    <input type="text" name="category_slug" placeholder="Ej: liquid-adv" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Descripción de la categoría:</label>
                                <textarea name="category_description" rows="3" placeholder="Describe qué aprenderán los alumnos en esta sección..."></textarea>
                            </div>
                            <div class="form-group">
                                <label>Color Identificativo:</label>
                                <div style="display: flex; align-items: center; gap: 15px; background: rgba(255,255,255,0.02); padding: 10px; border-radius: 12px; border: 1px solid var(--border-color);">
                                    <input type="color" name="category_color" value="#10b981">
                                    <span style="font-size: 12px; color: var(--text-muted);">Este color se usará en los badges y menús.</span>
                                </div>
                            </div>
                            <div class="submit-row" style="background: none; padding: 20px 0 0 0;">
                                <button type="button" onclick="document.getElementById('categoryForm').style.display='none'" class="btn btn-secondary" style="padding: 12px 25px;">CANCELAR</button>
                                <button type="submit" name="create_category" class="addlink" style="padding: 12px 30px;">💾 CREAR CATEGORÍA</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="module">
                    <table>
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Slug</th>
                                <th>Descripción</th>
                                <th>Color</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cat->name) ?></strong></td>
                                <td><code><?= htmlspecialchars($cat->slug) ?></code></td>
                                <td><?= htmlspecialchars($cat->description) ?: 'Sin descripción' ?></td>
                                <td><span style="background: <?= $cat->color ?>; padding: 4px 8px; border-radius: 4px; color: white; font-size: 12px;">Color</span></td>
                                <td>
                                    <a href="?delete_category=<?= $cat->id ?>" 
                                       style="color: var(--accent-danger);"
                                       onclick="return confirm('¿Estás seguro?')">🗑️ Eliminar</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color: var(--text-muted);">No hay categorías creadas aún.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

<?php elseif ($view === 'users'): ?>
                <h1>👥 Gestión de Usuarios</h1>
                <p style="color: var(--text-muted); margin-bottom: 24px;">Lista de alumnos registrados y su progreso actual</p>

                <div class="module">
                    <table>
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Rol</th>
                                <th>Progreso</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_users as $u): 
                                $completed_count = R::count('progress', 'user_id = ? AND completed = ?', [$u->id, 1]);
                                $pct = $total_modules > 0 ? round(($completed_count / $total_modules) * 100) : 0;
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 30px; height: 30px; border-radius: 50%; background: var(--accent-primary); color: #000; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                                            <?= strtoupper(substr($u->username, 0, 1)) ?>
                                        </div>
                                        <strong><?= htmlspecialchars($u->username) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge <?= $u->role === 'admin' ? 'badge-danger' : 'badge-info' ?>">
                                        <?= strtoupper($u->role) ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="flex: 1; min-width: 100px; height: 6px; background: rgba(255,255,255,0.05); border-radius: 10px; overflow: hidden;">
                                            <div style="width: <?= $pct ?>%; height: 100%; background: var(--accent-primary);"></div>
                                        </div>
                                        <span style="font-size: 11px; color: var(--text-muted);"><?= $completed_count ?>/<?= $total_modules ?></span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($u->username !== 'superuser'): ?>
                                        <a href="?delete_user=<?= $u->id ?>" style="color: var(--accent-danger);" onclick="return confirm('¿Eliminar a este usuario?')">🗑️ Eliminar</a>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 11px;">Ineliminable</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
