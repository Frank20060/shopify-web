<?php
/**
 * SIDEBAR - Con sistema de usuarios y progreso individual
 */
$current_page = basename($_SERVER['PHP_SELF']);
$current_slug = $_GET['module'] ?? null;

// --- CONEXIÓN SEGURA ---
require_once 'db.php';

// --- CATEGORÍAS ---
if (!isset($categories)) {
    $categories = R::findAll('category', 'ORDER BY name ASC');
}

// --- MÓDULOS AGRUPADOS ---
if (!isset($grouped_modules)) {
    $grouped_modules = [];
    foreach ($categories as $cat) {
        $grouped_modules[$cat->name] = R::find('module', 'category ILIKE ? ORDER BY id ASC', [$cat->slug]);
    }
}

// --- PROGRESO DEL USUARIO (para badges de completado) ---
$sb_user_id = $_SESSION['user_id'] ?? null;
if (!isset($user_completed_ids)) {
    $user_completed_ids = [];
    if ($sb_user_id) {
        $rows = R::find('progress', 'user_id = ? AND completed = ?', [$sb_user_id, 1]);
        $user_completed_ids = array_map(fn($r) => (int)$r->module_id, $rows);
    }
}

// --- ESTADO DE SESIÓN ---
$sb_username = $_SESSION['username'] ?? null;
$sb_is_admin = ($sb_username === 'superuser');
?>

