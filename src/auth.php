<?php
/**
 * AUTH HELPER - Funciones de autenticación centralizadas
 */

function auth_user_id()    { return $_SESSION['user_id']  ?? null; }
function auth_username()   { return $_SESSION['username'] ?? null; }
function auth_is_admin()   { return isset($_SESSION['role']) && $_SESSION['role'] === 'admin'; }
function auth_is_logged()  { return isset($_SESSION['user_id']); }

/**
 * Devuelve array de module_id completados por el usuario.
 * Requiere que R:: esté conectado.
 */
function get_user_completed_ids(int $user_id): array {
    if (!$user_id) return [];
    $rows = R::find('progress', 'user_id = ? AND completed = ?', [$user_id, 1]);
    return array_map(fn($r) => (int)$r->module_id, $rows);
}
