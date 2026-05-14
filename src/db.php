<?php
/**
 * CONEXIÓN CENTRALIZADA A BASE DE DATOS
 */
if (!class_exists('R')) {
    require 'rb-postgres.php';
}

if (!R::testConnection()) {
    // Si existe DATABASE_URL (en Render/Neon), la usamos. 
    // Si no, usamos los valores por defecto de tu Docker local.
    $db_url = getenv('DATABASE_URL');

    if ($db_url) {
        // Formato: postgres://user:pass@host:port/dbname
        // Convertimos el formato de Neon al formato de PDO que usa RedBean
        $url = parse_url($db_url);
        $host = $url['host'];
        $port = $url['port'] ?? 5432;
        $user = $url['user'];
        $pass = $url['pass'];
        $db   = ltrim($url['path'], '/');
        
        R::setup("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
    } else {
        // Valores locales (Docker)
        R::setup('pgsql:host=db;dbname=mydatabase', 'myuser', 'mypassword');
    }
}