<nav>
    <div class="logo">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none">
            <path d="M24.5 5.5L8.5 8.5L5.5 24.5L13.5 27.5L27.5 22.5L24.5 5.5Z" fill="var(--neon-green)"/>
            <path d="M12.5 12.5L19.5 10.5L21.5 21.5L15.5 23.5L12.5 12.5Z" fill="white"/>
        </svg>
        <span style="margin-left: 10px;">ShopifyMastery</span>
    </div>

    <ul class="nav-links" style="overflow-y: auto; flex-grow: 1; scrollbar-width: none;">
        <!-- DASHBOARD -->
        <li class="nav-item">
            <a href="index.php" class="nav-link <?= ($current_page === 'index.php' && !$current_slug) ? 'active' : '' ?>">
                🏠 Dashboard
            </a>
        </li>

        <!-- DOCUMENTACIÓN -->
        <li class="nav-item">
            <a href="docs.php" class="nav-link <?= ($current_page === 'docs.php') ? 'active' : '' ?>">
                📚 Documentación
            </a>
        </li>

        <!-- RETOS POR CATEGORÍA -->
        <?php
        $category_colors = [];
        foreach ($categories as $cat) {
            $category_colors[$cat->name] = $cat->color;
        }

        foreach ($grouped_modules as $category_name => $items):
            // Recuperar slug real del objeto categoría
            $cat_obj = null;
            foreach ($categories as $c) { if ($c->name === $category_name) { $cat_obj = $c; break; } }
            $category_slug = $cat_obj ? $cat_obj->slug : strtolower(str_replace(' ', '-', $category_name));

            // ¿Está abierta esta categoría? (el módulo activo pertenece a ella)
            $isOpen = (isset($current_module) && strcasecmp($current_module->category, $category_slug) === 0);
            $cat_color = $category_colors[$category_name] ?? '#5a31f4';

            // Contar completados en esta categoría
            $cat_completed = 0;
            foreach ($items as $item) {
                if (in_array((int)$item->id, $user_completed_ids)) $cat_completed++;
            }
        ?>
        <li class="nav-item category-group">
            <div class="category-header" onclick="toggleCategory('<?= $category_slug ?>')" style="--cat-color: <?= $cat_color ?>;">
                <span><?= $category_name ?></span>
                <div style="display:flex; align-items:center; gap: 6px;">
                    <?php if ($sb_user_id && $cat_completed > 0): ?>
                    <span style="font-size: 0.7rem; background: rgba(0,255,189,0.2); color: var(--neon-green); padding: 2px 8px; border-radius: 20px; font-weight: 700;">
                        <?= $cat_completed ?>/<?= count($items) ?>
                    </span>
                    <?php endif; ?>
                    <svg class="chevron" id="chevron-<?= $category_slug ?>" width="18" height="18" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5"
                         style="transform: <?= $isOpen ? 'rotate(180deg)' : 'rotate(0)' ?>; transition: transform 0.3s ease;">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </div>
            </div>
            <ul class="category-list" id="list-<?= $category_slug ?>"
                style="display: <?= $isOpen ? 'block' : 'none' ?>; --cat-color: <?= $cat_color ?>;">
                <?php if (count($items) > 0): ?>
                    <?php foreach ($items as $m):
                        $is_done = in_array((int)$m->id, $user_completed_ids);
                    ?>
                    <li class="category-item">
                        <a href="index.php?module=<?= $m->slug ?>"
                           class="nav-link <?= ($current_slug === $m->slug) ? 'active' : '' ?>">
                            <span class="item-title"><?= substr($m->title, 0, 30) ?><?= strlen($m->title) > 30 ? '...' : '' ?></span>
                            <?php if ($is_done): ?>
                                <span class="completion-badge">✓</span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="category-item" style="padding: 0.75rem 1rem; color: #888; font-size: 0.85rem;">
                        Sin ejercicios
                    </li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endforeach; ?>

        <!-- SEPARADOR -->
        <li style="border-top: 1px solid rgba(255,255,255,0.06); margin: 1rem 0.5rem;"></li>

        <!-- ADMIN — solo visible para administradores -->
        <?php if ($sb_is_admin): ?>
        <li class="nav-item">
            <a href="admin.php" class="nav-link" style="color: #f59e0b;">
                ⚙️ Panel de Admin
            </a>
        </li>
        <?php endif; ?>

        <!-- SECCIÓN DE USUARIO -->
        <li class="nav-item" style="margin-top: auto;">
            <?php if ($sb_user_id): ?>
            <!-- Usuario logueado -->
            <div style="display: flex; align-items: center; gap: 12px; padding: 0.8rem 1.2rem; border-radius: 16px; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.06);">
                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, var(--neon-green), #5a31f4); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1rem; color: #000; flex-shrink: 0;">
                    <?= strtoupper(substr($sb_username, 0, 1)) ?>
                </div>
                <div style="flex: 1; overflow: hidden;">
                    <div style="font-weight: 700; font-size: 0.9rem; color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        <?= htmlspecialchars($sb_username) ?>
                    </div>
                    <div style="font-size: 0.75rem; color: #888;"><?= $sb_is_admin ? '⚙️ Admin' : '🎓 Estudiante' ?></div>
                </div>
                <a href="index.php?logout=1" title="Cerrar sesión"
                   style="color: #888; display: flex; align-items: center; transition: color 0.2s;"
                   onmouseover="this.style.color='#ff4444'" onmouseout="this.style.color='#888'">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18.36 6.64a9 9 0 1 1-12.73 0"></path>
                        <line x1="12" y1="2" x2="12" y2="12"></line>
                    </svg>
                </a>
            </div>
            <?php else: ?>
            <!-- No logueado -->
            <button onclick="openAuthModal('login')"
                    style="width: 100%; padding: 0.9rem; background: rgba(0,255,189,0.08); border: 1px solid rgba(0,255,189,0.3); border-radius: 16px; color: var(--neon-green); font-weight: 700; font-size: 0.9rem; cursor: pointer; font-family: inherit; transition: all 0.3s ease;"
                    onmouseover="this.style.background='rgba(0,255,189,0.15)'"
                    onmouseout="this.style.background='rgba(0,255,189,0.08)'">
                🔐 Iniciar sesión
            </button>
            <?php endif; ?>
        </li>
    </ul>
</nav>

<script>
    function toggleCategory(categorySlug) {
        const list    = document.getElementById('list-' + categorySlug);
        const chevron = document.getElementById('chevron-' + categorySlug);
        if (!list) return;
        const isOpen = list.style.display === 'block';
        list.style.display    = isOpen ? 'none' : 'block';
        chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
        localStorage.setItem('categoryOpen_' + categorySlug, !isOpen);
    }

    window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.category-list').forEach(list => {
            const slug   = list.id.replace('list-', '');
            const wasOpen = localStorage.getItem('categoryOpen_' + slug) === 'true';
            if (wasOpen) {
                list.style.display = 'block';
                const ch = document.getElementById('chevron-' + slug);
                if (ch) ch.style.transform = 'rotate(180deg)';
            }
        });
        const active = document.querySelector('.nav-link.active');
        if (active) active.scrollIntoView({ block: 'center', behavior: 'smooth' });
    });
</script>
